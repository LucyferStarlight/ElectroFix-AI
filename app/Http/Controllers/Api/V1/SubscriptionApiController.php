<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionChangeRequest;
use App\Services\CompanySubscriptionService;
use App\Services\PlanCatalogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CompanySubscriptionService $companySubscriptionService,
        private readonly PlanCatalogService $planCatalogService
    ) {
    }

    public function show(Request $request)
    {
        $this->assertNoCrossCompanyInput($request);
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $pending = SubscriptionChangeRequest::query()
            ->where('company_id', $company->id)
            ->where('status', 'pending')
            ->orderByDesc('effective_at')
            ->first();

        return $this->success([
            'subscription' => $company->subscription,
            'pending_change' => $pending,
        ]);
    }

    public function checkout(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'No autorizado.');

        $data = $request->validate([
            'company_id' => ['prohibited'],
            'plan' => ['required', Rule::in(['starter', 'pro', 'enterprise'])],
            'billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
            'payment_method' => ['required', 'string', 'max:255'],
        ]);

        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $subscription = $this->companySubscriptionService->checkout(
            $company,
            $data['plan'],
            $data['billing_period'],
            $data['payment_method']
        );

        return $this->success($subscription, status: 201);
    }

    public function change(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'No autorizado.');

        $data = $request->validate([
            'company_id' => ['prohibited'],
            'plan' => ['required', Rule::in(['starter', 'pro', 'enterprise'])],
            'billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
        ]);

        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $result = $this->companySubscriptionService->requestChange(
            $company,
            $data['plan'],
            $data['billing_period'],
            $request->user()
        );

        return $this->success($result);
    }

    public function cancel(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'No autorizado.');
        $this->assertNoCrossCompanyInput($request);

        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $subscription = $this->companySubscriptionService->cancelAtPeriodEnd($company);

        return $this->success($subscription);
    }

    public function plans()
    {
        return $this->success([
            'plans' => $this->planCatalogService->publicPlans(),
        ]);
    }

    private function assertNoCrossCompanyInput(Request $request): void
    {
        if (! $request->has('company_id')) {
            return;
        }

        $companyId = (int) $request->input('company_id');
        if ($companyId !== (int) $request->user()?->company_id) {
            abort(403, 'No puedes operar la suscripción de otra empresa.');
        }
    }
}
