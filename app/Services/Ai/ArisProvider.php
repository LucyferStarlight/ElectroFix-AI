<?php

namespace App\Services\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;
use App\Services\Ai\GroqProvider;

class ArisProvider implements AiDiagnosticProvider
{
    public function diagnose(string $symptoms, string $deviceInfo): AiDiagnosticResult
    {
        $arisGroqProvider = new GroqProvider(
            app(AiDiagnosticFormatter::class),
            (string) config('services.groq.aris_model', 'llama-3.3-70b-versatile'),
            GroqProvider::buildSystemPrompt(),
        );

        return $arisGroqProvider->diagnose($symptoms, $deviceInfo);
    }
}
