<?php

namespace App\Contracts;

use App\DTOs\AiDiagnosticResult;

interface AiDiagnosticProvider
{
    public function diagnose(string $symptoms, string $deviceInfo): AiDiagnosticResult;
}
