<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PlanCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'starter',
                'is_public' => true,
                'ai_enabled' => true,
                'max_ai_requests' => 10,
                'max_ai_tokens' => 8000,
                'overage_enabled' => false,
                'overage_price_per_request' => null,
                'overage_price_per_1000_tokens' => null,
            ],
            [
                'name' => 'pro',
                'is_public' => true,
                'ai_enabled' => true,
                'max_ai_requests' => 75,
                'max_ai_tokens' => 50000,
                'overage_enabled' => false,
                'overage_price_per_request' => null,
                'overage_price_per_1000_tokens' => null,
            ],
            [
                'name' => 'enterprise',
                'is_public' => true,
                'ai_enabled' => true,
                'max_ai_requests' => 200,
                'max_ai_tokens' => 120000,
                'overage_enabled' => true,
                'overage_price_per_request' => 2.00,
                'overage_price_per_1000_tokens' => 0.00,
                'stripe_overage_requests_price_id' => env('AI_EXTRA_QUERY_PRICE_ID')
                    ?: env('STRIPE_ENTERPRISE_OVERAGE_REQUESTS_PRICE_ID')
                    ?: env('stripe_overage_requests_price_id'),
                'stripe_overage_tokens_price_id' => null,
            ],
            [
                'name' => 'developer_test',
                'is_public' => false,
                'ai_enabled' => true,
                'max_ai_requests' => 300,
                'max_ai_tokens' => 500000,
                'overage_enabled' => false,
                'overage_price_per_request' => null,
                'overage_price_per_1000_tokens' => null,
            ],
        ];

        foreach ($plans as $payload) {
            $plan = Plan::query()->updateOrCreate(['name' => $payload['name']], $payload);

            if (! in_array($plan->name, ['starter', 'pro', 'enterprise'], true)) {
                continue;
            }

            $prices = [
                ['billing_period' => 'monthly', 'trial_days' => 7],
                ['billing_period' => 'semiannual', 'trial_days' => 15],
                ['billing_period' => 'annual', 'trial_days' => 15],
            ];

            foreach ($prices as $price) {
                $priceId = $this->resolvePriceId($plan->name, $price['billing_period']);

                PlanPrice::query()->updateOrCreate(
                    ['plan_id' => $plan->id, 'billing_period' => $price['billing_period'], 'currency' => 'mxn'],
                    [
                        'stripe_price_id' => $priceId,
                        'trial_days' => $price['trial_days'],
                        'is_active' => true,
                        'currency' => 'mxn',
                    ]
                );
            }
        }

        $planMap = Plan::query()->pluck('id', 'name');
        foreach ($planMap as $name => $id) {
            DB::table('company_subscriptions')
                ->where('plan', $name)
                ->whereNull('plan_id')
                ->update(['plan_id' => $id]);
        }
    }

    /**
     * Admite tanto STRIPE_PRICE_* como formato corto STARTER_MONTHLY.
     */
    private function resolvePriceId(string $planName, string $period): string
    {
        $legacyKey = strtoupper(sprintf('STRIPE_PRICE_%s_%s', $planName, $period));
        $shortKey = strtoupper(sprintf('%s_%s', $planName, $period));

        return (string) (env($legacyKey) ?: env($shortKey) ?: sprintf('price_placeholder_%s_%s', $planName, $period));
    }
}
