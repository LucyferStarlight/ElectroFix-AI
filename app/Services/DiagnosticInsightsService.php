<?php

namespace App\Services;

use Illuminate\Support\Collection;

class DiagnosticInsightsService
{
    public function __construct(private readonly DiagnosticCaseSearchService $diagnosticCaseSearchService)
    {
    }

    public function getFrequentFailures(?int $companyId = null, int $limit = 10): Collection
    {
        return $this->diagnosticCaseSearchService->getFrequentFailures($companyId, $limit);
    }

    public function getAverageRepairCostByIssue(?int $companyId = null, int $limit = 10): Collection
    {
        return $this->diagnosticCaseSearchService->getAverageRepairCostByIssue($companyId, $limit);
    }
}
