<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDiagnostic;
use App\Models\User;

class OrderDiagnosticService
{
    public function createFromAi(
        Order $order,
        User $actor,
        array $analysis,
        int $promptTokens,
        int $completionTokens,
        string $symptomsSnapshot
    ): OrderDiagnostic {
        $version = (int) OrderDiagnostic::query()
            ->where('order_id', $order->id)
            ->max('version') + 1;

        return OrderDiagnostic::query()->create([
            'company_id' => $order->company_id,
            'order_id' => $order->id,
            'version' => $version,
            'created_by_user_id' => $actor->id,
            'source' => 'ai',
            'symptoms_snapshot' => $symptomsSnapshot,
            'equipment_snapshot' => [
                'type' => $order->equipment?->type,
                'brand' => $order->equipment?->brand,
                'model' => $order->equipment?->model,
                'serial_number' => $order->equipment?->serial_number,
            ],
            'diagnostic_summary' => $analysis['diagnostic_summary'] ?? null,
            'possible_causes' => $analysis['possible_causes'] ?? [],
            'recommended_actions' => $analysis['recommended_actions'] ?? [],
            'requires_parts_replacement' => (bool) ($analysis['requires_parts_replacement'] ?? false),
            'repair_labor_cost' => (float) ($analysis['cost_suggestion']['repair_labor_cost'] ?? 0),
            'replacement_parts_cost' => (float) ($analysis['cost_suggestion']['replacement_parts_cost'] ?? 0),
            'replacement_total_cost' => (float) ($analysis['cost_suggestion']['replacement_total_cost'] ?? 0),
            'estimated_time' => $analysis['estimated_time'] ?? null,
            'confidence_score' => (float) ($analysis['confidence_score'] ?? 0),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
            'provider' => $analysis['provider'] ?? 'groq',
            'model' => $analysis['model'] ?? 'llama-3.1-8b-instant',
        ]);
    }
}
