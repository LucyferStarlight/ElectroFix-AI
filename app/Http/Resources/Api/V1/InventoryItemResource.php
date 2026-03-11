<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InventoryItem */
class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'internal_code' => $this->internal_code,
            'quantity' => (int) $this->quantity,
            'low_stock_threshold' => (int) $this->low_stock_threshold,
            'is_sale_enabled' => (bool) $this->is_sale_enabled,
            'sale_price' => (float) ($this->sale_price ?? 0),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

