<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CompanySubscriptionService;
use App\Services\PlanCatalogService;
use Illuminate\Http\RedirectResponse;

class StripeSubscriptionService
{
    public function __construct(
        private readonly CompanySubscriptionService $companySubscriptionService,
        private readonly PlanCatalogService $planCatalogService
    ) {
    }

    public function checkoutHosted(Company $company, string $plan, string $period)
    {
        $price = $this->planCatalogService->resolvePrice($plan, $period, (string) $company->currency);
        $hasHadSubscription = Subscription::query()
            ->where('company_id', $company->id)
            ->exists();

        $company->createOrGetStripeCustomer();

        $checkoutData = [
            'success_url' => route('billing.success'),
            'cancel_url' => route('billing.cancel'),
            'client_reference_id' => (string) $company->id,
            'metadata' => [
                'company_id' => (string) $company->id,
                'plan' => $plan,
                'billing_period' => $period,
            ],
            'subscription_data' => [
                'metadata' => [
                    'company_id' => (string) $company->id,
                    'plan' => $plan,
                    'billing_period' => $period,
                ],
            ],
        ];

        if (! $hasHadSubscription && (int) $price->trial_days > 0) {
            $checkoutData['subscription_data']['trial_period_days'] = (int) $price->trial_days;
        }

        return $company
            ->newSubscription('default', $price->stripe_price_id)
            ->checkout($checkoutData);
    }

    public function checkoutDirect(Company $company, string $planName, string $billingPeriod, string $paymentMethod): Subscription
    {
        return $this->companySubscriptionService->checkout($company, $planName, $billingPeriod, $paymentMethod);
    }

    public function requestPlanChange(Company $company, string $planName, string $billingPeriod, User $actor): array
    {
        return $this->companySubscriptionService->requestChange($company, $planName, $billingPeriod, $actor);
    }

    public function cancelAtPeriodEnd(Company $company): Subscription
    {
        return $this->companySubscriptionService->cancelAtPeriodEnd($company);
    }

    public function portal(Company $company): RedirectResponse
    {
        $company->createOrGetStripeCustomer();

        return $company->redirectToBillingPortal(route('admin.subscription.edit'));
    }
}
