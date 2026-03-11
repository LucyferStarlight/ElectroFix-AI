<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithCompanyScope;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardMetricsApiController extends Controller
{
    use ApiResponse;
    use InteractsWithCompanyScope;

    public function __construct(private readonly DashboardMetricsService $metricsService)
    {
    }

    public function show(Request $request)
    {
        $companyId = $this->scopedCompanyId($request) ?: $request->integer('company_id');

        if (! $companyId) {
            abort(422, 'Debes indicar company_id para consultar métricas.');
        }

        $this->assertCompanyAccess($request, $companyId);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfDay();

        $company = Company::query()->findOrFail($companyId);
        $metrics = $this->metricsService->companyMetrics($company, $from, $to);

        return $this->success($metrics, [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'company_id' => $company->id,
        ]);
    }
}
