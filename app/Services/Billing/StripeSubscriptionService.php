<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CompanySubscriptionService;
use App\Services\PlanCatalogService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Cashier\Checkout;
use App\Services\Billing\PlanPrice;

class StripeSubscriptionService
{
    public function __construct(
        private readonly CompanySubscriptionService $companySubscriptionService,
        private readonly PlanCatalogService $planCatalogService
    ) {
    }

   public function checkoutHosted(Company $company, string $plan, string $period)
    {
        $priceId = PlanPrice::get($plan, $period);

        if (!$priceId) {
            throw new \Exception("Plan o periodo inválido");
        }

        return $company
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('billing.success'),
                'cancel_url' => route('billing.cancel'),
            ]);
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
