<?php

namespace App\Services\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;

class ArisProvider implements AiDiagnosticProvider
{
    public function __construct(
        private readonly AiDiagnosticFormatter $formatter
    ) {}

    public static function buildSystemPrompt(): string
    {
        return 'Eres ARIS, sistema experto de diagnóstico de electrodomésticos de ElectroFix. '
            .'Responde únicamente en es-MX. Sé más específico y técnico que un asistente genérico. '
            .'Devuelve SOLO JSON válido con el schema definido.';
    }

    public function diagnose(string $symptoms, string $deviceInfo): AiDiagnosticResult
    {
        $arisGroqProvider = new GroqProvider(
            $this->formatter,
            (string) config('services.groq.aris_model', 'llama-3.3-70b-versatile'),
            self::buildSystemPrompt(),
        );

        return $arisGroqProvider->diagnose($symptoms, $deviceInfo);
    }
}
