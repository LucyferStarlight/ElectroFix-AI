<?php

namespace App\Services;

use App\Models\Company;
use Laravel\Cashier\Subscription;

class AiOverageMeteringService
{
    public function reportEnterpriseOverage(Company $company, int $requestsOverageIncrement, int $tokensOverageIncrement): void
    {
        if ($requestsOverageIncrement <= 0 && $tokensOverageIncrement <= 0) {
            return;
        }

        /** @var Subscription|null $stripeSubscription */
        $stripeSubscription = $company->subscriptions()->active()->where('type', 'default')->first();
        if (! $stripeSubscription) {
            return;
        }

        $items = $stripeSubscription->items;

        if ($requestsOverageIncrement > 0) {
            $requestItem = $items->firstWhere('stripe_price', $company->subscription?->planModel?->stripe_overage_requests_price_id);
            if ($requestItem) {
                $requestItem->reportUsage($requestsOverageIncrement);
            }
        }

        if ($tokensOverageIncrement > 0) {
            $tokenItem = $items->firstWhere('stripe_price', $company->subscription?->planModel?->stripe_overage_tokens_price_id);
            if ($tokenItem) {
                $tokenItem->reportUsage($tokensOverageIncrement);
            }
        }
    }
}
