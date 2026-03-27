<?php

namespace App\Support;

class DiagnosticDataNormalizer
{
    /**
     * @return array<int, string>
     */
    private const STOP_WORDS = [
        'a',
        'al',
        'con',
        'de',
        'del',
        'el',
        'en',
        'es',
        'esta',
        'este',
        'hay',
        'la',
        'las',
        'lo',
        'los',
        'no',
        'por',
        'que',
        'se',
        'sin',
        'su',
        'sus',
        'un',
        'una',
        'unos',
        'unas',
        'y',
    ];

    /**
     * @param  array<int, string>  $possibleCauses
     */
    public static function normalizeDiagnosticMetadata(
        ?string $symptoms,
        array $possibleCauses = [],
        ?string $equipmentType = null
    ): array {
        $normalizedSymptoms = self::normalizeText($symptoms);
        $keywords = self::extractKeywords($symptoms, $possibleCauses, $equipmentType);
        $failureType = self::inferFailureType($normalizedSymptoms, $possibleCauses);

        return [
            'normalized_symptoms' => $normalizedSymptoms,
            'symptom_keywords' => $keywords,
            'failure_type' => $failureType,
            'diagnostic_signature' => self::buildSignature($equipmentType, $failureType, $keywords),
        ];
    }

    public static function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        $value = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $value
        );

        $value = preg_replace('/[^a-z0-9\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param  array<int, string>  $possibleCauses
     * @return array<int, string>
     */
    public static function extractKeywords(
        ?string $symptoms,
        array $possibleCauses = [],
        ?string $equipmentType = null
    ): array {
        $source = collect([
            self::normalizeText($symptoms),
            self::normalizeText($equipmentType),
            ...array_map(static fn ($cause) => self::normalizeText((string) $cause), $possibleCauses),
        ])
            ->filter()
            ->implode(' ');

        $tokens = preg_split('/\s+/u', $source) ?: [];

        return collect($tokens)
            ->map(static fn ($token) => trim((string) $token))
            ->filter(static fn ($token) => mb_strlen($token) >= 3)
            ->reject(static fn ($token) => in_array($token, self::STOP_WORDS, true))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $possibleCauses
     */
    public static function inferFailureType(?string $normalizedSymptoms, array $possibleCauses = []): ?string
    {
        $catalog = [
            'power_failure' => ['no enciende', 'no prende', 'sin energia', 'sin corriente', 'no arranca'],
            'cooling_failure' => ['no enfria', 'no congela', 'temperatura alta', 'no enfria bien'],
            'water_leak' => ['fuga', 'gotea', 'pierde agua', 'derrame', 'filtracion'],
            'noise_vibration' => ['ruido', 'vibracion', 'golpeteo', 'zumbido'],
            'heating_failure' => ['no calienta', 'no calienta bien', 'temperatura baja'],
            'drain_failure' => ['no drena', 'no desagua', 'drenaje', 'atasco'],
            'control_board_failure' => ['tarjeta', 'placa', 'control', 'panel', 'electronica'],
        ];

        $haystack = collect([$normalizedSymptoms, ...array_map(
            static fn ($cause) => self::normalizeText((string) $cause),
            $possibleCauses
        )])
            ->filter()
            ->implode(' ');

        if ($haystack === '') {
            return null;
        }

        foreach ($catalog as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($haystack, $pattern)) {
                    return $type;
                }
            }
        }

        return 'general_failure';
    }

    /**
     * @param  array<int, string>  $keywords
     */
    public static function buildSignature(?string $equipmentType, ?string $failureType, array $keywords): ?string
    {
        $parts = array_filter([
            self::normalizeText($equipmentType),
            $failureType,
            implode('-', array_slice($keywords, 0, 6)),
        ]);

        if ($parts === []) {
            return null;
        }

        return implode('|', $parts);
    }
}
