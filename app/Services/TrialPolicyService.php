<?php

namespace App\Services;

use App\Models\PlanPrice;
use Carbon\CarbonImmutable;

class TrialPolicyService
{
    public function trialDaysForPrice(PlanPrice $price, ?CarbonImmutable $now = null): int
    {
        if (! $this->promoWindowIsActive($now)) {
            return 0;
        }

        return max(0, (int) $price->trial_days);
    }

    public function promoWindowIsActive(?CarbonImmutable $now = null): bool
    {
        $startDate = trim((string) config('billing.trial_promo.start_date', ''));
        if ($startDate === '') {
            return false;
        }

        $now ??= CarbonImmutable::now();
        $startsAt = CarbonImmutable::parse($startDate)->startOfDay();
        $endsAt = $startsAt
            ->addMonthsNoOverflow(max(1, (int) config('billing.trial_promo.duration_months', 6)))
            ->subSecond();

        return $now->betweenIncluded($startsAt, $endsAt);
    }
}
