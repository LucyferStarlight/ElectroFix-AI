<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Services\PlanCatalogService;
use App\Services\Exceptions\StripeCheckoutException;
use App\Services\TrialPolicyService;
use App\Support\TechnicianStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CompanyRegistrationController extends Controller
{

    public function showForm(PlanCatalogService $planCatalogService, Request $request)
    {
        $featureMap = [
            'starter' => [
                'Hasta 5 técnicos',
                'Hasta 75 órdenes activas',
                'Inventario',
                'Facturación',
                'Gestión de clientes',
                'Estadísticas básicas',
                '10 consultas IA por mes',
            ],
            'pro' => [
                'Hasta 100 técnicos',
                'Hasta 500 órdenes activas',
                'Inventario completo',
                'Facturación integrada',
                'Estadísticas',
                'IA diagnóstica con Groq incluida',
                '75 consultas IA / mes',
            ],
            'enterprise' => [
                'Técnicos ilimitados',
                'Órdenes ilimitadas',
                'Inventario avanzado',
                'Facturación completa',
                'Reportes avanzados',
                'IA diagnóstica con Groq incluida',
                '200 consultas IA / mes',
                'Consultas adicionales disponibles',
            ],
        ];

        $labelMap = [
            'starter' => (string) config('plans.starter.label', 'Básico'),
            'pro' => (string) config('plans.pro.label', 'Profesional'),
            'enterprise' => (string) config('plans.enterprise.label', 'Empresarial'),
        ];

        $plans = [];
        $publicPlans = $planCatalogService->publicPlans();
        foreach ($publicPlans as $plan) {
            $name = (string) $plan->name;
            $prices = [
                'monthly' => null,
                'semiannual' => null,
                'annual' => null,
            ];
            foreach ($plan->prices as $price) {
                $period = (string) $price->billing_period;
                if (array_key_exists($period, $prices)) {
                    $prices[$period] = $price->amount !== null ? (float) $price->amount : null;
                }
            }

            $plans[$name] = [
                'label' => $labelMap[$name] ?? ucfirst($name),
                'price' => $prices['monthly'],
                'prices' => $prices,
                'features' => $featureMap[$name] ?? [],
                'ai_enabled' => (bool) $plan->ai_enabled,
            ];
        }

        $selectedPlan = (string) $request->query('plan', 'starter');
        $selectedPeriod = (string) $request->query('period', 'monthly');
        if (! in_array($selectedPeriod, ['monthly', 'semiannual', 'annual'], true)) {
            $selectedPeriod = 'monthly';
        }

        return view('auth.register', compact('plans', 'selectedPlan', 'selectedPeriod'));
    }

    public function store(Request $request): RedirectResponse
    {
        $publicPlanKeys = $this->publicPlanKeys();

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:180'],
            'admin_name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'plan' => ['required', Rule::in($publicPlanKeys)],
            'billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'terms' => ['accepted'],
        ]);

        $existingUser = User::query()->where('email', $data['email'])->first();
        if ($existingUser && $existingUser->company?->status !== 'pending_payment') {
            return back()
                ->withErrors(['email' => 'Este correo ya está registrado.'])
                ->withInput();
        }

        $company = $existingUser?->company;
        if (! $company) {
            $company = Company::query()->create([
                'name' => $data['company_name'],
                'owner_name' => $data['admin_name'],
                'owner_email' => $data['email'],
                'owner_phone' => $data['phone'] ?? '',
                'billing_email' => $data['email'],
                'status' => 'pending_payment',
            ]);
        } else {
            $company->update([
                'name' => $data['company_name'],
                'owner_name' => $data['admin_name'],
                'owner_email' => $data['email'],
                'owner_phone' => $data['phone'] ?? $company->owner_phone ?? '',
                'billing_email' => $data['email'],
                'status' => 'pending_payment',
            ]);
        }

        $admin = $existingUser ?: User::query()->create([
            'company_id' => $company->id,
            'name' => $data['admin_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'is_active' => true,
            'can_access_billing' => true,
            'can_access_inventory' => true,
            'must_change_password' => false,
        ]);

        if ($existingUser) {
            $admin->update([
                'name' => $data['admin_name'],
                'password' => Hash::make($data['password']),
            ]);
        }

        if (! $admin->technicianProfile) {
            $this->createTechnicianProfile($company->id, $admin);
        }

        $plan = (string) $data['plan'];
        $billingPeriod = (string) $data['billing_period'];

        if (! $this->stripeIsConfigured()) {
            return back()->withErrors([
                'plan' => 'En este momento no es posible iniciar el proceso de pago. Intenta nuevamente en unos minutos o contacta a soporte.',
            ])->withInput();
        }

        $stripeCheckoutService = app(\App\Services\StripeCheckoutService::class);

        $priceId = (string) data_get(config('stripe.plans', []), "{$plan}.prices.{$billingPeriod}");
        if ($priceId === '') {
            return back()->withErrors([
                'plan' => 'El plan seleccionado no está configurado correctamente.',
            ])->withInput();
        }

        $trialDays = $this->resolveTrialDays($plan, $billingPeriod);

        $successUrl = url('/onboarding/success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('register');

        $metadata = [
            'company_id' => (string) $company->id,
            'plan' => $plan,
            'billing_period' => $billingPeriod,
            'company_name' => $company->name,
            'admin_name' => $admin->name,
            'admin_email' => $admin->email,
        ];
        $subscriptionData = [
            'metadata' => $metadata,
        ];
        if ($trialDays > 0) {
            $subscriptionData['trial_period_days'] = $trialDays;
        }

        try {
            if (! $company->stripe_id) {
                $company->stripe_id = $stripeCheckoutService->createCustomer(
                    $company->name,
                    $data['email'],
                    $data['phone'] ?? null
                );
                $company->save();
            }

            $session = $stripeCheckoutService->createCheckoutSession([
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer' => $company->stripe_id,
                'metadata' => $metadata,
                'subscription_data' => $subscriptionData,
            ]);
        } catch (StripeCheckoutException $exception) {
            return back()->withErrors([
                'plan' => $exception->getMessage(),
            ])->withInput();
        }

        $company->update([
            'pending_plan' => $plan,
            'pending_billing_period' => $billingPeriod,
            'stripe_checkout_session_id' => $session['id'],
        ]);

        return redirect()->away($session['url']);
    }

    private function publicPlanKeys(): array
    {
        return app(PlanCatalogService::class)
            ->publicPlans()
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->toArray();
    }

    public function success(Request $request)
    {
        $sessionId = (string) $request->query('session_id', '');
        $company = $sessionId !== ''
            ? Company::query()->where('stripe_checkout_session_id', $sessionId)->first()
            : null;

        $plan = $company?->subscription?->plan ?? $company?->pending_plan ?? null;
        $planData = $plan ? (config('plans')[$plan] ?? null) : null;

        return view('onboarding.success', [
            'company' => $company,
            'plan' => $plan,
            'planData' => $planData,
            'status' => $company?->status ?? 'pending_payment',
        ]);
    }

    public function suspended(Request $request)
    {
        $user = $request->user();
        $company = $user?->company;

        return view('account.suspended', [
            'company' => $company,
            'supportEmail' => config('support.email'),
            'supportWhatsapp' => config('support.whatsapp_url'),
        ]);
    }

    public function retryCheckout(Request $request): RedirectResponse
    {
        $company = $request->user()?->company;
        if (! $company) {
            abort(404);
        }

        $plan = (string) ($company->pending_plan ?: $company->subscription?->plan ?: 'starter');
        $billingPeriod = (string) ($company->pending_billing_period ?: 'monthly');

        $priceId = (string) data_get(config('stripe.plans', []), "{$plan}.prices.{$billingPeriod}");
        if ($priceId === '') {
            return back()->withErrors(['plan' => 'El plan seleccionado no está configurado correctamente.']);
        }

        $trialDays = $this->resolveTrialDays($plan, $billingPeriod);

        if (! $this->stripeIsConfigured()) {
            return back()->withErrors([
                'plan' => 'En este momento no es posible continuar con el pago. Intenta nuevamente en unos minutos o contacta a soporte.',
            ]);
        }

        $stripeCheckoutService = app(\App\Services\StripeCheckoutService::class);

        $successUrl = url('/onboarding/success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('register');

        $metadata = [
            'company_id' => (string) $company->id,
            'plan' => $plan,
            'billing_period' => $billingPeriod,
            'company_name' => $company->name,
            'admin_name' => $company->owner_name,
            'admin_email' => $company->owner_email,
        ];
        $subscriptionData = [
            'metadata' => $metadata,
        ];
        if ($trialDays > 0) {
            $subscriptionData['trial_period_days'] = $trialDays;
        }

        try {
            if (! $company->stripe_id) {
                $company->stripe_id = $stripeCheckoutService->createCustomer(
                    $company->name,
                    $company->owner_email,
                    $company->owner_phone
                );
                $company->save();
            }

            $session = $stripeCheckoutService->createCheckoutSession([
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer' => $company->stripe_id,
                'metadata' => $metadata,
                'subscription_data' => $subscriptionData,
            ]);
        } catch (StripeCheckoutException $exception) {
            return back()->withErrors([
                'plan' => $exception->getMessage(),
            ]);
        }

        $company->update([
            'pending_plan' => $plan,
            'pending_billing_period' => $billingPeriod,
            'stripe_checkout_session_id' => $session['id'],
        ]);

        return redirect()->away($session['url']);
    }

    private function createTechnicianProfile(int $companyId, User $user): void
    {
        TechnicianProfile::query()->create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'employee_code' => 'USR-'.$user->id,
            'display_name' => $user->name,
            'specialties' => [],
            'status' => TechnicianStatus::AVAILABLE,
            'max_concurrent_orders' => 5,
            'hourly_cost' => 0,
            'is_assignable' => true,
        ]);
    }

    private function stripeIsConfigured(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        return trim((string) config('services.stripe.key')) !== ''
            && trim((string) config('services.stripe.secret')) !== '';
    }

    private function resolveTrialDays(string $plan, string $billingPeriod): int
    {
        try {
            $price = app(PlanCatalogService::class)->resolvePrice($plan, $billingPeriod, 'mxn');

            return app(TrialPolicyService::class)->trialDaysForPrice($price);
        } catch (ModelNotFoundException) {
            return 0;
        }
    }
}
