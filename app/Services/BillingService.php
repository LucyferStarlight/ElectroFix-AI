<?php

namespace App\Services;

use App\Models\BillingDocument;
use App\Models\BillingDocumentItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Exceptions\QuoteVersionException;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        private readonly RepairOutcomeService $repairOutcomeService,
        private readonly OrderStateMachine $orderStateMachine,
        private readonly OrderWorkflowService $orderWorkflowService
    ) {}

    public function createDocument(Company $company, User $actor, array $payload): BillingDocument
    {
        return DB::transaction(function () use ($company, $actor, $payload): BillingDocument {
            return $this->persistDocument($company, $actor, $payload);
        });
    }

    public function createNewQuoteVersion(BillingDocument $quote, User $actor, array $payload): BillingDocument
    {
        if (! $quote->isQuote()) {
            throw QuoteVersionException::onlyQuotesCanBeVersioned();
        }

        $quote->loadMissing('order', 'company');

        if (! $quote->order || ! $quote->company) {
            throw QuoteVersionException::quoteRequiresSingleOrder();
        }

        return DB::transaction(function () use ($quote, $actor, $payload): BillingDocument {
            $basePayload = array_merge([
                'document_type' => 'quote',
                'source' => $quote->source,
                'customer_mode' => $quote->customer_mode,
                'customer_id' => $quote->customer_id,
                'walk_in_name' => $quote->walk_in_name,
                'tax_mode' => $quote->tax_mode,
                'notes' => $quote->notes,
                'items' => $quote->items()
                    ->get()
                    ->map(fn (BillingDocumentItem $item): array => [
                        'item_kind' => $item->item_kind,
                        'description' => $item->description,
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'inventory_item_id' => $item->inventory_item_id,
                        'order_id' => $item->order_id,
                    ])->all(),
            ], $payload);

            $basePayload['document_type'] = 'quote';

            return $this->persistDocument($quote->company, $actor, $basePayload, $quote->order);
        });
    }

    private function buildItemsAndTotals(Company $company, float $vatRate, string $taxMode, array $rawItems): array
    {
        $subtotal = 0.0;
        $vatAmount = 0.0;
        $total = 0.0;
        $items = [];

        foreach ($rawItems as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $inventoryItemId = ! empty($item['inventory_item_id']) ? (int) $item['inventory_item_id'] : null;
            $orderId = ! empty($item['order_id']) ? (int) $item['order_id'] : null;

            if ($item['item_kind'] === 'product' && $inventoryItemId) {
                $inventoryItem = InventoryItem::query()
                    ->where('company_id', $company->id)
                    ->find($inventoryItemId);

                if (! $inventoryItem) {
                    abort(422, 'Uno de los productos de inventario no pertenece a tu empresa.');
                }

                if (! $inventoryItem->is_sale_enabled) {
                    abort(422, sprintf(
                        'El producto %s no está habilitado para venta.',
                        $inventoryItem->name
                    ));
                }

                if ($quantity > $inventoryItem->quantity) {
                    abort(422, sprintf(
                        'La cantidad solicitada para %s supera el stock disponible (%d).',
                        $inventoryItem->name,
                        $inventoryItem->quantity
                    ));
                }
            }

            if ($item['item_kind'] === 'service' && $orderId) {
                $order = Order::query()
                    ->where('company_id', $company->id)
                    ->find($orderId);

                if (! $order) {
                    abort(422, 'Uno de los servicios seleccionados no pertenece a tu empresa.');
                }
            }

            $lineRaw = $quantity * $unitPrice;

            if ($taxMode === 'included') {
                $lineTotal = round($lineRaw, 2);
                $lineSubtotal = round($lineTotal / (1 + $vatRate), 2);
                $lineVat = round($lineTotal - $lineSubtotal, 2);
            } else {
                $lineSubtotal = round($lineRaw, 2);
                $lineVat = round($lineSubtotal * $vatRate, 2);
                $lineTotal = round($lineSubtotal + $lineVat, 2);
            }

            $subtotal += $lineSubtotal;
            $vatAmount += $lineVat;
            $total += $lineTotal;

            $items[] = [
                'inventory_item_id' => $inventoryItemId,
                'order_id' => $orderId,
                'item_kind' => $item['item_kind'],
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_subtotal' => $lineSubtotal,
                'line_vat' => $lineVat,
                'line_total' => $lineTotal,
            ];
        }

        return [round($subtotal, 2), round($vatAmount, 2), round($total, 2), $items];
    }

    private function attachWalkInServiceOrders(Company $company, User $actor, BillingDocument $document, array $items): array
    {
        $walkInName = $document->walk_in_name ?: 'Cliente de Mostrador';

        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'name' => $walkInName,
            'email' => sprintf('walkin+%d@%s.local', $document->id, strtolower($company->country ?: 'mx')),
            'phone' => $company->billing_phone ?: $company->owner_phone ?: 'PENDIENTE',
            'address' => 'Mostrador',
        ]);

        $equipment = Equipment::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => 'Servicio en Mostrador',
            'brand' => 'Mostrador',
            'model' => 'Atención inmediata',
            'serial_number' => 'WALKIN-'.$document->id,
        ]);

        foreach ($items as &$item) {
            if ($item['item_kind'] !== 'service') {
                continue;
            }

            $order = Order::query()->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician' => $actor->name,
                'symptoms' => $item['description'],
                'status' => OrderStatus::DELIVERED,
                'estimated_cost' => $item['line_total'],
                'ai_potential_causes' => null,
                'ai_estimated_time' => null,
                'ai_suggested_parts' => null,
                'ai_technical_advice' => null,
            ]);

            $item['order_id'] = $order->id;
        }

        return $items;
    }

    private function applyLinkedServiceOrderStatus(Company $company, array $items, string $documentType): void
    {
        $targetStatus = $documentType === 'quote'
            ? OrderStatus::QUOTED
            : OrderStatus::COMPLETED;

        foreach ($items as $item) {
            if ($item['item_kind'] !== 'service' || empty($item['order_id'])) {
                continue;
            }

            $order = Order::query()
                ->where('company_id', $company->id)
                ->find($item['order_id']);

            if (! $order) {
                continue;
            }

            if ($this->orderStateMachine->canTransition($order->status, $targetStatus)) {
                $this->orderStateMachine->transition($order, $targetStatus);
            }
        }
    }

    private function persistDocument(
        Company $company,
        User $actor,
        array $payload,
        ?Order $forcedOrder = null
    ): BillingDocument {
        $vatRate = ((float) $company->vat_percentage) / 100;
        $taxMode = $payload['tax_mode'];

        [$subtotal, $vatAmount, $total, $items] = $this->buildItemsAndTotals($company, $vatRate, $taxMode, $payload['items']);

        $quoteOrder = $forcedOrder ?? $this->resolveQuoteOrder($company, $payload['document_type'], $items);
        if ($payload['document_type'] === 'quote' && $quoteOrder) {
            $this->orderWorkflowService->ensureCanQuote($quoteOrder);
        }

        $version = $this->resolveDocumentVersion($quoteOrder, $payload['document_type']);
        $status = $this->resolveDocumentStatus($payload['document_type'], $payload);
        $isActive = $this->resolveDocumentActiveFlag($payload['document_type'], $status);

        if ($payload['customer_mode'] === 'walk_in') {
            $quoteOrder = null;
        }

        $document = BillingDocument::query()->create([
            'company_id' => $company->id,
            'user_id' => $actor->id,
            'customer_id' => $payload['customer_mode'] === 'registered' ? $payload['customer_id'] : null,
            'order_id' => $quoteOrder?->id,
            'document_number' => $this->nextDocumentNumber($company),
            'document_type' => $payload['document_type'],
            'version' => $version,
            'status' => $status,
            'is_active' => $isActive,
            'customer_mode' => $payload['customer_mode'],
            'walk_in_name' => $payload['customer_mode'] === 'walk_in'
                ? ($payload['walk_in_name'] ?: 'Cliente de Mostrador')
                : null,
            'source' => $payload['source'],
            'tax_mode' => $taxMode,
            'vat_percentage' => $company->vat_percentage,
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'total' => $total,
            'notes' => $payload['notes'] ?? null,
            'issued_at' => now(),
        ]);

        if (in_array($document->source, ['repair', 'mixed'], true) && $document->document_type === 'invoice') {
            $this->assertRepairOrdersCanBeClosed($company, $items);
        }

        if ($payload['customer_mode'] === 'walk_in') {
            $items = $this->attachWalkInServiceOrders($company, $actor, $document, $items);
        } else {
            $this->applyLinkedServiceOrderStatus($company, $items, $payload['document_type']);
        }

        foreach ($items as $item) {
            BillingDocumentItem::query()->create([
                'billing_document_id' => $document->id,
                ...$item,
            ]);
        }

        if ($document->document_type === 'quote' && $quoteOrder) {
            $this->deactivateOtherQuoteVersions($quoteOrder, $document);
        }

        if ($document->document_type === 'invoice') {
            $this->consumeInventoryForInvoice($company, $items, $actor);
        }

        if (in_array($document->source, ['repair', 'mixed'], true) && $document->document_type === 'invoice' && isset($payload['repair_outcome'])) {
            $this->repairOutcomeService->closeFromBillingDocument($document, $payload);
        }

        return $document->fresh(['items.order', 'items.inventoryItem', 'customer', 'company', 'user', 'order']);
    }

    private function resolveQuoteOrder(Company $company, string $documentType, array $items): ?Order
    {
        if ($documentType !== 'quote') {
            return null;
        }

        $orderIds = collect($items)
            ->filter(fn (array $item): bool => $item['item_kind'] === 'service' && ! empty($item['order_id']))
            ->pluck('order_id')
            ->unique()
            ->values();

        if ($orderIds->count() !== 1) {
            throw QuoteVersionException::quoteRequiresSingleOrder();
        }

        $order = Order::query()
            ->where('company_id', $company->id)
            ->find($orderIds->first());

        if (! $order) {
            throw QuoteVersionException::quoteOrderMismatch();
        }

        return $order;
    }

    private function resolveDocumentVersion(?Order $order, string $documentType): int
    {
        if ($documentType !== 'quote' || ! $order) {
            return 1;
        }

        return (int) BillingDocument::query()
            ->where('order_id', $order->id)
            ->where('document_type', 'quote')
            ->max('version') + 1;
    }

    private function resolveDocumentStatus(string $documentType, array $payload): string
    {
        if ($documentType === 'quote') {
            return in_array($payload['status'] ?? 'draft', ['draft', 'sent'], true)
                ? (string) ($payload['status'] ?? 'draft')
                : 'draft';
        }

        return 'approved';
    }

    private function resolveDocumentActiveFlag(string $documentType, string $status): bool
    {
        if ($documentType !== 'quote') {
            return false;
        }

        return in_array($status, ['draft', 'sent', 'approved'], true);
    }

    private function deactivateOtherQuoteVersions(Order $order, BillingDocument $currentDocument): void
    {
        BillingDocument::query()
            ->where('order_id', $order->id)
            ->where('document_type', 'quote')
            ->whereKeyNot($currentDocument->id)
            ->update(['is_active' => false]);
    }

    private function resolveSingleRepairOrder(BillingDocument $document): ?Order
    {
        $document->loadMissing('items.order');

        $orders = $document->items
            ->filter(fn (BillingDocumentItem $item): bool => $item->item_kind === 'service' && $item->order !== null)
            ->pluck('order')
            ->filter()
            ->unique('id')
            ->values();

        if ($orders->count() !== 1) {
            return null;
        }

        /** @var Order $order */
        $order = $orders->first();

        return $order;
    }

    private function assertRepairOrdersCanBeClosed(Company $company, array $items): void
    {
        $orderIds = collect($items)
            ->filter(fn (array $item): bool => $item['item_kind'] === 'service' && ! empty($item['order_id']))
            ->pluck('order_id')
            ->unique();

        foreach ($orderIds as $orderId) {
            $order = Order::query()
                ->where('company_id', $company->id)
                ->find($orderId);

            if ($order) {
                $this->orderWorkflowService->ensureCanRepair($order);
            }
        }
    }

    private function consumeInventoryForInvoice(Company $company, array $items, User $actor): void
    {
        foreach ($items as $item) {
            if ($item['item_kind'] !== 'product' || empty($item['inventory_item_id'])) {
                continue;
            }

            $inventory = InventoryItem::query()
                ->where('company_id', $company->id)
                ->findOrFail((int) $item['inventory_item_id']);

            $qtyToDiscount = (int) ceil((float) $item['quantity']);

            if ($inventory->quantity < $qtyToDiscount) {
                abort(422, sprintf(
                    'Stock insuficiente para %s. Disponible: %d, requerido: %d.',
                    $inventory->name,
                    $inventory->quantity,
                    $qtyToDiscount
                ));
            }

            app(InventoryService::class)->adjustStock($inventory, $actor, [
                'movement_type' => 'removal',
                'quantity' => $qtyToDiscount,
                'notes' => 'Descuento por facturación emitida',
            ]);
        }
    }

    private function nextDocumentNumber(Company $company): string
    {
        $prefix = 'DOC-'.str_pad((string) $company->id, 3, '0', STR_PAD_LEFT).'-';

        $last = BillingDocument::query()
            ->where('company_id', $company->id)
            ->where('document_number', 'like', $prefix.'%')
            ->latest('id')
            ->first();

        if (! $last) {
            return $prefix.'000001';
        }

        $number = (int) substr($last->document_number, -6);

        return $prefix.str_pad((string) ($number + 1), 6, '0', STR_PAD_LEFT);
    }
}
