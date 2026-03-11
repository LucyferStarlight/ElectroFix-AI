<?php

namespace App\Http\Controllers;

use App\Services\PlanCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Stripe\StripeClient;

class SubscriptionController extends Controller
{
    public function index(PlanCatalogService $planCatalogService)
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
                $priceId = (string) Arr::get($configPlans, "{$name}.prices.{$period}", '');
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

        return view('index', compact('plans'));
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $plans = config('stripe.plans', []);

        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys($plans))],
            'billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
            'company_name' => ['nullable', 'string', 'max:180'],
            'admin_name' => ['nullable', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
        ]);

        $plan = (string) $validated['plan'];
        $billingPeriod = (string) $validated['billing_period'];
        $priceId = (string) Arr::get($plans, "{$plan}.prices.{$billingPeriod}", '');

        if ($priceId === '') {
            return back()->withErrors([
                'plan' => 'Este plan no está configurado correctamente en Stripe.',
            ])->withInput();
        }

        $stripe = new StripeClient((string) config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => route('landing').'?checkout=success',
            'cancel_url' => route('landing').'?checkout=cancel',
            'customer_email' => $validated['email'] ?? null,
            'metadata' => [
                'signup_source' => 'public_landing',
                'plan' => $plan,
                'billing_period' => $billingPeriod,
                'company_name' => (string) ($validated['company_name'] ?? ''),
                'admin_name' => (string) ($validated['admin_name'] ?? ''),
                'admin_email' => (string) ($validated['email'] ?? ''),
            ],
            'subscription_data' => [
                'metadata' => [
                    'signup_source' => 'public_landing',
                    'plan' => $plan,
                    'billing_period' => $billingPeriod,
                    'company_name' => (string) ($validated['company_name'] ?? ''),
                    'admin_name' => (string) ($validated['admin_name'] ?? ''),
                    'admin_email' => (string) ($validated['email'] ?? ''),
                ],
            ],
        ]);

        return redirect()->away((string) $session->url);
    }
}
