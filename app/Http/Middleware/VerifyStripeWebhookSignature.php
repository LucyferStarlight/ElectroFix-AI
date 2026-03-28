<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class VerifyStripeWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) (config('stripe.webhook_secret') ?: config('services.stripe.webhook_secret') ?: config('cashier.webhook.secret'));
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');

        if ($secret === '') {
            Log::critical('Stripe webhook secret is not configured.');

            return response()->json(['ok' => false, 'message' => 'Webhook secret no configurado.'], 500);
        }

        if ($signature === '') {
            Log::warning('Stripe webhook rejected: missing Stripe-Signature header.');

            return response()->json(['ok' => false, 'message' => 'Firma de webhook inválida.'], 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\UnexpectedValueException|SignatureVerificationException $exception) {
            Log::warning('Stripe webhook rejected: invalid signature.', [
                'error_message' => mb_substr($exception->getMessage(), 0, 240),
            ]);

            return response()->json(['ok' => false, 'message' => 'Firma de webhook inválida.'], 400);
        }

        $request->attributes->set('stripe_event', $event->toArray());

        return $next($request);
    }
}
