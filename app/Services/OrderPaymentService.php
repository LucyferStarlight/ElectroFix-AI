<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Exceptions\OrderPaymentException;
use Illuminate\Support\Arr;

class OrderPaymentService
{
    public function syncStripePaymentIntentSucceeded(array $payload): void
    {
        $object = (array) Arr::get($payload, 'data.object', []);
        $paymentIntentId = (string) ($object['id'] ?? '');
        $amountReceived = (int) ($object['amount_received'] ?? $object['amount'] ?? 0);

        if ($paymentIntentId === '' || $amountReceived <= 0) {
            return;
        }

        $order = $this->resolveOrderFromStripePayload($object);
        if (! $order) {
            throw OrderPaymentException::stripeOrderReferenceMissing();
        }

        $existing = OrderPayment::query()
            ->where('direction', 'payment')
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if ($existing) {
            return;
        }

        $order->registerPayment($this->fromStripeAmount($amountReceived), [
            'source' => 'stripe',
            'currency' => (string) ($object['currency'] ?? 'mxn'),
            'stripe_payment_intent_id' => $paymentIntentId,
            'stripe_checkout_session_id' => null,
            'stripe_charge_id' => (string) Arr::get($object, 'latest_charge'),
            'status' => 'succeeded',
            'processed_at' => now(),
            'metadata' => [
                'stripe_event_type' => 'payment_intent.succeeded',
                'metadata' => Arr::get($object, 'metadata', []),
            ],
        ]);
    }

    public function syncStripeCheckoutCompleted(array $payload): void
    {
        $object = (array) Arr::get($payload, 'data.object', []);
        $mode = (string) ($object['mode'] ?? '');
        $paymentStatus = (string) ($object['payment_status'] ?? '');
        $order = $this->resolveOrderFromStripePayload($object);

        if ($mode !== 'payment' || ! $order) {
            return;
        }

        if (! in_array($paymentStatus, ['paid', 'no_payment_required'], true)) {
            return;
        }

        $sessionId = (string) ($object['id'] ?? '');
        if ($sessionId === '') {
            return;
        }

        $existing = OrderPayment::query()
            ->where('direction', 'payment')
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if ($existing) {
            return;
        }

        $amountTotal = (int) ($object['amount_total'] ?? 0);
        if ($amountTotal <= 0) {
            return;
        }

        $order->registerPayment($this->fromStripeAmount($amountTotal), [
            'source' => 'stripe',
            'currency' => (string) ($object['currency'] ?? 'mxn'),
            'stripe_payment_intent_id' => (string) ($object['payment_intent'] ?? ''),
            'stripe_checkout_session_id' => $sessionId,
            'status' => 'succeeded',
            'processed_at' => now(),
            'metadata' => [
                'stripe_event_type' => 'checkout.session.completed',
                'metadata' => Arr::get($object, 'metadata', []),
            ],
        ]);
    }

    public function syncStripeChargeRefunded(array $payload): void
    {
        $object = (array) Arr::get($payload, 'data.object', []);
        $chargeId = (string) ($object['id'] ?? '');
        $paymentIntentId = (string) ($object['payment_intent'] ?? '');
        $amountRefunded = (int) ($object['amount_refunded'] ?? 0);

        if ($amountRefunded <= 0 || ($chargeId === '' && $paymentIntentId === '')) {
            return;
        }

        $existingRefund = OrderPayment::query()
            ->where('direction', 'refund')
            ->when($chargeId !== '', fn ($query) => $query->where('stripe_charge_id', $chargeId))
            ->when($chargeId === '' && $paymentIntentId !== '', fn ($query) => $query->where('stripe_payment_intent_id', $paymentIntentId))
            ->first();

        if ($existingRefund) {
            return;
        }

        $originalPayment = OrderPayment::query()
            ->where('direction', 'payment')
            ->when($paymentIntentId !== '', fn ($query) => $query->where('stripe_payment_intent_id', $paymentIntentId))
            ->when($paymentIntentId === '' && $chargeId !== '', fn ($query) => $query->where('stripe_charge_id', $chargeId))
            ->latest('id')
            ->first();

        $order = $originalPayment?->order ?? $this->resolveOrderFromStripePayload($object);
        if (! $order) {
            throw OrderPaymentException::stripeOrderReferenceMissing();
        }

        $order->registerRefund($this->fromStripeAmount($amountRefunded), [
            'source' => 'stripe',
            'currency' => (string) ($object['currency'] ?? 'mxn'),
            'stripe_payment_intent_id' => $paymentIntentId ?: $originalPayment?->stripe_payment_intent_id,
            'stripe_checkout_session_id' => $originalPayment?->stripe_checkout_session_id,
            'stripe_charge_id' => $chargeId ?: $originalPayment?->stripe_charge_id,
            'stripe_refund_id' => (string) Arr::get($object, 'refunds.data.0.id', ''),
            'status' => 'refunded',
            'processed_at' => now(),
            'metadata' => [
                'stripe_event_type' => 'charge.refunded',
                'metadata' => Arr::get($object, 'metadata', []),
            ],
        ]);
    }

    private function resolveOrderFromStripePayload(array $object): ?Order
    {
        $metadata = (array) ($object['metadata'] ?? []);
        $orderId = (int) ($metadata['order_id'] ?? 0);

        if ($orderId <= 0) {
            return null;
        }

        return Order::query()->find($orderId);
    }

    private function fromStripeAmount(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
