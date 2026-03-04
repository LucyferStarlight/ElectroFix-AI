<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySubscriptionActive
{
    private const ADMIN_ALLOWED_ROUTES_WHEN_INACTIVE = [
        'admin.subscription.edit',
        'admin.subscription.checkout',
        'admin.subscription.change',
        'admin.subscription.cancel',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role === 'developer') {
            return $next($request);
        }

        $subscription = $user->company?->subscription;

        if (! $subscription) {
            return $this->deny($request, $user->role, 'No existe una suscripción activa para tu empresa.');
        }

        if (in_array($subscription->status, ['active', 'trial'], true)) {
            return $next($request);
        }

        if ($user->role === 'admin' && $this->isAdminAllowedRoute($request)) {
            return $next($request);
        }

        $message = $user->role === 'worker'
            ? 'El acceso ha sido suspendido por un tema administrativo. Por favor, informa a tu empleador.'
            : sprintf(
                'Tu suscripción está en estado %s. Fecha límite: %s. Acción requerida: actualizar método de pago o renovar plan.',
                $subscription->status,
                $subscription->current_period_end?->format('Y-m-d') ?? 'N/A'
            );

        return $this->deny($request, $user->role, $message);
    }

    private function deny(Request $request, string $role, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'data' => null,
                'meta' => [],
                'error' => [
                    'code' => 'SUBSCRIPTION_INACTIVE',
                    'message' => $message,
                ],
            ], 402);
        }

        if ($role === 'admin') {
            return redirect()->route('admin.subscription.edit')->withErrors([
                'email' => $message,
            ]);
        }

        auth()->logout();

        return redirect()->route('login')->withErrors([
            'email' => $message,
        ]);
    }

    private function isAdminAllowedRoute(Request $request): bool
    {
        foreach (self::ADMIN_ALLOWED_ROUTES_WHEN_INACTIVE as $name) {
            if ($request->routeIs($name)) {
                return true;
            }
        }

        if ($request->is('api/v1/billing/subscription*')) {
            return true;
        }

        return false;
    }
}
