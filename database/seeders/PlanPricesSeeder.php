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
                $envKey = strtoupper(sprintf('STRIPE_PRICE_%s_%s', $planName, $period));
                $priceId = (string) env($envKey, sprintf('price_placeholder_%s_%s', $planName, $period));

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
                    ]
                );
            }
        }
    }
}

