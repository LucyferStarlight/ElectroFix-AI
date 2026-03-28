<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AttachObservabilityContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = [
            'order_id' => $this->extractOrderId($request),
            'user_id' => $request->user()?->id,
            'action' => $this->resolveAction($request),
        ];

        $request->attributes->set('observability.context', $context);
        Log::withContext($context);

        return $next($request);
    }

    private function resolveAction(Request $request): string
    {
        $route = $request->route();
        $routeName = $route?->getName();

        if (is_string($routeName) && trim($routeName) !== '') {
            return $routeName;
        }

        return strtolower($request->method()).' '.$request->path();
    }

    private function extractOrderId(Request $request): ?int
    {
        $routeOrder = $request->route('order');

        if ($routeOrder instanceof Model) {
            return (int) $routeOrder->getKey();
        }

        if (is_numeric($routeOrder)) {
            return (int) $routeOrder;
        }

        $bodyOrderId = $request->input('order_id');

        return is_numeric($bodyOrderId) ? (int) $bodyOrderId : null;
    }
}
