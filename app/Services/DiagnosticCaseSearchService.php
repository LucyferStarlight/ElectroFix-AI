<?php

namespace App\Services;

use App\Models\OrderDiagnostic;
use App\Models\OrderRepairOutcome;
use App\Support\DiagnosticDataNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DiagnosticCaseSearchService
{
    public function findSimilarCases(string $symptoms, int|array|null $equipmentId = null, int $limit = 10): Collection
    {
        $context = is_array($equipmentId) ? $equipmentId : [];

        if (is_int($equipmentId)) {
            $context['equipment_id'] = $equipmentId;
        }

        $metadata = DiagnosticDataNormalizer::normalizeDiagnosticMetadata(
            $symptoms,
            [],
            $context['equipment_type'] ?? null
        );

        $keywords = $metadata['symptom_keywords'];
        $normalizedSymptoms = $metadata['normalized_symptoms'];
        $failureType = $metadata['failure_type'];

        $query = OrderDiagnostic::query()
            ->with(['order.customer', 'order.equipment'])
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNotNull('normalized_symptoms')
                    ->orWhereNotNull('symptoms_snapshot');
            });

        if (isset($context['company_id'])) {
            $query->where('company_id', $context['company_id']);
        }

        if (isset($context['customer_id'])) {
            $query->where('customer_id', $context['customer_id']);
        }

        if (isset($context['equipment_id'])) {
            $query->where('equipment_id', $context['equipment_id']);
        } elseif (! empty($context['equipment_type'])) {
            $query->where('equipment_type', DiagnosticDataNormalizer::normalizeText((string) $context['equipment_type']));
        }

        $this->applySimilarityPrefilter($query, $normalizedSymptoms, $keywords, $failureType);

        $results = $query
            ->latest('created_at')
            ->limit(120)
            ->get()
            ->map(function (OrderDiagnostic $diagnostic) use ($keywords, $failureType, $context, $normalizedSymptoms): array {
                $similarityPercentage = $this->calculateSimilarityPercentage(
                    $diagnostic,
                    $keywords,
                    $failureType,
                    $normalizedSymptoms,
                    $context
                );
                $isExactEquipmentMatch = isset($context['equipment_id'])
                    && (int) $diagnostic->equipment_id === (int) $context['equipment_id'];

                return [
                    'diagnostic' => $diagnostic,
                    'similarity_score' => (int) round($similarityPercentage),
                    'similarity_percentage' => $similarityPercentage,
                    'matched_keywords' => array_values(array_intersect($keywords, $diagnostic->symptom_keywords ?? [])),
                    'is_exact_equipment_match' => $isExactEquipmentMatch,
                    'is_failure_type_match' => $failureType !== null && $diagnostic->failure_type === $failureType,
                ];
            })
            ->filter(static fn (array $result): bool => $result['similarity_percentage'] > 0)
            ->sortBy([
                ['similarity_percentage', 'desc'],
                ['is_exact_equipment_match', 'desc'],
                [static fn (array $row): int => (int) $row['diagnostic']->id, 'desc'],
            ])
            ->take($limit)
            ->values();

        return $results->map(static function (array $row, int $index): array {
            $row['relevance_rank'] = $index + 1;

            return $row;
        });
    }

    public function getFrequentFailures(?int $companyId = null, int $limit = 10): Collection
    {
        $limit = max(1, min($limit, 100));

        $baseQuery = OrderDiagnostic::query()
            ->when($companyId !== null, fn (Builder $query) => $query->where('company_id', $companyId))
            ->whereNotNull('failure_type');

        $totalCases = (clone $baseQuery)->count();

        return $baseQuery
            ->selectRaw('failure_type, COUNT(*) as cases_count, AVG(confidence_score) as average_confidence')
            ->groupBy('failure_type')
            ->orderByDesc('cases_count')
            ->orderByDesc('average_confidence')
            ->limit($limit)
            ->get()
            ->map(static function (OrderDiagnostic $diagnostic) use ($totalCases): array {
                $casesCount = (int) $diagnostic->cases_count;
                $sharePercentage = $totalCases > 0
                    ? round(($casesCount / $totalCases) * 100, 2)
                    : 0.0;

                return [
                    'failure_type' => (string) $diagnostic->failure_type,
                    'cases_count' => $casesCount,
                    'share_percentage' => $sharePercentage,
                    'average_confidence' => $diagnostic->average_confidence !== null
                        ? round((float) $diagnostic->average_confidence, 2)
                        : null,
                ];
            });
    }

    public function getAverageRepairCostByIssue(?int $companyId = null, int $limit = 10): Collection
    {
        $limit = max(1, min($limit, 100));

        $latestVersionPerOrder = DB::table('order_diagnostics')
            ->selectRaw('order_id, MAX(version) as latest_version')
            ->groupBy('order_id');

        return OrderRepairOutcome::query()
            ->from('order_repair_outcomes as outcomes')
            ->joinSub($latestVersionPerOrder, 'latest_diag', function ($join): void {
                $join->on('latest_diag.order_id', '=', 'outcomes.order_id');
            })
            ->join('order_diagnostics as diagnostics', function ($join): void {
                $join->on('diagnostics.order_id', '=', 'latest_diag.order_id')
                    ->on('diagnostics.version', '=', 'latest_diag.latest_version');
            })
            ->when($companyId !== null, fn ($query) => $query->where('outcomes.company_id', $companyId))
            ->whereNotNull('diagnostics.failure_type')
            ->whereNotNull('outcomes.actual_amount_charged')
            ->selectRaw('
                diagnostics.failure_type as failure_type,
                COUNT(outcomes.id) as cases_count,
                AVG(outcomes.actual_amount_charged) as average_repair_cost,
                MIN(outcomes.actual_amount_charged) as min_repair_cost,
                MAX(outcomes.actual_amount_charged) as max_repair_cost
            ')
            ->groupBy('diagnostics.failure_type')
            ->orderByDesc('cases_count')
            ->orderByDesc('average_repair_cost')
            ->limit($limit)
            ->get()
            ->map(static function ($row): array {
                return [
                    'failure_type' => (string) $row->failure_type,
                    'average_repair_cost' => round((float) $row->average_repair_cost, 2),
                    'cases_count' => (int) $row->cases_count,
                    'min_repair_cost' => round((float) $row->min_repair_cost, 2),
                    'max_repair_cost' => round((float) $row->max_repair_cost, 2),
                ];
            });
    }

    /**
     * @param  array<int, string>  $keywords
     * @param  array<string, mixed>  $context
     */
    private function calculateSimilarityPercentage(
        OrderDiagnostic $diagnostic,
        array $keywords,
        ?string $failureType,
        ?string $normalizedSymptoms,
        array $context
    ): float {
        $score = 0.0;
        $targetKeywords = collect($keywords)->unique()->values()->all();
        $sourceKeywords = collect($diagnostic->symptom_keywords ?? [])->unique()->values()->all();
        $keywordIntersection = array_intersect($targetKeywords, $sourceKeywords);
        $keywordUnion = array_unique(array_merge($targetKeywords, $sourceKeywords));
        $keywordScore = count($keywordUnion) > 0
            ? (count($keywordIntersection) / count($keywordUnion)) * 45
            : 0.0;
        $score += $keywordScore;

        if ($normalizedSymptoms !== null && $diagnostic->normalized_symptoms !== null) {
            similar_text($normalizedSymptoms, $diagnostic->normalized_symptoms, $textPercent);
            $score += min(30.0, round(($textPercent / 100) * 30, 2));
        }

        if ($failureType !== null && $diagnostic->failure_type === $failureType) {
            $score += 10.0;
        }

        if (isset($context['equipment_id']) && (int) $context['equipment_id'] > 0) {
            if ((int) $diagnostic->equipment_id === (int) $context['equipment_id']) {
                $score += 15.0;
            }
        } elseif (! empty($context['equipment_type'])
            && $diagnostic->equipment_type === DiagnosticDataNormalizer::normalizeText((string) $context['equipment_type'])) {
            $score += 10.0;
        }

        if (! empty($context['customer_id']) && (int) $diagnostic->customer_id === (int) $context['customer_id']) {
            $score += 5.0;
        }

        return round(min(100.0, $score), 2);
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function applySimilarityPrefilter(
        Builder $query,
        ?string $normalizedSymptoms,
        array $keywords,
        ?string $failureType
    ): void {
        $query->where(function (Builder $builder) use ($normalizedSymptoms, $keywords, $failureType): void {
            if ($failureType !== null) {
                $builder->orWhere('failure_type', $failureType);
            }

            if ($normalizedSymptoms !== null) {
                $builder->orWhere('normalized_symptoms', 'like', '%'.$normalizedSymptoms.'%');
                $builder->orWhere('symptoms_snapshot', 'like', '%'.$normalizedSymptoms.'%');
            }

            foreach ($keywords as $keyword) {
                $builder->orWhere('normalized_symptoms', 'like', '%'.$keyword.'%');
                $builder->orWhere('symptoms_snapshot', 'like', '%'.$keyword.'%');
                $builder->orWhereJsonContains('symptom_keywords', $keyword);
            }
        });
    }
}
