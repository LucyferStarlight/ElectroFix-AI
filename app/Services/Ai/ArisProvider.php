<?php

namespace App\Services\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;

class ArisProvider implements AiDiagnosticProvider
{
    public function __construct(private readonly GroqProvider $groqProvider)
    {
    }

    public function diagnose(string $symptoms, string $deviceInfo): AiDiagnosticResult
    {
        $previousModel = config('services.groq.model');
        $previousPrompt = config('services.groq.system_prompt');

        config([
            'services.groq.model' => config('services.groq.aris_model', 'llama-3.3-70b-versatile'),
            'services.groq.system_prompt' => GroqProvider::buildSystemPrompt(),
        ]);

        try {
            return $this->groqProvider->diagnose($symptoms, $deviceInfo);
        } finally {
            config([
                'services.groq.model' => $previousModel,
                'services.groq.system_prompt' => $previousPrompt,
            ]);
        }
    }
}
