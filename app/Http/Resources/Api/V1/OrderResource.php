<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'equipment_id' => $this->equipment_id,
            'technician_profile_id' => $this->technician_profile_id,
            'technician' => $this->technician,
            'status' => $this->status,
            'symptoms' => $this->symptoms,
            'estimated_cost' => (float) $this->estimated_cost,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
            ]),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment?->id,
                'brand' => $this->equipment?->brand,
                'model' => $this->equipment?->model,
                'type' => $this->equipment?->type,
            ]),
            'technician_profile' => $this->whenLoaded('technicianProfile', fn () => [
                'id' => $this->technicianProfile?->id,
                'display_name' => $this->technicianProfile?->display_name,
                'status' => $this->technicianProfile?->status,
            ]),
            'latest_diagnostic' => $this->whenLoaded('latestDiagnostic', fn () => new OrderDiagnosticResource($this->latestDiagnostic)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

