<?php

namespace App\Services;

use App\Models\OrderDiagnostic;
use App\Models\OrderRepairOutcome;
use Illuminate\Support\Collection;

class DiagnosticInsightsService
{
    public function getFrequentFailures(?int $companyId = null, int $limit = 10): Collection
    {
        return OrderDiagnostic::query()
            ->selectRaw('failure_type, COUNT(*) as cases_count')
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->whereNotNull('failure_type')
            ->groupBy('failure_type')
            ->orderByDesc('cases_count')
            ->limit($limit)
            ->get()
            ->map(fn (OrderDiagnostic $diagnostic): array => [
                'failure_type' => (string) $diagnostic->failure_type,
                'cases_count' => (int) $diagnostic->cases_count,
            ]);
    }

    public function getAverageRepairCostByIssue(?int $companyId = null, int $limit = 10): Collection
    {
        return OrderRepairOutcome::query()
            ->join('orders', 'orders.id', '=', 'order_repair_outcomes.order_id')
            ->join('order_diagnostics', 'order_diagnostics.order_id', '=', 'orders.id')
            ->selectRaw('order_diagnostics.failure_type as failure_type, AVG(order_repair_outcomes.actual_amount_charged) as average_repair_cost, COUNT(DISTINCT order_repair_outcomes.id) as cases_count')
            ->when($companyId !== null, fn ($query) => $query->where('order_repair_outcomes.company_id', $companyId))
            ->whereNotNull('order_diagnostics.failure_type')
            ->whereNotNull('order_repair_outcomes.actual_amount_charged')
            ->groupBy('order_diagnostics.failure_type')
            ->orderByDesc('cases_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                'failure_type' => (string) $row->failure_type,
                'average_repair_cost' => round((float) $row->average_repair_cost, 2),
                'cases_count' => (int) $row->cases_count,
            ]);
    }
}
