<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BillingDocument */
class BillingDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'order_id' => $this->order_id,
            'document_number' => $this->document_number,
            'document_type' => $this->document_type,
            'version' => $this->version,
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'source' => $this->source,
            'customer_mode' => $this->customer_mode,
            'customer_display_name' => $this->customerDisplayName(),
            'tax_mode' => $this->tax_mode,
            'vat_percentage' => (float) $this->vat_percentage,
            'subtotal' => (float) $this->subtotal,
            'vat_amount' => (float) $this->vat_amount,
            'total' => (float) $this->total,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'item_kind' => $item->item_kind,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_subtotal' => (float) $item->line_subtotal,
                    'line_vat' => (float) $item->line_vat,
                    'line_total' => (float) $item->line_total,
                    'order_id' => $item->order_id,
                    'inventory_item_id' => $item->inventory_item_id,
                ])->values();
            }),
        ];
    }
}
