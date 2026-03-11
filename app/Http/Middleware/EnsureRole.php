<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        if (! $user->is_active) {
            abort(403, 'Tu cuenta está desactivada.');
        }

        return $next($request);
    }
}
