<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Observability\ObservabilityLogger;
use App\Services\Exceptions\OrderPaymentException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    public function __construct(private readonly ObservabilityLogger $observability) {}

    public function syncStripePaymentIntentSucceeded(array $payload): void
    {
        $object = (array) Arr::get($payload, 'data.object', []);
        $paymentIntentId = (string) ($object['id'] ?? '');
        $amountReceived = (int) ($object['amount_received'] ?? $object['amount'] ?? 0);
        $context = $this->paymentContext($object, 'payments.stripe.payment_intent_succeeded');

        if ($paymentIntentId === '' || $amountReceived <= 0) {
            return;
        }

        try {
            DB::transaction(function () use ($object, $paymentIntentId, $amountReceived, $context): void {
                $existing = OrderPayment::query()
                    ->where('direction', 'payment')
                    ->where('stripe_payment_intent_id', $paymentIntentId)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return;
                }

                $order = $this->resolveLockedOrderFromStripePayload($object);
                if (! $order) {
                    throw OrderPaymentException::stripeOrderReferenceMissing();
                }

                $this->assertStripeAmountIsExpected($order, $amountReceived, (array) Arr::get($object, 'metadata', []));

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

                $this->observability->payment('payments.sync.completed', array_merge($context, [
                    'order_id' => $order->id,
                    'amount' => $this->fromStripeAmount($amountReceived),
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'source' => 'stripe',
                    'status' => 'succeeded',
                ]));
            });
        } catch (\Throwable $exception) {
            $this->observability->critical('payments.sync.failed', $exception, $context);

            throw $exception;
        }
    }

    public function syncStripeCheckoutCompleted(array $payload): void
    {
        $object = (array) Arr::get($payload, 'data.object', []);
        $mode = (string) ($object['mode'] ?? '');
        $paymentStatus = (string) ($object['payment_status'] ?? '');
        $order = $this->resolveOrderFromStripePayload($object);
        $context = $this->paymentContext($object, 'payments.stripe.checkout_completed');

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

        $amountTotal = (int) ($object['amount_total'] ?? 0);
        $paymentIntentId = (string) ($object['payment_intent'] ?? '');

        if ($amountTotal <= 0) {
            return;
        }

        try {
            DB::transaction(function () use ($object, $order, $sessionId, $paymentIntentId, $amountTotal, $context): void {
                $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
                if (! $lockedOrder) {
                    throw OrderPaymentException::stripeOrderReferenceMissing();
                }

                if ($paymentIntentId !== '') {
                    $existingByIntent = OrderPayment::query()
                        ->where('direction', 'payment')
                        ->where('stripe_payment_intent_id', $paymentIntentId)
                        ->lockForUpdate()
                        ->first();

                    if ($existingByIntent) {
                        return;
                    }
                }

                $existingBySession = OrderPayment::query()
                    ->where('direction', 'payment')
                    ->where('stripe_checkout_session_id', $sessionId)
                    ->lockForUpdate()
                    ->first();

                if ($existingBySession) {
                    return;
                }

                $this->assertStripeAmountIsExpected($lockedOrder, $amountTotal, (array) Arr::get($object, 'metadata', []));

                $lockedOrder->registerPayment($this->fromStripeAmount($amountTotal), [
                    'source' => 'stripe',
                    'currency' => (string) ($object['currency'] ?? 'mxn'),
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'stripe_checkout_session_id' => $sessionId,
                    'status' => 'succeeded',
                    'processed_at' => now(),
                    'metadata' => [
                        'stripe_event_type' => 'checkout.session.completed',
                        'metadata' => Arr::get($object, 'metadata', []),
                    ],
                ]);

                $this->observability->payment('payments.sync.completed', array_merge($context, [
                    'order_id' => $lockedOrder->id,
                    'amount' => $this->fromStripeAmount($amountTotal),
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'stripe_checkout_session_id' => $sessionId,
                    'source' => 'stripe',
                    'status' => 'succeeded',
                ]));
            });
        } catch (\Throwable $exception) {
            $this->observability->critical('payments.sync.failed', $exception, $context);

            throw $exception;
        }
    }

    public function syncStripeChargeRefunded(array $payload): void
    {
        $object = (array) Arr::get($payload, 'data.object', []);
        $chargeId = (string) ($object['id'] ?? '');
        $paymentIntentId = (string) ($object['payment_intent'] ?? '');
        $amountRefunded = (int) ($object['amount_refunded'] ?? 0);
        $context = $this->paymentContext($object, 'payments.stripe.charge_refunded');

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

        try {
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

            $this->observability->payment('payments.refund.completed', array_merge($context, [
                'order_id' => $order->id,
                'amount' => $this->fromStripeAmount($amountRefunded),
                'stripe_payment_intent_id' => $paymentIntentId ?: $originalPayment?->stripe_payment_intent_id,
                'stripe_charge_id' => $chargeId ?: $originalPayment?->stripe_charge_id,
                'source' => 'stripe',
                'status' => 'refunded',
            ]));
        } catch (\Throwable $exception) {
            $this->observability->critical('payments.refund.failed', $exception, $context);

            throw $exception;
        }
    }

    public function syncStripePaymentIntentFailed(array $payload): void
    {
        $object = (array) Arr::get($payload, 'data.object', []);
        $paymentIntentId = (string) ($object['id'] ?? '');
        $amountAttempted = (int) ($object['amount_received'] ?? $object['amount'] ?? 0);
        $context = $this->paymentContext($object, 'payments.stripe.payment_intent_failed');

        if ($paymentIntentId === '') {
            return;
        }

        try {
            DB::transaction(function () use ($object, $paymentIntentId, $amountAttempted, $context): void {
                $order = $this->resolveLockedOrderFromStripePayload($object);
                if (! $order) {
                    throw OrderPaymentException::stripeOrderReferenceMissing();
                }

                $alreadySucceeded = OrderPayment::query()
                    ->where('direction', 'payment')
                    ->where('stripe_payment_intent_id', $paymentIntentId)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadySucceeded) {
                    return;
                }

                $alreadyFailed = OrderPayment::query()
                    ->where('direction', 'attempt')
                    ->where('stripe_payment_intent_id', $paymentIntentId)
                    ->where('status', 'failed')
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyFailed) {
                    return;
                }

                $order->payments()->create([
                    'billing_document_id' => null,
                    'direction' => 'attempt',
                    'amount' => $amountAttempted > 0 ? $this->fromStripeAmount($amountAttempted) : 0,
                    'currency' => strtolower((string) ($object['currency'] ?? 'mxn')),
                    'source' => 'stripe',
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'stripe_checkout_session_id' => null,
                    'stripe_charge_id' => (string) Arr::get($object, 'latest_charge'),
                    'stripe_refund_id' => null,
                    'status' => 'failed',
                    'metadata' => [
                        'stripe_event_type' => 'payment_intent.payment_failed',
                        'failure_code' => (string) Arr::get($object, 'last_payment_error.code', ''),
                        'failure_message' => (string) Arr::get($object, 'last_payment_error.message', ''),
                        'metadata' => Arr::get($object, 'metadata', []),
                    ],
                    'processed_at' => now(),
                ]);

                $order->refreshPaymentTotals();

                $this->observability->warning('payments.attempt.failed', array_merge($context, [
                    'order_id' => $order->id,
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'failure_code' => (string) Arr::get($object, 'last_payment_error.code', ''),
                ]));
            });
        } catch (\Throwable $exception) {
            $this->observability->critical('payments.attempt.failed_unexpected', $exception, $context);

            throw $exception;
        }
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

    private function resolveLockedOrderFromStripePayload(array $object): ?Order
    {
        $metadata = (array) ($object['metadata'] ?? []);
        $orderId = (int) ($metadata['order_id'] ?? 0);

        if ($orderId <= 0) {
            return null;
        }

        return Order::query()->lockForUpdate()->find($orderId);
    }

    private function assertStripeAmountIsExpected(Order $order, int $receivedAmountCents, array $metadata = []): void
    {
        $expectedOutstanding = $this->toStripeAmount($order->outstandingBalance());

        if ($expectedOutstanding <= 0) {
            throw OrderPaymentException::stripePaymentAlreadySettled();
        }

        $expectedFromMetadata = (int) ($metadata['expected_amount_cents'] ?? 0);
        if ($expectedFromMetadata > 0 && $expectedFromMetadata !== $receivedAmountCents) {
            throw OrderPaymentException::stripeAmountMismatch(
                $this->fromStripeAmount($receivedAmountCents),
                $this->fromStripeAmount($expectedFromMetadata)
            );
        }

        if ($receivedAmountCents > $expectedOutstanding) {
            throw OrderPaymentException::stripeAmountMismatch(
                $this->fromStripeAmount($receivedAmountCents),
                $this->fromStripeAmount($expectedOutstanding)
            );
        }
    }

    private function fromStripeAmount(int $amount): float
    {
        return round($amount / 100, 2);
    }

    private function toStripeAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function paymentContext(array $object, string $action): array
    {
        return [
            'action' => $action,
            'order_id' => (int) Arr::get($object, 'metadata.order_id', 0) ?: null,
            'stripe_object_id' => (string) ($object['id'] ?? ''),
            'source' => 'stripe',
        ];
    }
}
