<?php

namespace App\Http\Controllers;

use App\Services\Billing\StripeSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class BillingController extends Controller
{
    public function __construct(private readonly StripeSubscriptionService $stripeSubscriptionService)
    {
    }

    public function checkout(Request $request)
    {
        if (! $this->stripeIsConfigured()) {
            return back()->withErrors([
                'plan' => 'En este momento no es posible procesar el pago. Intenta nuevamente en unos minutos o contacta a soporte.',
            ]);
        }

        $data = $request->validate([
            'company_id' => ['prohibited'],
            'plan' => ['required', Rule::in(['starter', 'pro', 'enterprise'])],
            'billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
        ]);

        $company = $request->user()?->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        return $this->stripeSubscriptionService->checkoutHosted(
            $company,
            (string) $data['plan'],
            (string) $data['billing_period']
        );
    }

    public function success(): RedirectResponse
    {
        return redirect()
            ->route('admin.subscription.edit')
            ->with('success', 'Checkout completado. Stripe confirmará el estado final por webhook.');
    }

    public function cancel(): RedirectResponse
    {
        return redirect()
            ->route('admin.subscription.edit')
            ->with('warning', 'El checkout fue cancelado. No se aplicaron cambios.');
    }

    public function portal(Request $request): RedirectResponse
    {
        if (! $this->stripeIsConfigured()) {
            return back()->withErrors([
                'plan' => 'En este momento no es posible acceder al portal de facturacion. Intenta nuevamente en unos minutos o contacta a soporte.',
            ]);
        }

        $request->validate([
            'company_id' => ['prohibited'],
        ]);

        $company = $request->user()?->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        return $this->stripeSubscriptionService->portal($company);
    }

    private function stripeIsConfigured(): bool
    {
        return trim((string) config('services.stripe.key')) !== ''
            && trim((string) config('services.stripe.secret')) !== '';
    }
}
