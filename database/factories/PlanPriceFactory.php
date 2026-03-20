<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanPriceFactory extends Factory
{
    protected $model = PlanPrice::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'stripe_price_id' => 'price_test_'.$this->faker->unique()->numerify('########'),
            'billing_period' => 'monthly',
            'currency' => 'mxn',
            'amount' => null,
            'trial_days' => 7,
            'is_active' => true,
        ];
    }
}
