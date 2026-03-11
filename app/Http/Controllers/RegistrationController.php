<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterCompanyRequest;
use App\Models\RegistrationConfirmation;
use App\Models\RegistrationIntent;
use App\Services\RegistrationService;
use App\Services\PlanCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Stripe\StripeClient;
use App\Models\Subscription;

class RegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $registrationService)
    {
    }

    public function showForm(PlanCatalogService $planCatalogService)
    {
        $configPlans = config('stripe.plans', []);
        $featureMap = [
            'starter' => [
                'Hasta 5 técnicos',
                'Hasta 75 órdenes activas',
                'Inventario',
                'Facturación',
                'Gestión de clientes',
                'Estadísticas básicas',
                'Sin acceso a IA',
            ],
            'pro' => [
                'Hasta 100 técnicos',
                'Hasta 500 órdenes activas',
                'Inventario completo',
                'Facturación integrada',
                'Estadísticas',
                'IA habilitada',
                '100 consultas IA incluidas por mes',
                'Aprox. 60,000 tokens',
                'Sin consultas adicionales',
            ],
            'enterprise' => [
                'Técnicos ilimitados',
                'Órdenes ilimitadas',
                'Inventario avanzado',
                'Facturación completa',
                'Reportes avanzados',
                'IA habilitada',
                '200 consultas IA incluidas por mes',
                'Aprox. 120,000 tokens',
                'Consultas adicionales disponibles (2 MXN c/u)',
            ],
        ];
        $labelMap = [
            'starter' => 'Starter',
            'pro' => 'Pro (Profesional)',
            'enterprise' => 'Enterprise (Empresarial)',
        ];

        $orderedPlans = [];
        $publicPlans = $planCatalogService->publicPlans();
        foreach ($publicPlans as $plan) {
            $name = (string) $plan->name;
            $prices = [];
            foreach ($plan->prices as $price) {
                $period = (string) $price->billing_period;
                $priceId = (string) ($configPlans[$name]['prices'][$period] ?? '');
                $prices[$period] = [
                    'amount' => $price->amount !== null ? (float) $price->amount : null,
                    'price_id' => $priceId,
                ];
            }

            $orderedPlans[$name] = [
                'name' => $name,
                'label' => $labelMap[$name] ?? ucfirst($name),
                'features' => $featureMap[$name] ?? [],
                'prices' => $prices,
            ];
        }

        $planOrder = ['starter', 'pro', 'enterprise'];
        $plans = [];
        foreach ($planOrder as $planName) {
            if (isset($orderedPlans[$planName])) {
                $plans[$planName] = $orderedPlans[$planName];
            }
        }

        return view('auth.register', compact('plans'));
    }

    public function store(RegisterCompanyRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $plan = (string) Arr::get($payload, 'subscription.plan', 'starter');
        $billingPeriod = (string) Arr::get($payload, 'subscription.billing_period', 'monthly');
        $plans = config('stripe.plans', []);

        $validator = validator(
            ['plan' => $plan, 'billing_period' => $billingPeriod],
            [
                'plan' => ['required', Rule::in(array_keys($plans))],
                'billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
            ]
        );
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $priceId = (string) Arr::get($plans, "{$plan}.prices.{$billingPeriod}", '');
        if ($priceId === '') {
            return back()->withErrors([
                'subscription.plan' => 'Este plan no está configurado correctamente en Stripe.',
            ])->withInput();
        }

        $token = Str::random(80);
        RegistrationIntent::query()->create([
            'access_token' => $token,
            'payload_snapshot' => $payload,
            'expires_at' => now()->addHours(24),
        ]);

        $stripe = new StripeClient((string) config('services.stripe.secret'));
        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => route('register.stripe.success', ['token' => $token]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('register', ['plan' => $plan, 'period' => $billingPeriod]),
            'customer_email' => (string) Arr::get($payload, 'admin.email', ''),
            'metadata' => [
                'registration_token' => $token,
                'plan' => $plan,
                'billing_period' => $billingPeriod,
                'company_name' => (string) Arr::get($payload, 'company.name', ''),
                'admin_name' => (string) Arr::get($payload, 'admin.name', ''),
                'admin_email' => (string) Arr::get($payload, 'admin.email', ''),
            ],
            'subscription_data' => [
                'metadata' => [
                    'registration_token' => $token,
                    'plan' => $plan,
                    'billing_period' => $billingPeriod,
                    'company_name' => (string) Arr::get($payload, 'company.name', ''),
                    'admin_name' => (string) Arr::get($payload, 'admin.name', ''),
                    'admin_email' => (string) Arr::get($payload, 'admin.email', ''),
                ],
            ],
        ]);

        return redirect()->away((string) $session->url);
    }

    public function confirmation(string $token)
    {
        $confirmation = RegistrationConfirmation::query()
            ->where('access_token', $token)
            ->firstOrFail();

        if ($confirmation->expires_at->isPast()) {
            abort(410, 'La confirmación expiró.');
        }

        if ($confirmation->consumed_at) {
            return view('auth.register-confirmation-expired');
        }

        $snapshot = $confirmation->payload_snapshot;

        $confirmation->update([
            'consumed_at' => now(),
        ]);

        return view('auth.register-confirmation', [
            'snapshot' => $snapshot,
            'loginEmail' => data_get($snapshot, 'admin.email'),
        ]);
    }

    public function stripeSuccess(Request $request, string $token): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '') {
            abort(400, 'Sesión de Stripe inválida.');
        }

        $intent = RegistrationIntent::query()
            ->where('access_token', $token)
            ->firstOrFail();

        if ($intent->expires_at->isPast()) {
            abort(410, 'La confirmación expiró.');
        }

        if ($intent->consumed_at && $intent->confirmation_token) {
            return redirect()->route('register.confirmation', $intent->confirmation_token);
        }

        $stripe = new StripeClient((string) config('services.stripe.secret'));
        $session = $stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription'],
        ]);

        $stripeCustomerId = (string) ($session->customer ?? '');
        $stripeSubscriptionId = (string) ($session->subscription?->id ?? $session->subscription ?? '');
        $stripeSubscriptionStatus = (string) ($session->subscription?->status ?? '');
        $paymentStatus = (string) ($session->payment_status ?? '');

        if ($stripeCustomerId === '' || $stripeSubscriptionId === '') {
            abort(400, 'Checkout incompleto en Stripe.');
        }

        $businessStatus = match ($stripeSubscriptionStatus) {
            'trialing' => Subscription::STATUS_TRIALING,
            'active' => Subscription::STATUS_ACTIVE,
            'past_due' => Subscription::STATUS_PAST_DUE,
            'canceled' => Subscription::STATUS_CANCELED,
            default => $paymentStatus === 'paid' ? Subscription::STATUS_ACTIVE : Subscription::STATUS_TRIALING,
        };

        $confirmation = $this->registrationService->registerWithStripe(
            $intent->payload_snapshot,
            $stripeCustomerId,
            $stripeSubscriptionId !== '' ? $stripeSubscriptionId : null,
            $businessStatus,
            $stripeSubscriptionStatus !== '' ? $stripeSubscriptionStatus : null
        );

        $intent->update([
            'consumed_at' => now(),
            'stripe_session_id' => $sessionId,
            'confirmation_token' => $confirmation->access_token,
        ]);

        return redirect()->route('register.confirmation', $confirmation->access_token);
    }
}
