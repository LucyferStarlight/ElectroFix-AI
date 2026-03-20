<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PlanPrice;
use App\Models\StripeWebhookEvent;
use App\Models\Subscription;
use App\Models\User;
use App\Mail\CompanyWelcomeMail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StripeWebhookService
{
    // Cashier gestiona el modelo de suscripciones; este servicio procesa eventos del webhook de Stripe.
    public function __construct(
        private readonly CompanySubscriptionService $companySubscriptionService,
        private readonly StripeSignupService $stripeSignupService
    ) {
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

        if (! $stored->wasRecentlyCreated
            && $stored->status === 'processing'
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
                    'invoice.payment_succeeded',
                    'invoice.paid' => $this->onInvoicePaid($eventPayload),
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
                'invoice.paid',
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
        $companyId = (int) Arr::get($payload, 'data.object.metadata.company_id', 0);

        if ($customerId === '' || $subscriptionId === '') {
            return;
        }

        $company = $companyId > 0 ? Company::query()->find($companyId) : null;
        if (! $company) {
            $company = $this->syncCompanyByStripeCustomerId($customerId);
        }
        if (! $company && (string) Arr::get($payload, 'data.object.metadata.signup_source') === 'public_landing') {
            $company = $this->stripeSignupService->createOrSyncFromCheckout($payload);
        }

        if (! $company) {
            return;
        }

        $existingByStripeId = Subscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if ($existingByStripeId && $existingByStripeId->company_id !== $company->id) {
            Log::warning('Stripe checkout completed ignored due to subscription ownership mismatch', [
                'company_id' => $company->id,
                'existing_company_id' => $existingByStripeId->company_id,
                'stripe_subscription_id' => $subscriptionId,
            ]);

            return;
        }

        if ($existingByStripeId) {
            $updates = [
                'cancel_at_period_end' => false,
            ];

            if ($existingByStripeId->status === Subscription::STATUS_INACTIVE) {
                $updates['status'] = Subscription::STATUS_TRIALING;
            }

            $existingByStripeId->update($updates);

            Log::info('ElectroFix billing notification: checkout already synchronized', [
                'company_id' => $company->id,
                'stripe_subscription_id' => $subscriptionId,
            ]);

            return;
        }

        if (in_array($plan, ['starter', 'pro', 'enterprise'], true)
            && in_array($billingPeriod, ['monthly', 'semiannual', 'annual'], true)) {
            $this->companySubscriptionService->syncBusinessSubscription(
                $company,
                $plan,
                $billingPeriod,
                $subscriptionId,
                Subscription::STATUS_TRIALING
            );
        } else {
            Subscription::query()->updateOrCreate(
                ['company_id' => $company->id],
                [
                    'stripe_subscription_id' => $subscriptionId,
                    'status' => Subscription::STATUS_TRIALING,
                    'cancel_at_period_end' => false,
                ]
            );
        }

        if ($company->stripe_id !== $customerId) {
            $company->update(['stripe_id' => $customerId]);
        }

        $company->update([
            'status' => 'active',
            'pending_attempts' => 0,
            'pending_expires_at' => null,
            'pending_last_failed_at' => null,
            'pending_plan' => null,
            'pending_billing_period' => null,
            'stripe_checkout_session_id' => null,
        ]);

        $admin = User::query()
            ->where('company_id', $company->id)
            ->where('role', 'admin')
            ->first();

        if ($admin) {
            Mail::to($admin->email)->queue(new CompanyWelcomeMail($company, $plan ?: $company->subscription?->plan ?? 'starter'));
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
            ->update(['status' => Subscription::STATUS_ACTIVE]);

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

        $subscription = Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if ($subscription) {
            $subscription->update(['status' => Subscription::STATUS_PAST_DUE]);
        }

        $company = $subscription?->company;
        if ($company) {
            $attempts = (int) $company->pending_attempts + 1;

            if ($attempts >= 4) {
                User::query()->where('company_id', $company->id)->forceDelete();
                $company->delete();

                return;
            }

            $days = match ($attempts) {
                1 => 7,
                2 => 4,
                3 => 1,
                default => 1,
            };

            $company->update([
                'status' => 'suspended',
                'pending_attempts' => $attempts,
                'pending_last_failed_at' => now(),
                'pending_expires_at' => now()->addDays($days),
                'pending_plan' => $company->pending_plan ?: $subscription?->plan,
                'pending_billing_period' => $company->pending_billing_period ?: $subscription?->billing_period,
            ]);
        }

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
            'trialing' => Subscription::STATUS_TRIALING,
            'active' => Subscription::STATUS_ACTIVE,
            'past_due' => Subscription::STATUS_PAST_DUE,
            'canceled' => Subscription::STATUS_CANCELED,
            default => Subscription::STATUS_INACTIVE,
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

        $company = null;
        if ($stripeCustomerId !== '') {
            $company = $this->syncCompanyByStripeCustomerId($stripeCustomerId);
        }

        $existing = Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if ($existing) {
            if ($company && $existing->company_id !== $company->id) {
                Log::warning('Stripe subscription update ignored due to subscription ownership mismatch', [
                    'company_id' => $company->id,
                    'existing_company_id' => $existing->company_id,
                    'stripe_subscription_id' => $stripeSubscriptionId,
                ]);

                return;
            }

            $existing->update($updates);
        } elseif ($company) {
            Subscription::query()->updateOrCreate(
                ['company_id' => $company->id],
                array_merge($updates, ['stripe_subscription_id' => $stripeSubscriptionId])
            );
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
                'status' => Subscription::STATUS_CANCELED,
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
