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
                '10 consultas IA incluidas por mes',
                'Aprox. 8,000 tokens',
                'Diagnóstico asistido con Groq',
            ],
            'pro' => [
                'Hasta 100 técnicos',
                'Hasta 500 órdenes activas',
                'Inventario completo',
                'Facturación integrada',
                'Estadísticas',
                'IA habilitada',
                '75 consultas IA incluidas por mes',
                'Aprox. 50,000 tokens',
                'Sin consultas adicionales',
            ],
            'enterprise' => [
                'Técnicos ilimitados',
                'Órdenes ilimitadas',
                'Inventario avanzado',
                'Facturación completa',
                'Reportes avanzados',
                'IA con Groq habilitada',
                '200 consultas IA incluidas por mes',
                'Aprox. 120,000 tokens',
                'Consultas adicionales disponibles (2 MXN c/u)',
            ],
        ];
        $labelMap = [
            'starter' => (string) config('plans.starter.label', 'Básico'),
            'pro' => (string) config('plans.pro.label', 'Profesional'),
            'enterprise' => (string) config('plans.enterprise.label', 'Empresarial'),
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

        if (! $this->stripeIsConfigured()) {
            return back()->withErrors([
                'plan' => 'En este momento no es posible procesar el pago del plan seleccionado. Intenta nuevamente en unos minutos o contacta a soporte.',
            ])->withInput();
        }

        $stripe = new StripeClient((string) config('services.stripe.secret'));

        try {
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
        } catch (\Stripe\Exception\ApiErrorException $exception) {
            $message = 'En este momento no fue posible iniciar el pago. Intenta nuevamente en unos minutos o contacta a soporte.';

            if (str_contains(mb_strtolower($exception->getMessage()), 'no such price')) {
                $message = 'En este momento el plan seleccionado no esta disponible para pago. Intenta nuevamente en unos minutos o contacta a soporte.';
            }

            return back()->withErrors([
                'plan' => $message,
            ])->withInput();
        }

        return redirect()->away((string) $session->url);
    }

    private function stripeIsConfigured(): bool
    {
        return trim((string) config('services.stripe.key')) !== ''
            && trim((string) config('services.stripe.secret')) !== '';
    }
}
