<?php

namespace App\Services\Billing;

use App\Models\PlanPrice as PlanPriceModel;

class PlanPrice
{
    public static function get(string $plan, string $period, string $currency = 'mxn'): ?string
    {
        $currency = strtolower(trim($currency)) ?: 'mxn';

        $query = PlanPriceModel::query()
            ->whereHas('plan', fn ($q) => $q->where('name', $plan))
            ->where('billing_period', $period)
            ->where('is_active', true);

        $price = $query->where('currency', $currency)->value('stripe_price_id');

        if ($price) {
            return $price;
        }

        return $query->where('currency', 'mxn')->value('stripe_price_id');
    }
}
