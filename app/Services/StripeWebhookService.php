<?php

namespace App\Services;

use App\Models\Company;
use App\Models\StripeWebhookEvent;
use App\Models\Subscription;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeWebhookService
{
    public function handle(array $eventPayload): void
    {
        $eventId = (string) Arr::get($eventPayload, 'id');
        $eventType = (string) Arr::get($eventPayload, 'type');

        if ($eventId === '' || $eventType === '') {
            return;
        }

        $stored = StripeWebhookEvent::query()->firstOrCreate(
            ['event_id' => $eventId],
            [
                'type' => $eventType,
                'payload' => $eventPayload,
                'status' => 'processing',
            ]
        );

        if ($stored->status === 'processed') {
            return;
        }

        // If another process is handling this event and it is still fresh, skip duplicate work.
        if ($stored->status === 'processing'
            && $stored->updated_at !== null
            && $stored->updated_at->gt(now()->subMinutes(5))) {
            return;
        }

        $stored->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            DB::transaction(function () use ($eventPayload, $eventType): void {
                match ($eventType) {
                    'invoice.payment_succeeded' => $this->onInvoicePaid($eventPayload),
                    'invoice.payment_failed' => $this->onInvoiceFailed($eventPayload),
                    'customer.subscription.updated' => $this->onSubscriptionUpdated($eventPayload),
                    'customer.subscription.deleted' => $this->onSubscriptionDeleted($eventPayload),
                    default => null,
                };
            });

            $stored->update([
                'status' => in_array($eventType, [
                    'invoice.payment_succeeded',
                    'invoice.payment_failed',
                    'customer.subscription.updated',
                    'customer.subscription.deleted',
                ], true) ? 'processed' : 'ignored',
                'processed_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $stored->update([
                'status' => 'error',
                'processed_at' => now(),
                'error_message' => mb_substr($e->getMessage(), 0, 240),
            ]);

            Log::error('Stripe webhook processing failed', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'error_message' => mb_substr($e->getMessage(), 0, 240),
            ]);

            throw $e;
        }
    }

    private function onInvoicePaid(array $payload): void
    {
        $stripeSubscriptionId = (string) Arr::get($payload, 'data.object.subscription');
        if ($stripeSubscriptionId === '') {
            return;
        }

        Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->update(['status' => 'active']);
    }

    private function onInvoiceFailed(array $payload): void
    {
        $stripeSubscriptionId = (string) Arr::get($payload, 'data.object.subscription');
        if ($stripeSubscriptionId === '') {
            return;
        }

        Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->update(['status' => 'past_due']);
    }

    private function onSubscriptionUpdated(array $payload): void
    {
        $stripeSubscriptionId = (string) Arr::get($payload, 'data.object.id');
        if ($stripeSubscriptionId === '') {
            return;
        }

        $status = (string) Arr::get($payload, 'data.object.status');
        $periodEndTs = Arr::get($payload, 'data.object.current_period_end');
        $cancelAtPeriodEnd = (bool) Arr::get($payload, 'data.object.cancel_at_period_end', false);

        $mappedStatus = match ($status) {
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'canceled',
            default => 'suspended',
        };

        Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->update([
                'status' => $mappedStatus,
                'current_period_end' => is_numeric($periodEndTs) ? now()->setTimestamp((int) $periodEndTs) : null,
                'cancel_at_period_end' => $cancelAtPeriodEnd,
            ]);
    }

    private function onSubscriptionDeleted(array $payload): void
    {
        $stripeSubscriptionId = (string) Arr::get($payload, 'data.object.id');
        if ($stripeSubscriptionId === '') {
            return;
        }

        Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->update([
                'status' => 'canceled',
                'cancel_at_period_end' => true,
            ]);
    }

    public function syncCompanyByStripeCustomerId(string $stripeCustomerId): ?Company
    {
        return Company::query()->where('stripe_id', $stripeCustomerId)->first();
    }
}
