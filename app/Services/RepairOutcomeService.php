<?php

namespace App\Services;

use App\Models\BillingDocument;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderRepairOutcome;
use App\Models\User;
use App\Services\Exceptions\OutcomeNotFoundException;
use App\Services\Exceptions\RepairOutcomeAlreadyClosedException;
use App\Support\OrderStatus;

class RepairOutcomeService
{
    public function __construct(private readonly OrderStateMachine $orderStateMachine) {}

    public function closeFromBillingDocument(BillingDocument $doc, array $data): OrderRepairOutcome
    {
        $doc->loadMissing(['company.subscription', 'items.order.latestDiagnostic']);

        $order = $this->resolveSingleOrder($doc);
        if (! $order) {
            throw new \InvalidArgumentException(
                'Para documentos de reparación o mixtos debe existir exactamente una orden de servicio vinculada.'
            );
        }

        $alreadyClosed = OrderRepairOutcome::query()
            ->where('order_id', $order->id)
            ->exists();

        if ($alreadyClosed) {
            throw new RepairOutcomeAlreadyClosedException('La orden ya cuenta con un cierre de reparación.');
        }

        $company = $doc->company;
        $planAtClose = (string) ($company->subscription?->plan ?? 'starter');
        $hadAiDiagnosis = $order->ai_diagnosed_at !== null;

        return OrderRepairOutcome::query()->create([
            'order_id' => $order->id,
            'billing_document_id' => $doc->id,
            'company_id' => $company->id,
            'repair_outcome' => (string) $data['repair_outcome'],
            'outcome_notes' => $data['outcome_notes'] ?? null,
            'work_performed' => (string) $data['work_performed'],
            'actual_amount_charged' => (float) $data['actual_amount_charged'],
            'aris_estimated_cost' => $this->resolveAiEstimatedCost($order),
            'had_ai_diagnosis' => $hadAiDiagnosis,
            'feeds_aris_training' => $this->shouldFeedAiDataset($order, $company),
            'plan_at_close' => $planAtClose,
        ]);
    }

    public function markDelivered(Order $order, User $actor): OrderRepairOutcome
    {
        $outcome = OrderRepairOutcome::query()
            ->where('order_id', $order->id)
            ->first();

        if (! $outcome) {
            throw new OutcomeNotFoundException('No existe cierre de reparación para esta orden.');
        }

        if ($actor->role !== 'developer' && $outcome->company_id !== $actor->company_id) {
            abort(403, 'No puedes marcar entrega de órdenes de otra empresa.');
        }

        if (! $outcome->delivered_at) {
            $outcome->update([
                'delivered_at' => now(),
            ]);
        }

        if ($this->orderStateMachine->canTransition($order->status, OrderStatus::DELIVERED)) {
            $this->orderStateMachine->transition($order, OrderStatus::DELIVERED);
        }

        return $outcome->fresh();
    }

    public function updateFeedback(Order $order, array $data): OrderRepairOutcome
    {
        $outcome = OrderRepairOutcome::query()->where('order_id', $order->id)->first();
        if (! $outcome) {
            throw new OutcomeNotFoundException('No existe cierre de reparación para esta orden.');
        }

        $outcome->update([
            'diagnostic_accuracy' => $data['diagnostic_accuracy'],
            'technician_notes' => $data['technician_notes'] ?? null,
            'actual_causes' => $data['actual_causes'] ?? null,
        ]);

        return $outcome->fresh();
    }

    private function shouldFeedAiDataset(Order $order, Company $company): bool
    {
        $plan = $company->subscription?->plan ?? 'starter';

        // Starter siempre alimenta el dataset de IA,
        // independientemente de si se usó diagnóstico IA en la orden.
        if ($plan === 'starter') {
            return true;
        }

        // Pro y Enterprise solo alimentan cuando la orden tuvo diagnóstico IA.
        // Esto garantiza que el dato de entrenamiento siempre tenga
        // un diagnóstico IA contra el cual comparar el resultado real.
        return (bool) $order->ai_diagnosed_at;
    }

    private function resolveSingleOrder(BillingDocument $doc): ?Order
    {
        $orders = $doc->items
            ->filter(fn ($item) => $item->item_kind === 'service' && $item->order)
            ->map(fn ($item) => $item->order)
            ->unique('id')
            ->values();

        if ($orders->count() !== 1) {
            return null;
        }

        /** @var Order $order */
        $order = $orders->first();

        return $order;
    }

    private function resolveAiEstimatedCost(Order $order): ?float
    {
        $diagnostic = $order->latestDiagnostic;
        if (! $diagnostic) {
            return null;
        }

        return (float) $diagnostic->replacement_total_cost;
    }
}
