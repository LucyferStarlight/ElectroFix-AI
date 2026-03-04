<?php

namespace App\Services;

class AiDiagnosticService
{
    public function analyze(string $type, string $brand, ?string $model, string $symptoms): array
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
            'cost_suggestion' => [
                'repair_labor_cost' => round($repairLaborCost, 2),
                'replacement_parts_cost' => round($replacementPartsCost, 2),
                'replacement_total_cost' => $replacementTotalCost,
            ],
        ];
    }
}
