<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class PlanCatalogService
{
    public function publicPlans()
    {
        try {
            return Plan::query()
                ->where('is_public', true)
                ->with(['prices' => fn ($q) => $q->where('is_active', true)->orderBy('billing_period')])
                ->orderBy('id')
                ->get();
        } catch (QueryException) {
            return new Collection();
        }
    }

    public function resolvePlan(string $name): Plan
    {
        return Plan::query()->where('name', $name)->firstOrFail();
    }

    public function resolvePrice(string $planName, string $billingPeriod, string $currency = 'mxn'): PlanPrice
    {
        $currency = strtolower(trim($currency)) ?: 'mxn';

        $query = PlanPrice::query()
            ->whereHas('plan', fn ($q) => $q->where('name', $planName))
            ->where('billing_period', $billingPeriod)
            ->where('is_active', true);

        $price = $query->where('currency', $currency)->first();

        if ($price) {
            return $price;
        }

        return $query->where('currency', 'mxn')->firstOrFail();
    }
}
