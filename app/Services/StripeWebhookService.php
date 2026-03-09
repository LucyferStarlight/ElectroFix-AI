<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PlanPrice;
use App\Models\StripeWebhookEvent;
use App\Models\Subscription;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeWebhookService
{
    public function __construct(private readonly CompanySubscriptionService $companySubscriptionService)
    {
    }

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
                    'checkout.session.completed' => $this->onCheckoutCompleted($eventPayload),
                    'customer.subscription.created',
                    'customer.subscription.updated' => $this->onSubscriptionUpsert($eventPayload),
                    'customer.subscription.deleted' => $this->onSubscriptionDeleted($eventPayload),
                    'invoice.payment_succeeded' => $this->onInvoicePaid($eventPayload),
                    'invoice.payment_failed' => $this->onInvoiceFailed($eventPayload),
                    default => null,
                };
            });

            $trackedEvents = [
                'checkout.session.completed',
                'customer.subscription.created',
                'customer.subscription.updated',
                'customer.subscription.deleted',
                'invoice.payment_succeeded',
                'invoice.payment_failed',
            ];

            $stored->update([
                'status' => in_array($eventType, $trackedEvents, true) ? 'processed' : 'ignored',
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

    private function onCheckoutCompleted(array $payload): void
    {
        $customerId = (string) Arr::get($payload, 'data.object.customer');
        $subscriptionId = (string) Arr::get($payload, 'data.object.subscription');
        $plan = (string) Arr::get($payload, 'data.object.metadata.plan');
        $billingPeriod = (string) Arr::get($payload, 'data.object.metadata.billing_period');

        if ($customerId === '' || $subscriptionId === '') {
            return;
        }

        $company = $this->syncCompanyByStripeCustomerId($customerId);
        if (! $company) {
            return;
        }

        if (in_array($plan, ['starter', 'pro', 'enterprise'], true)
            && in_array($billingPeriod, ['monthly', 'semiannual', 'annual'], true)) {
            $this->companySubscriptionService->syncBusinessSubscription(
                $company,
                $plan,
                $billingPeriod,
                $subscriptionId,
                'trialing'
            );
        } else {
            Subscription::query()->updateOrCreate(
                ['company_id' => $company->id],
                [
                    'stripe_subscription_id' => $subscriptionId,
                    'status' => 'trialing',
                    'cancel_at_period_end' => false,
                ]
            );
        }

        Log::info('ElectroFix billing notification: checkout completed', [
            'company_id' => $company->id,
            'stripe_subscription_id' => $subscriptionId,
        ]);
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

        Log::info('ElectroFix billing notification: payment succeeded', [
            'stripe_subscription_id' => $stripeSubscriptionId,
        ]);
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

        Log::warning('ElectroFix billing notification: payment failed', [
            'stripe_subscription_id' => $stripeSubscriptionId,
        ]);
    }

    private function onSubscriptionUpsert(array $payload): void
    {
        $stripeSubscriptionId = (string) Arr::get($payload, 'data.object.id');
        if ($stripeSubscriptionId === '') {
            return;
        }

        $status = (string) Arr::get($payload, 'data.object.status');
        $periodEndTs = Arr::get($payload, 'data.object.current_period_end');
        $cancelAtPeriodEnd = (bool) Arr::get($payload, 'data.object.cancel_at_period_end', false);
        $stripeCustomerId = (string) Arr::get($payload, 'data.object.customer');
        $priceId = (string) Arr::get($payload, 'data.object.items.data.0.price.id');

        $mappedStatus = match ($status) {
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'canceled',
            default => 'inactive',
        };

        $updates = [
            'status' => $mappedStatus,
            'current_period_end' => is_numeric($periodEndTs) ? now()->setTimestamp((int) $periodEndTs) : null,
            'cancel_at_period_end' => $cancelAtPeriodEnd,
        ];

        $price = $priceId !== '' ? PlanPrice::query()->where('stripe_price_id', $priceId)->with('plan')->first() : null;
        if ($price) {
            $updates['plan_id'] = $price->plan_id;
            $updates['plan'] = $price->plan->name;
            $updates['billing_period'] = $price->billing_period;
            $updates['billing_cycle'] = $price->billing_period === 'annual' ? 'yearly' : 'monthly';
        }

        Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->update($updates);

        if ($stripeCustomerId !== '') {
            $company = $this->syncCompanyByStripeCustomerId($stripeCustomerId);
            if ($company) {
                Subscription::query()->updateOrCreate(
                    ['company_id' => $company->id],
                    array_merge($updates, ['stripe_subscription_id' => $stripeSubscriptionId])
                );
            }
        }

        Log::info('ElectroFix billing notification: subscription updated', [
            'stripe_subscription_id' => $stripeSubscriptionId,
            'status' => $mappedStatus,
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

        Log::warning('ElectroFix billing notification: subscription canceled', [
            'stripe_subscription_id' => $stripeSubscriptionId,
        ]);
    }

    public function syncCompanyByStripeCustomerId(string $stripeCustomerId): ?Company
    {
        return Company::query()->where('stripe_id', $stripeCustomerId)->first();
    }
}
