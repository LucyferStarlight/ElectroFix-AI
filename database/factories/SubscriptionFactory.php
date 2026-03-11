<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->startOfMonth();

        return [
            'company_id' => Company::factory(),
            'plan' => 'starter',
            'status' => 'active',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMonth()->endOfMonth(),
            'billing_cycle' => 'monthly',
            'user_limit' => 10,
        ];
    }
}
