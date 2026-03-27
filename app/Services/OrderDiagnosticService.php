<?php

namespace App\Services;

use App\Models\EquipmentEvent;
use App\Models\Order;
use App\Models\OrderDiagnostic;
use App\Models\User;
use App\Support\DiagnosticDataNormalizer;

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

        $metadata = DiagnosticDataNormalizer::normalizeDiagnosticMetadata(
            $symptomsSnapshot,
            $analysis['possible_causes'] ?? [],
            $order->equipment?->type
        );

        $diagnostic = OrderDiagnostic::query()->create([
            'company_id' => $order->company_id,
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'equipment_id' => $order->equipment_id,
            'version' => $version,
            'created_by_user_id' => $actor->id,
            'source' => 'ai',
            'symptoms_snapshot' => $symptomsSnapshot,
            'normalized_symptoms' => $metadata['normalized_symptoms'],
            'symptom_keywords' => $metadata['symptom_keywords'],
            'equipment_snapshot' => [
                'type' => $order->equipment?->type,
                'brand' => $order->equipment?->brand,
                'model' => $order->equipment?->model,
                'serial_number' => $order->equipment?->serial_number,
            ],
            'equipment_type' => DiagnosticDataNormalizer::normalizeText($order->equipment?->type),
            'diagnostic_summary' => $analysis['diagnostic_summary'] ?? null,
            'failure_type' => $metadata['failure_type'],
            'diagnostic_signature' => $metadata['diagnostic_signature'],
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

        EquipmentEvent::query()->create([
            'company_id' => $order->company_id,
            'equipment_id' => $order->equipment_id,
            'order_id' => $order->id,
            'created_by_user_id' => $actor->id,
            'event_type' => 'order.diagnostic.created',
            'title' => 'Diagnóstico técnico registrado',
            'description' => $analysis['diagnostic_summary'] ?? 'Se registró un nuevo diagnóstico técnico.',
            'payload' => [
                'order_diagnostic_id' => $diagnostic->id,
                'version' => $diagnostic->version,
                'source' => $diagnostic->source,
                'failure_type' => $diagnostic->failure_type,
                'symptom_keywords' => $diagnostic->symptom_keywords ?? [],
                'diagnostic_signature' => $diagnostic->diagnostic_signature,
            ],
        ]);

        return $diagnostic;
    }
}
