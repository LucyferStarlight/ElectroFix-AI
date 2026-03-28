<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => static fn (): int => Company::factory()->create()->id,
            'customer_id' => static fn (array $attributes): int => Customer::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->id,
            'equipment_id' => static fn (array $attributes): int => Equipment::factory()->create([
                'company_id' => $attributes['company_id'],
                'customer_id' => $attributes['customer_id'],
            ])->id,
            'technician' => fake()->name(),
            'symptoms' => 'No enciende',
            'status' => OrderStatus::CREATED->value,
            'payment_status' => 'pending',
            'total_paid' => 0,
            'estimated_cost' => 1200,
        ];
    }
}
