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
        $startsAt = $this->promoStartsAt();
        if (! $startsAt) {
            return false;
        }

        $now ??= CarbonImmutable::now($startsAt->getTimezone());
        $endsAt = $startsAt
            ->addMonthsNoOverflow(max(1, (int) config('billing.trial_promo.duration_months', 6)))
            ->subSecond();

        return $now->betweenIncluded($startsAt, $endsAt);
    }

    public function promoStartsAt(): ?CarbonImmutable
    {
        $timezone = trim((string) config('billing.trial_promo.timezone', 'America/Mexico_City')) ?: 'America/Mexico_City';
        $startAt = trim((string) config('billing.trial_promo.start_at', ''));
        if ($startAt !== '') {
            return CarbonImmutable::parse($startAt, $timezone);
        }

        $startDate = trim((string) config('billing.trial_promo.start_date', ''));
        if ($startDate === '') {
            return null;
        }

        $startTime = trim((string) config('billing.trial_promo.start_time', '01:00:00')) ?: '01:00:00';

        return CarbonImmutable::parse($startDate.' '.$startTime, $timezone);
    }
}
