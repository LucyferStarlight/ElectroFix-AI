<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanPrice;

class PlanCatalogService
{
    public function publicPlans()
    {
        return Plan::query()
            ->where('is_public', true)
            ->with(['prices' => fn ($q) => $q->where('is_active', true)->orderBy('billing_period')])
            ->orderBy('id')
            ->get();
    }

    public function resolvePlan(string $name): Plan
    {
        return Plan::query()->where('name', $name)->firstOrFail();
    }

    public function resolvePrice(string $planName, string $billingPeriod): PlanPrice
    {
        return PlanPrice::query()
            ->whereHas('plan', fn ($q) => $q->where('name', $planName))
            ->where('billing_period', $billingPeriod)
            ->where('is_active', true)
            ->firstOrFail();
    }
}
