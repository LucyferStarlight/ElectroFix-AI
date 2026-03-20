<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionChangeRequest;
use App\Http\Requests\StoreSubscriptionCheckoutRequest;
use App\Models\Subscription;
use App\Models\SubscriptionChangeRequest;
use App\Services\CompanySubscriptionService;
use App\Services\PlanCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $hasHadSubscription = Subscription::query()
            ->where('company_id', $company->id)
            ->exists();

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
            'showTrialBadge' => ! $hasHadSubscription,
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

    public function update(Request $request): RedirectResponse
    {
        $this->assertNoCrossCompanyInput($request);
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $data = $request->validate([
            'plan' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in([
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_PAST_DUE,
                Subscription::STATUS_CANCELED,
                Subscription::STATUS_INACTIVE,
            ])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'billing_cycle' => ['nullable', 'string', 'max:20'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $subscription = Subscription::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'plan' => $data['plan'],
                'status' => $data['status'],
                'starts_at' => $data['starts_at'] ?? now()->startOfMonth(),
                'ends_at' => $data['ends_at'] ?? now()->startOfMonth()->addMonth()->endOfMonth(),
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'user_limit' => $data['user_limit'] ?? 10,
            ]
        );

        $subscription->update([
            'plan' => $data['plan'],
            'status' => $data['status'],
            'starts_at' => $data['starts_at'] ?? $subscription->starts_at,
            'ends_at' => $data['ends_at'] ?? $subscription->ends_at,
            'billing_cycle' => $data['billing_cycle'] ?? $subscription->billing_cycle,
            'user_limit' => $data['user_limit'] ?? $subscription->user_limit,
        ]);

        return back()->with('success', 'Suscripción actualizada.');
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
