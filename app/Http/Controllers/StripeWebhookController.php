<?php

namespace App\Http\Controllers;

use App\Services\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeWebhookService $stripeWebhookService)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('cashier.webhook.secret');
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');

        if ($secret === '') {
            return response()->json(['ok' => false, 'message' => 'Webhook secret no configurado.'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\UnexpectedValueException|SignatureVerificationException $e) {
            return response()->json(['ok' => false, 'message' => 'Firma de webhook inválida.'], 400);
        }

        try {
            $this->stripeWebhookService->handle($event->toArray());
        } catch (\Throwable $e) {
            Log::error('Stripe webhook controller failure', [
                'event_id' => (string) data_get($event->toArray(), 'id'),
                'event_type' => (string) data_get($event->toArray(), 'type'),
                'error_message' => mb_substr($e->getMessage(), 0, 240),
            ]);

            return response()->json(['ok' => false, 'message' => 'No se pudo procesar el webhook.'], 500);
        }

        return response()->json(['ok' => true]);
    }
}
