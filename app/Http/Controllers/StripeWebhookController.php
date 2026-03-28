<?php

namespace App\Http\Controllers;

use App\Services\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeWebhookService $stripeWebhookService)
    {
    }

    public function handle(Request $request): JsonResponse
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
            Log::error('Stripe webhook controller failure', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'error_message' => mb_substr($exception->getMessage(), 0, 240),
            ]);

            return response()->json(['ok' => false, 'message' => 'No se pudo procesar el webhook.'], 500);
        }

        return response()->json(['ok' => true]);
    }
}
