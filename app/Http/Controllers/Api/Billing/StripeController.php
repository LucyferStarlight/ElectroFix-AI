<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\CompanySubscriptionService;
use App\Services\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StripeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CompanySubscriptionService $companySubscriptionService,
        private readonly StripeWebhookService $stripeWebhookService
    ) {
    }

    /**
     * Crea la suscripción inicial de una empresa usando el plan/periodo seleccionado.
     */
    public function checkout(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'No autorizado.');

        $data = $request->validate([
            'company_id' => ['prohibited'],
            'plan' => ['required', Rule::in(['starter', 'pro', 'enterprise'])],
            'billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
            'payment_method' => ['required', 'string', 'max:255'],
        ]);

        $company = $request->user()?->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $subscription = $this->companySubscriptionService->checkout(
            $company,
            (string) $data['plan'],
            (string) $data['billing_period'],
            (string) $data['payment_method']
        );

        return $this->success($subscription, status: 201);
    }

    /**
     * Realiza upgrade inmediato o programa downgrade al cierre de ciclo.
     */
    public function change(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'No autorizado.');

        $data = $request->validate([
            'company_id' => ['prohibited'],
            'plan' => ['required', Rule::in(['starter', 'pro', 'enterprise'])],
            'billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
        ]);

        $company = $request->user()?->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $result = $this->companySubscriptionService->requestChange(
            $company,
            (string) $data['plan'],
            (string) $data['billing_period'],
            $request->user()
        );

        return $this->success($result);
    }

    /**
     * Cancela al final de periodo para evitar perder acceso inmediato.
     */
    public function cancel(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'No autorizado.');

        $request->validate(['company_id' => ['prohibited']]);

        $company = $request->user()?->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $subscription = $this->companySubscriptionService->cancelAtPeriodEnd($company);

        return $this->success($subscription);
    }

    /**
     * Endpoint para Stripe Webhooks. Debe ser público y validar firma.
     */
    public function webhook(Request $request): JsonResponse
    {
        $event = (array) $request->attributes->get('stripe_event', []);
        $eventId = (string) data_get($event, 'id');
        $eventType = (string) data_get($event, 'type');

        if ($eventId === '' || $eventType === '') {
            return response()->json(['ok' => false, 'message' => 'Evento Stripe inválido.'], 400);
        }

        try {
            $this->stripeWebhookService->handle($event);
        } catch (\Throwable $exception) {
            Log::error('Stripe webhook API failure', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'error_message' => mb_substr($exception->getMessage(), 0, 240),
            ]);

            return response()->json(['ok' => false, 'message' => 'No se pudo procesar el webhook.'], 500);
        }

        return response()->json(['ok' => true]);
    }
}
