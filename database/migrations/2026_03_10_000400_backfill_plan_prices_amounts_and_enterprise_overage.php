<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans') || ! Schema::hasTable('plan_prices')) {
            return;
        }

        $amounts = [
            'starter' => [
                'monthly' => 399,
                'semiannual' => 2100,
                'annual' => 3900,
            ],
            'pro' => [
                'monthly' => 699,
                'semiannual' => 3900,
                'annual' => 7500,
            ],
            'enterprise' => [
                'monthly' => 1199,
                'semiannual' => 6900,
                'annual' => 12900,
            ],
        ];

        $planIds = DB::table('plans')
            ->whereIn('name', array_keys($amounts))
            ->pluck('id', 'name');

        foreach ($amounts as $planName => $periods) {
            $planId = $planIds[$planName] ?? null;
            if (! $planId) {
                continue;
            }

            foreach ($periods as $period => $amount) {
                DB::table('plan_prices')
                    ->where('plan_id', $planId)
                    ->where('billing_period', $period)
                    ->where('currency', 'mxn')
                    ->update(['amount' => $amount]);
            }
        }

        DB::table('plans')
            ->where('name', 'pro')
            ->update([
                'max_ai_requests' => 100,
                'max_ai_tokens' => 60000,
            ]);

        DB::table('plans')
            ->where('name', 'enterprise')
            ->update([
                'max_ai_requests' => 200,
                'max_ai_tokens' => 120000,
                'overage_enabled' => true,
                'overage_price_per_request' => 2.00,
                'overage_price_per_1000_tokens' => 0.00,
                'stripe_overage_requests_price_id' => env('AI_EXTRA_QUERY_PRICE_ID')
                    ?: env('STRIPE_ENTERPRISE_OVERAGE_REQUESTS_PRICE_ID')
                    ?: env('stripe_overage_requests_price_id'),
                'stripe_overage_tokens_price_id' => null,
            ]);
    }

    public function down(): void
    {
        // Non-reversible backfill by design.
    }
};
