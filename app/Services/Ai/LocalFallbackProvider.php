<?php

namespace App\Services\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;

class LocalFallbackProvider implements AiDiagnosticProvider
{
    public function __construct(private readonly AiDiagnosticFormatter $formatter)
    {
    }

    public function diagnose(string $symptoms, string $deviceInfo): AiDiagnosticResult
    {
        $payload = $this->formatter->fallbackPayload($deviceInfo, $symptoms);
        $payload['provider'] = 'local';
        $payload['model'] = 'heuristic-v2';
        $payload['tokens_used'] = 0;

        return AiDiagnosticResult::fromPayload($payload, 'local', 0);
    }
}
