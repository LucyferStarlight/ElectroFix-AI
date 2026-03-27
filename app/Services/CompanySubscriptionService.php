<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionChangeRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompanySubscriptionService
{
    public function __construct(
        private readonly PlanCatalogService $planCatalogService,
        private readonly TrialPolicyService $trialPolicyService
    ) {
    }

    public function checkout(Company $company, string $planName, string $billingPeriod, string $paymentMethod): Subscription
    {
        if (trim($paymentMethod) === '') {
            abort(422, 'Debes seleccionar un método de pago válido.');
        }

        $existingBusiness = $company->subscription;
        $existingStripe = $company->subscription('default');
        if ($existingBusiness
            && in_array($existingBusiness->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING, Subscription::STATUS_PAST_DUE], true)
            && $existingStripe
            && ! $existingStripe->canceled()) {
            return $existingBusiness;
        }

        $this->deactivateActiveBusinessSubscription($company);

        $price = $this->planCatalogService->resolvePrice($planName, $billingPeriod, (string) $company->currency);
        $plan = $price->plan;
        $trialDays = $this->trialPolicyService->trialDaysForPrice($price);

        $company->createOrGetStripeCustomer();
        $company->updateDefaultPaymentMethod($paymentMethod);

        $builder = $company->newSubscription('default', $price->stripe_price_id)
            ->trialDays($trialDays);

        if ($plan->overage_enabled) {
            if ($plan->stripe_overage_requests_price_id) {
                $builder->price($plan->stripe_overage_requests_price_id);
            }
            if ($plan->stripe_overage_tokens_price_id) {
                $builder->price($plan->stripe_overage_tokens_price_id);
            }
        }

        $stripeSubscription = $builder->create($paymentMethod, [
            'email' => $company->stripeEmail(),
            'name' => $company->stripeName(),
        ]);

        $businessStatus = match ($stripeSubscription->stripe_status) {
            'trialing' => Subscription::STATUS_TRIALING,
            'active' => Subscription::STATUS_ACTIVE,
            'past_due' => Subscription::STATUS_PAST_DUE,
            'canceled' => Subscription::STATUS_CANCELED,
            default => Subscription::STATUS_INACTIVE,
        };

        return $this->syncBusinessSubscription($company, $plan->name, $billingPeriod, $stripeSubscription->id, $businessStatus);
    }

    public function requestChange(Company $company, string $planName, string $billingPeriod, User $actor): array
    {
        $price = $this->planCatalogService->resolvePrice($planName, $billingPeriod, (string) $company->currency);
        $current = $company->subscription;

        if (! $current || ! $current->stripe_subscription_id) {
            $defaultPaymentMethod = (string) ($company->defaultPaymentMethod()?->id ?? '');
            if ($defaultPaymentMethod === '') {
                abort(422, 'No hay método de pago configurado para crear la suscripción.');
            }

            $updated = $this->checkout($company, $planName, $billingPeriod, $defaultPaymentMethod);

            return ['mode' => 'created', 'subscription' => $updated, 'change' => null];
        }

        $isDowngrade = $this->isDowngrade($current->plan, $planName);

        if ($isDowngrade) {
            $change = SubscriptionChangeRequest::query()->create([
                'company_id' => $company->id,
                'requested_plan_id' => $price->plan_id,
                'requested_billing_period' => $billingPeriod,
                'effective_at' => $current->current_period_end ?? now()->addMonth(),
                'status' => 'pending',
                'requested_by_user_id' => $actor->id,
            ]);

            return ['mode' => 'deferred', 'subscription' => $current, 'change' => $change];
        }

        $stripeSubscription = $company->subscription('default');
        if ($stripeSubscription) {
            $stripeSubscription->swap($price->stripe_price_id);
        }

        $updated = $this->syncBusinessSubscription(
            $company,
            $planName,
            $billingPeriod,
            $stripeSubscription?->stripe_id ?? $current->stripe_subscription_id,
            $stripeSubscription?->stripe_status === 'trialing' ? 'trialing' : 'active'
        );

        return ['mode' => 'immediate', 'subscription' => $updated, 'change' => null];
    }

    public function cancelAtPeriodEnd(Company $company): Subscription
    {
        $stripeSubscription = $company->subscription('default');
        if ($stripeSubscription) {
            $stripeSubscription->cancel();
        }

        $business = $company->subscription;
        $business?->update(['cancel_at_period_end' => true]);

        return $business->fresh();
    }

    public function applyDueChanges(Company $company): ?Subscription
    {
        $pending = SubscriptionChangeRequest::query()
            ->where('company_id', $company->id)
            ->where('status', 'pending')
            ->where('effective_at', '<=', now())
            ->oldest('effective_at')
            ->first();

        if (! $pending) {
            return null;
        }

        $business = $company->subscription;
        if (! $business) {
            return null;
        }

        $plan = $pending->requestedPlan;
        $currency = strtolower((string) ($company->currency ?: 'mxn'));
        $price = $plan->prices()
            ->where('billing_period', $pending->requested_billing_period)
            ->where('is_active', true)
            ->where('currency', $currency)
            ->first();

        if (! $price) {
            $price = $plan->prices()
                ->where('billing_period', $pending->requested_billing_period)
                ->where('is_active', true)
                ->where('currency', 'mxn')
                ->first();
        }

        if (! $price) {
            return null;
        }

        DB::transaction(function () use ($company, $business, $pending, $price, $plan): void {
            $stripeSubscription = $company->subscription('default');
            if ($stripeSubscription) {
                $stripeSubscription->swap($price->stripe_price_id);
            }

            $business->update([
                'plan' => $plan->name,
                'plan_id' => $plan->id,
                'billing_period' => $pending->requested_billing_period,
                'status' => 'active',
            ]);

            $pending->update(['status' => 'applied']);
        });

        return $company->subscription()->first();
    }

    public function syncBusinessSubscription(
        Company $company,
        string $planName,
        string $billingPeriod,
        ?string $stripeSubscriptionId,
        string $status = 'active'
    ): Subscription {
        $plan = $this->planCatalogService->resolvePlan($planName);
        $periodEnd = match ($billingPeriod) {
            'semiannual' => now()->addMonthsNoOverflow(6),
            'annual' => now()->addYearNoOverflow(),
            default => now()->addMonthNoOverflow(),
        };

        $business = Subscription::query()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'plan' => $planName,
                'plan_id' => $plan->id,
                'status' => $status,
                'billing_period' => $billingPeriod,
                'billing_cycle' => $billingPeriod === 'annual' ? 'yearly' : 'monthly',
                'stripe_subscription_id' => $stripeSubscriptionId,
                'starts_at' => Carbon::today(),
                'ends_at' => $periodEnd->toDateString(),
                'current_period_end' => $periodEnd,
                'cancel_at_period_end' => false,
            ]
        );

        return $business;
    }


    private function deactivateActiveBusinessSubscription(Company $company): void
    {
        $business = $company->subscription;

        if (! $business || ! in_array($business->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING], true)) {
            return;
        }

        $stripeSubscription = $company->subscription('default');
        if ($stripeSubscription && ! $stripeSubscription->canceled()) {
            $stripeSubscription->cancelNow();
        }

        $business->update([
            'status' => Subscription::STATUS_INACTIVE,
            'cancel_at_period_end' => false,
        ]);
    }

    private function isDowngrade(string $currentPlan, string $requestedPlan): bool
    {
        $weight = [
            'starter' => 1,
            'pro' => 2,
            'enterprise' => 3,
            'developer_test' => 4,
        ];

        return ($weight[$requestedPlan] ?? 0) < ($weight[$currentPlan] ?? 0);
    }
}
