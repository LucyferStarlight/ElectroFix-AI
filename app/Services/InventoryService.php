<?php

namespace App\Services;

use App\Models\Company;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Notifications\LowStockInventoryNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function createItem(Company $company, User $actor, array $payload): InventoryItem
    {
        return DB::transaction(function () use ($company, $actor, $payload): InventoryItem {
            $item = InventoryItem::query()->create([
                'company_id' => $company->id,
                'name' => $payload['name'],
                'internal_code' => trim(strtoupper($payload['internal_code'])),
                'quantity' => (int) $payload['quantity'],
                'low_stock_threshold' => (int) ($payload['low_stock_threshold'] ?? 5),
                'is_sale_enabled' => (bool) ($payload['is_sale_enabled'] ?? false),
                'sale_price' => ! empty($payload['is_sale_enabled']) ? ($payload['sale_price'] ?? null) : null,
            ]);

            InventoryMovement::query()->create([
                'inventory_item_id' => $item->id,
                'company_id' => $company->id,
                'user_id' => $actor->id,
                'movement_type' => 'addition',
                'quantity' => $item->quantity,
                'stock_before' => 0,
                'stock_after' => $item->quantity,
                'notes' => 'Alta inicial de producto',
            ]);

            $this->notifyLowStockIfNeeded($item);

            return $item;
        });
    }

    public function adjustStock(InventoryItem $item, User $actor, array $payload): InventoryItem
    {
        return DB::transaction(function () use ($item, $actor, $payload): InventoryItem {
            $movementType = $payload['movement_type'];
            $delta = (int) $payload['quantity'];
            $before = $item->quantity;

            $after = $movementType === 'addition'
                ? $before + $delta
                : $before - $delta;

            if ($after < 0) {
                abort(422, 'No puedes retirar más unidades que el stock disponible.');
            }

            $item->update(['quantity' => $after]);

            InventoryMovement::query()->create([
                'inventory_item_id' => $item->id,
                'company_id' => $item->company_id,
                'user_id' => $actor->id,
                'movement_type' => $movementType,
                'quantity' => $delta,
                'stock_before' => $before,
                'stock_after' => $after,
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->notifyLowStockIfNeeded($item->fresh());

            return $item->fresh();
        });
    }

    private function notifyLowStockIfNeeded(InventoryItem $item): void
    {
        if (! $item->isLowStock()) {
            return;
        }

        $usersToNotify = User::query()
            ->where('company_id', $item->company_id)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('role', 'admin')
                    ->orWhere(function ($q): void {
                        $q->where('role', 'worker')
                            ->where('can_access_inventory', true);
                    });
            })
            ->get();

        foreach ($usersToNotify as $user) {
            $alreadyExists = $user->notifications()
                ->where('type', LowStockInventoryNotification::class)
                ->where('data->item_id', $item->id)
                ->where('read_at', null)
                ->exists();

            if (! $alreadyExists) {
                $user->notify(new LowStockInventoryNotification($item));
            }
        }
    }

    public function lowStockItemsForCompany(int $companyId): Collection
    {
        return InventoryItem::query()
            ->where('company_id', $companyId)
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->orderBy('quantity')
            ->get();
    }
}
