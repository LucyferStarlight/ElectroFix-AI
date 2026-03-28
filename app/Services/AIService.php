<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;
use App\Services\Exceptions\AiQuotaExceededException;

class AIService
{
    public function __construct(private readonly AiDiagnosticProvider $provider) {}

    public function diagnose(string $symptoms, string $deviceInfo): AiDiagnosticResult
    {
        $sanitizedSymptoms = $this->sanitizeSymptoms($symptoms);

        return $this->provider->diagnose($sanitizedSymptoms, $deviceInfo);
    }

    public function sanitizeSymptoms(string $symptoms): string
    {
        $clean = trim(strip_tags($symptoms));
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        $blockedPatterns = [
            '/\b(ignore|ignora)\b.{0,40}\b(previous|previas?)\b/iu',
            '/\b(system|assistant|developer)\s*:/iu',
            '/```/u',
            '/<\s*script/iu',
        ];

        foreach ($blockedPatterns as $pattern) {
            $clean = preg_replace($pattern, '', $clean) ?? $clean;
        }

        $clean = trim($clean);

        if ($clean === '' || mb_strlen($clean) < 5) {
            throw new AiQuotaExceededException(
                'invalid_symptoms',
                'Los síntomas ingresados no son válidos para diagnóstico.'
            );
        }

        return mb_substr($clean, 0, 600);
    }
}
