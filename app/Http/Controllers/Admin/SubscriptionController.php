<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionChangeRequest;
use App\Http\Requests\StoreSubscriptionCheckoutRequest;
use App\Models\SubscriptionChangeRequest;
use App\Services\CompanySubscriptionService;
use App\Services\PlanCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly CompanySubscriptionService $companySubscriptionService,
        private readonly PlanCatalogService $planCatalogService
    ) {
    }

    public function edit(Request $request)
    {
        $this->assertNoCrossCompanyInput($request);
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $subscription = $company->subscription;
        $pendingChange = SubscriptionChangeRequest::query()
            ->where('company_id', $company->id)
            ->where('status', 'pending')
            ->orderByDesc('effective_at')
            ->first();

        return view('admin.subscription.edit', [
            'currentPage' => 'admin-subscription',
            'subscription' => $subscription,
            'plans' => $this->planCatalogService->publicPlans(),
            'pendingChange' => $pendingChange,
        ]);
    }

    public function checkout(StoreSubscriptionCheckoutRequest $request): RedirectResponse
    {
        $this->assertNoCrossCompanyInput($request);
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $this->companySubscriptionService->checkout(
            $company,
            $request->string('plan')->toString(),
            $request->string('billing_period')->toString(),
            $request->string('payment_method')->toString()
        );

        return back()->with('success', 'Suscripción creada correctamente en Stripe.');
    }

    public function change(StoreSubscriptionChangeRequest $request): RedirectResponse
    {
        $this->assertNoCrossCompanyInput($request);
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $result = $this->companySubscriptionService->requestChange(
            $company,
            $request->string('plan')->toString(),
            $request->string('billing_period')->toString(),
            $request->user()
        );

        if ($result['mode'] === 'deferred') {
            return back()->with('success', 'Cambio programado para el fin del ciclo actual.');
        }

        return back()->with('success', 'Cambio de suscripción aplicado.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->assertNoCrossCompanyInput($request);
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $this->companySubscriptionService->cancelAtPeriodEnd($company);

        return back()->with('success', 'La suscripción fue marcada para cancelarse al final del periodo pagado.');
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
