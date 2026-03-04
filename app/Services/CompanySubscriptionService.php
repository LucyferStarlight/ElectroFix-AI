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
    public function __construct(private readonly PlanCatalogService $planCatalogService)
    {
    }

    public function checkout(Company $company, string $planName, string $billingPeriod, string $paymentMethod): Subscription
    {
        $price = $this->planCatalogService->resolvePrice($planName, $billingPeriod);
        $plan = $price->plan;

        $company->createOrGetStripeCustomer();
        $company->updateDefaultPaymentMethod($paymentMethod);

        $builder = $company->newSubscription('default', $price->stripe_price_id)
            ->trialDays((int) $price->trial_days);

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

        return $this->syncBusinessSubscription($company, $plan->name, $billingPeriod, $stripeSubscription->id);
    }

    public function requestChange(Company $company, string $planName, string $billingPeriod, User $actor): array
    {
        $price = $this->planCatalogService->resolvePrice($planName, $billingPeriod);
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
            $stripeSubscription?->stripe_id ?? $current->stripe_subscription_id
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
        $price = $plan->prices()
            ->where('billing_period', $pending->requested_billing_period)
            ->where('is_active', true)
            ->first();

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

    public function syncBusinessSubscription(Company $company, string $planName, string $billingPeriod, ?string $stripeSubscriptionId): Subscription
    {
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
                'status' => 'active',
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
