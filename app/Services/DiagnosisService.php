<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AiDiagnosticResult;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;

class DiagnosisService
{
    public function __construct(private readonly AiDiagnosticService $aiDiagnosticService) {}

    public function run(Order $order, Company $company, User $actor, string $symptoms): AiDiagnosticResult
    {
        return $this->aiDiagnosticService->diagnose($order, $company, $actor, $symptoms);
    }
}
