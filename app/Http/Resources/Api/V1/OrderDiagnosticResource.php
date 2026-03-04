<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OrderDiagnostic */
class OrderDiagnosticResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'source' => $this->source,
            'symptoms_snapshot' => $this->symptoms_snapshot,
            'diagnostic_summary' => $this->diagnostic_summary,
            'possible_causes' => $this->possible_causes ?? [],
            'recommended_actions' => $this->recommended_actions ?? [],
            'requires_parts_replacement' => (bool) $this->requires_parts_replacement,
            'cost_suggestion' => [
                'repair_labor_cost' => (float) $this->repair_labor_cost,
                'replacement_parts_cost' => (float) $this->replacement_parts_cost,
                'replacement_total_cost' => (float) $this->replacement_total_cost,
            ],
            'estimated_time' => $this->estimated_time,
            'confidence_score' => (float) ($this->confidence_score ?? 0),
            'tokens' => [
                'prompt' => (int) $this->prompt_tokens,
                'completion' => (int) $this->completion_tokens,
                'total' => (int) $this->total_tokens,
            ],
            'provider' => $this->provider,
            'model' => $this->model,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

