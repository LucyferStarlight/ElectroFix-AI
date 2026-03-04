<?php

namespace App\Http\Controllers;

use App\Services\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $this->stripeWebhookService->handle($event->toArray());

        return response()->json(['ok' => true]);
    }
}
