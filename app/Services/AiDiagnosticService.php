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

        if (str_contains($text, 'no enciende') || str_contains($text, 'enciende')) {
            $causes[] = 'Falla en fuente de alimentación o tarjeta principal.';
            $parts[] = 'Tarjeta electrónica';
            $parts[] = 'Fusible térmico';
            $time = '3-5 horas';
            $advice = 'Verifica voltajes de entrada/salida antes de reemplazar módulos.';
        }

        if (str_contains($text, 'ruido') || str_contains($text, 'vibr')) {
            $causes[] = 'Desgaste de rodamientos o desbalance mecánico.';
            $parts[] = 'Rodamientos';
            $parts[] = 'Soportes antivibración';
            $time = '2-3 horas';
        }

        if (str_contains($text, 'fuga') || str_contains($text, 'agua')) {
            $causes[] = 'Deterioro en sellos o mangueras.';
            $parts[] = 'Kit de sellos';
            $parts[] = 'Manguera de drenaje';
            $time = '1-2 horas';
        }

        if (empty($causes)) {
            $causes[] = 'Posible combinación de fallo electrónico y desgaste por uso.';
            $parts[] = 'Kit de diagnóstico estándar';
        }

        return [
            'equipment' => trim($brand.' '.$type.' '.($model ?? '')),
            'potential_causes' => array_values(array_unique($causes)),
            'estimated_time' => $time,
            'suggested_parts' => array_values(array_unique($parts)),
            'technical_advice' => $advice,
        ];
    }
}
