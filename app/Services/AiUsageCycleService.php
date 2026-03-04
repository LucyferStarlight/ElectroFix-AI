<?php

namespace App\Services;

use App\Models\Company;
use Carbon\Carbon;

class AiUsageCycleService
{
    public function currentCycle(Company $company, ?Carbon $now = null): array
    {
        $now ??= now();

        $anchor = $company->subscription?->starts_at
            ? Carbon::parse($company->subscription->starts_at)
            : $now->copy();

        $anchorDay = max(1, min(28, (int) $anchor->day));

        $cycleStart = Carbon::create($now->year, $now->month, $anchorDay, 0, 0, 0, $now->timezone);
        if ($now->lt($cycleStart)) {
            $cycleStart->subMonthNoOverflow();
        }

        $cycleEnd = $cycleStart->copy()->addMonthNoOverflow()->subDay();

        return [
            'start' => $cycleStart->startOfDay(),
            'end' => $cycleEnd->endOfDay(),
            'year_month' => $cycleStart->format('Y-m'),
        ];
    }
}
