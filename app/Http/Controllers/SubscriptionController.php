<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Stripe\StripeClient;

class SubscriptionController extends Controller
{
    public function index()
    {
        return view('index', [
            'plans' => config('stripe.plans', []),
        ]);
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
