<?php

namespace App\Services;

use App\Models\OrderDiagnostic;
use App\Support\DiagnosticDataNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DiagnosticCaseSearchService
{
    public function findSimilarCases(string $symptoms, array $context = [], int $limit = 10): Collection
    {
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
            ->whereNotNull('normalized_symptoms');

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

        return $query
            ->latest('created_at')
            ->limit(75)
            ->get()
            ->map(function (OrderDiagnostic $diagnostic) use ($keywords, $failureType, $context, $normalizedSymptoms): array {
                $score = $this->calculateSimilarityScore(
                    $diagnostic,
                    $keywords,
                    $failureType,
                    $normalizedSymptoms,
                    $context
                );

                return [
                    'diagnostic' => $diagnostic,
                    'similarity_score' => $score,
                    'matched_keywords' => array_values(array_intersect($keywords, $diagnostic->symptom_keywords ?? [])),
                ];
            })
            ->filter(static fn (array $result): bool => $result['similarity_score'] > 0)
            ->sortByDesc('similarity_score')
            ->take($limit)
            ->values();
    }

    /**
     * @param  array<int, string>  $keywords
     * @param  array<string, mixed>  $context
     */
    private function calculateSimilarityScore(
        OrderDiagnostic $diagnostic,
        array $keywords,
        ?string $failureType,
        ?string $normalizedSymptoms,
        array $context
    ): int {
        $score = 0;
        $matchedKeywords = array_intersect($keywords, $diagnostic->symptom_keywords ?? []);
        $score += count($matchedKeywords) * 18;

        if ($failureType !== null && $diagnostic->failure_type === $failureType) {
            $score += 30;
        }

        if (! empty($context['equipment_type'])
            && $diagnostic->equipment_type === DiagnosticDataNormalizer::normalizeText((string) $context['equipment_type'])) {
            $score += 12;
        }

        if (! empty($context['customer_id']) && (int) $diagnostic->customer_id === (int) $context['customer_id']) {
            $score += 10;
        }

        if ($normalizedSymptoms !== null && $diagnostic->normalized_symptoms !== null) {
            similar_text($normalizedSymptoms, $diagnostic->normalized_symptoms, $percent);
            $score += (int) round(min($percent, 40));
        }

        return $score;
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
            }

            foreach ($keywords as $keyword) {
                $builder->orWhere('normalized_symptoms', 'like', '%'.$keyword.'%');
                $builder->orWhereJsonContains('symptom_keywords', $keyword);
            }
        });
    }
}
