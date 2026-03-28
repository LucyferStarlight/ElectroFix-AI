<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Order;
use App\Models\OrderRepairOutcome;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ConfirmDiagnosisAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Order $order, OrderRepairOutcome $outcome, array $payload): array
    {
        $aiDiagnosis = (array) ($outcome->ai_diagnosis ?? []);
        if ($aiDiagnosis === []) {
            $latestDiagnostic = $order->latestDiagnostic()->first();

            $aiDiagnosis = [
                'summary' => $latestDiagnostic?->diagnostic_summary,
                'failure_type' => $latestDiagnostic?->failure_type,
                'possible_causes' => $latestDiagnostic?->possible_causes ?? [],
                'recommended_actions' => $latestDiagnostic?->recommended_actions ?? [],
                'confidence_score' => $latestDiagnostic?->confidence_score !== null
                    ? (float) $latestDiagnostic->confidence_score
                    : null,
            ];
        }

        $actualCauses = Arr::wrap($payload['actual_causes'] ?? []);
        $realDiagnosis = (array) ($payload['real_diagnosis'] ?? []);

        if ($realDiagnosis === []) {
            $realDiagnosis = [
                'diagnostic_accuracy' => $payload['diagnostic_accuracy'] ?? null,
                'technician_notes' => $payload['technician_notes'] ?? null,
                'actual_causes' => $actualCauses,
            ];
        }

        $comparison = $this->calculateComparison($aiDiagnosis, $actualCauses);
        $realDiagnosis['ai_comparison'] = $comparison;

        $repairApplied = (string) ($payload['repair_applied'] ?? '');
        if (trim($repairApplied) === '') {
            $repairApplied = (string) ($payload['technician_notes'] ?? $outcome->work_performed);
        }

        return [
            'ai_diagnosis' => $aiDiagnosis,
            'real_diagnosis' => $realDiagnosis,
            'repair_applied' => trim($repairApplied) !== '' ? trim($repairApplied) : null,
            'confidence_score' => $aiDiagnosis['confidence_score'] ?? $outcome->confidence_score,
            'validated' => (bool) ($payload['validated'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $aiDiagnosis
     * @param  array<int, string>  $actualCauses
     * @return array<string, mixed>
     */
    private function calculateComparison(array $aiDiagnosis, array $actualCauses): array
    {
        $failureType = Str::of((string) ($aiDiagnosis['failure_type'] ?? ''))
            ->lower()
            ->trim()
            ->value();

        if ($failureType === '' || $actualCauses === []) {
            return [
                'match' => false,
                'score' => 0,
            ];
        }

        $normalizedCauses = collect($actualCauses)
            ->map(static fn ($cause): string => Str::of((string) $cause)->lower()->trim()->value())
            ->filter()
            ->values();

        $exactMatch = $normalizedCauses->contains($failureType);
        $partialMatch = ! $exactMatch
            && $normalizedCauses->contains(
                static fn (string $cause): bool => str_contains($cause, $failureType)
                    || str_contains($failureType, $cause)
            );

        return [
            'match' => $exactMatch || $partialMatch,
            'score' => $exactMatch ? 100 : ($partialMatch ? 60 : 0),
        ];
    }
}
