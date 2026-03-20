<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyActive
{
    private const ALLOWED_WHEN_PENDING = [
        'admin.subscription.edit',
        'billing.checkout',
        'billing.portal',
        'logout',
        'account.suspended',
        'onboarding.retry',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role === 'developer') {
            return $next($request);
        }

        $company = $user->company;
        if (! $company) {
            return $next($request);
        }

        if ($company->status === 'active') {
            return $next($request);
        }

        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        return redirect()
            ->route('account.suspended')
            ->withErrors(['email' => $this->messageForStatus((string) $company->status)]);
    }

    private function isAllowedRoute(Request $request): bool
    {
        foreach (self::ALLOWED_WHEN_PENDING as $name) {
            if ($request->routeIs($name)) {
                return true;
            }
        }

        return false;
    }

    private function messageForStatus(string $status): string
    {
        return match ($status) {
            'pending_payment' => 'Tu cuenta está pendiente de pago. Completa la suscripción para continuar.',
            'suspended' => 'Tu suscripción está suspendida por falta de pago. Por favor reintenta el cobro.',
            default => 'Tu cuenta no está activa.',
        };
    }
}
