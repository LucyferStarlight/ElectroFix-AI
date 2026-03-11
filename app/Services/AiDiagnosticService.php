<?php

namespace App\Services;

use App\Application\AI\Contracts\AiProviderInterface;
use App\Application\AI\DTO\AiResponse;
use Illuminate\Support\Facades\Log;

class AiDiagnosticService
{
    public function __construct(private readonly AiProviderInterface $provider)
    {
    }

    public function analyze(string $type, string $brand, ?string $model, string $symptoms, array $meta = []): array
    {
        $startedAt = microtime(true);
        $providerResponse = $this->provider->generateSolution([
            'type' => $type,
            'brand' => $brand,
            'model' => $model,
            'symptoms' => $symptoms,
            'language' => 'es-MX',
        ]);

        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($providerResponse->success) {
            $normalized = $this->normalizeProviderContent(
                $providerResponse,
                $type,
                $brand,
                $model,
                $symptoms
            );

            Log::channel('ai')->info('AI diagnostic generated', [
                'company_id' => $meta['company_id'] ?? null,
                'order_id' => $meta['order_id'] ?? null,
                'provider' => $this->provider->getProviderName(),
                'success' => true,
                'elapsed_ms' => $elapsedMs,
                'tokens_used' => $providerResponse->tokensUsed,
            ]);

            return $normalized;
        }

        Log::channel('ai')->warning('AI diagnostic provider fallback', [
            'company_id' => $meta['company_id'] ?? null,
            'order_id' => $meta['order_id'] ?? null,
            'provider' => $this->provider->getProviderName(),
            'success' => false,
            'elapsed_ms' => $elapsedMs,
            'error_code' => $providerResponse->errorCode,
            'error_message' => $providerResponse->errorMessage,
        ]);

        $fallback = $this->fallbackHeuristic($type, $brand, $model, $symptoms);
        $fallback['success'] = true;
        $fallback['error_code'] = $providerResponse->errorCode ?? 'provider_error';
        $fallback['error_message'] = $providerResponse->errorMessage ?? 'No fue posible consultar el proveedor IA externo.';
        $fallback['provider'] = $this->provider->getProviderName().'_fallback';

        return $fallback;
    }

    private function normalizeProviderContent(
        AiResponse $providerResponse,
        string $type,
        string $brand,
        ?string $model,
        string $symptoms
    ): array {
        $base = $this->fallbackHeuristic($type, $brand, $model, $symptoms);
        $content = $providerResponse->content;
        $costSuggestion = is_array($content['cost_suggestion'] ?? null) ? $content['cost_suggestion'] : [];

        $requiresParts = (bool) ($content['requires_parts_replacement'] ?? false);
        $labor = (float) ($costSuggestion['repair_labor_cost'] ?? $base['cost_suggestion']['repair_labor_cost']);
        $parts = $requiresParts
            ? (float) ($costSuggestion['replacement_parts_cost'] ?? 0)
            : 0.0;
        $replacementTotal = $requiresParts
            ? (float) ($costSuggestion['replacement_total_cost'] ?? ($labor + $parts))
            : 0.0;

        return [
            'equipment' => trim($brand.' '.$type.' '.($model ?? '')),
            'diagnostic_summary' => (string) ($content['diagnostic_summary'] ?? $base['diagnostic_summary']),
            'potential_causes' => $this->normalizeStringArray($content['possible_causes'] ?? $base['possible_causes']),
            'possible_causes' => $this->normalizeStringArray($content['possible_causes'] ?? $base['possible_causes']),
            'recommended_actions' => $this->normalizeStringArray($content['recommended_actions'] ?? $base['recommended_actions']),
            'estimated_time' => (string) ($content['estimated_time'] ?? $base['estimated_time']),
            'suggested_parts' => $this->normalizeStringArray($content['suggested_parts'] ?? $base['suggested_parts']),
            'technical_advice' => (string) ($content['technical_advice'] ?? $base['technical_advice']),
            'requires_parts_replacement' => $requiresParts,
            'confidence_score' => (float) ($content['confidence_score'] ?? $base['confidence_score']),
            'provider' => $this->provider->getProviderName(),
            'model' => (string) config('services.gemini.model', 'gemini-1.5-flash'),
            'success' => true,
            'error_code' => null,
            'error_message' => null,
            'tokens_used' => $providerResponse->tokensUsed,
            'cost_suggestion' => [
                'repair_labor_cost' => round($labor, 2),
                'replacement_parts_cost' => round($parts, 2),
                'replacement_total_cost' => round($replacementTotal, 2),
            ],
        ];
    }

    private function normalizeStringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $value
        ), static fn (string $item): bool => $item !== ''));

        return array_values(array_unique($normalized));
    }

    private function fallbackHeuristic(string $type, string $brand, ?string $model, string $symptoms): array
    {
        $text = mb_strtolower($symptoms);

        $causes = [];
        $parts = [];
        $time = '2-4 horas';
        $advice = 'Realiza primero una inspección eléctrica básica y confirma continuidad de componentes críticos.';
        $repairLaborCost = 500.00;

        if (str_contains($text, 'no enciende') || str_contains($text, 'enciende')) {
            $causes[] = 'Falla en fuente de alimentación o tarjeta principal.';
            $parts[] = 'Tarjeta electrónica';
            $parts[] = 'Fusible térmico';
            $time = '3-5 horas';
            $advice = 'Verifica voltajes de entrada/salida antes de reemplazar módulos.';
            $repairLaborCost = 850.00;
        }

        if (str_contains($text, 'ruido') || str_contains($text, 'vibr')) {
            $causes[] = 'Desgaste de rodamientos o desbalance mecánico.';
            $parts[] = 'Rodamientos';
            $parts[] = 'Soportes antivibración';
            $time = '2-3 horas';
            $repairLaborCost = max($repairLaborCost, 700.00);
        }

        if (str_contains($text, 'fuga') || str_contains($text, 'agua')) {
            $causes[] = 'Deterioro en sellos o mangueras.';
            $parts[] = 'Kit de sellos';
            $parts[] = 'Manguera de drenaje';
            $time = '1-2 horas';
            $repairLaborCost = max($repairLaborCost, 600.00);
        }

        if (empty($causes)) {
            $causes[] = 'Posible combinación de fallo electrónico y desgaste por uso.';
            $advice = 'Se recomienda mantenimiento preventivo, limpieza técnica y recalibración antes de cambiar piezas.';
            $repairLaborCost = 450.00;
        }

        $parts = array_values(array_unique($parts));
        $requiresPartsReplacement = ! empty($parts);
        $replacementPartsCost = $requiresPartsReplacement ? count($parts) * 320.00 : 0.00;
        $replacementTotalCost = $requiresPartsReplacement
            ? round($replacementPartsCost + $repairLaborCost, 2)
            : 0.00;

        return [
            'equipment' => trim($brand.' '.$type.' '.($model ?? '')),
            'diagnostic_summary' => $requiresPartsReplacement
                ? 'Se detecta escenario de reparación con posible reemplazo de componentes.'
                : 'Se detecta escenario de reparación sin necesidad inicial de reemplazo de piezas.',
            'potential_causes' => array_values(array_unique($causes)),
            'possible_causes' => array_values(array_unique($causes)),
            'recommended_actions' => [
                'Inspección eléctrica inicial',
                $requiresPartsReplacement ? 'Validar estado de piezas críticas para reemplazo' : 'Ajuste y calibración de componentes actuales',
                'Prueba funcional completa antes de entrega',
            ],
            'estimated_time' => $time,
            'suggested_parts' => $parts,
            'technical_advice' => $advice,
            'requires_parts_replacement' => $requiresPartsReplacement,
            'confidence_score' => $requiresPartsReplacement ? 82.5 : 74.0,
            'provider' => 'local_stub',
            'model' => 'heuristic-v2',
            'success' => true,
            'error_code' => null,
            'error_message' => null,
            'tokens_used' => null,
            'cost_suggestion' => [
                'repair_labor_cost' => round($repairLaborCost, 2),
                'replacement_parts_cost' => round($replacementPartsCost, 2),
                'replacement_total_cost' => $replacementTotalCost,
            ],
        ];
    }
}
