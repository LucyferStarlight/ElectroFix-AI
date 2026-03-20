<?php

namespace App\Services\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;
use App\Services\Exceptions\ArisNotAvailableException;

class ArisProvider implements AiDiagnosticProvider
{
    public function diagnose(string $symptoms, string $deviceInfo): AiDiagnosticResult
    {
        throw new ArisNotAvailableException('ARIS Repair no está disponible aún en este plan.');
    }
}
