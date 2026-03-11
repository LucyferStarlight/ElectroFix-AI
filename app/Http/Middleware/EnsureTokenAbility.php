<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenAbility
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'No autenticado.');
        }

        // Keep developer/admin superusers unrestricted in API.
        if (in_array($user->role, ['developer', 'admin'], true)) {
            return $next($request);
        }

        foreach ($abilities as $ability) {
            if ($user->tokenCan($ability)) {
                return $next($request);
            }
        }

        abort(403, 'Tu token no tiene permisos para esta operación.');
    }
}

