<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['starter', 'pro', 'enterprise', 'developer_test']);

        return [
            'name' => $name,
            'is_public' => $name !== 'developer_test',
            'ai_enabled' => in_array($name, ['pro', 'enterprise', 'developer_test'], true),
            'max_ai_requests' => $name === 'starter' ? 0 : 100,
            'max_ai_tokens' => $name === 'starter' ? 0 : 60000,
            'overage_enabled' => $name === 'enterprise',
            'overage_price_per_request' => $name === 'enterprise' ? 2.0 : 0,
            'overage_price_per_1000_tokens' => 0,
            'stripe_overage_requests_price_id' => null,
            'stripe_overage_tokens_price_id' => null,
            'ai_provider_override' => null,
        ];
    }
}
