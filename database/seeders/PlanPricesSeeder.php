<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Seeder;

class PlanPricesSeeder extends Seeder
{
    public function run(): void
    {
        $periods = [
            'monthly' => 7,
            'semiannual' => 15,
            'annual' => 15,
        ];
        foreach (['starter', 'pro', 'enterprise'] as $planName) {
            $plan = Plan::query()->where('name', $planName)->first();
            if (! $plan) {
                continue;
            }

            foreach ($periods as $period => $trialDays) {
                $legacyKey = strtoupper(sprintf('STRIPE_PRICE_%s_%s', $planName, $period));
                $shortKey = strtoupper(sprintf('%s_%s', $planName, $period));
                $priceId = (string) (env($legacyKey) ?: env($shortKey) ?: sprintf('price_placeholder_%s_%s', $planName, $period));
                $amount = $this->resolveAmount($planName, $period);

                PlanPrice::query()->updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'billing_period' => $period,
                        'currency' => 'mxn',
                    ],
                    [
                        'stripe_price_id' => $priceId,
                        'trial_days' => $trialDays,
                        'is_active' => true,
                        'amount' => $amount,
                    ]
                );
            }
        }
    }

    private function resolveAmount(string $planName, string $period): ?float
    {
        $key = strtoupper(sprintf('PLAN_AMOUNT_%s_%s', $planName, $period));
        $val = env($key);

        return is_numeric($val) ? (float) $val : null;
    }
}
