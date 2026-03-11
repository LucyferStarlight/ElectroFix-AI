<?php

namespace Database\Factories;

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
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'equipment_id' => Equipment::factory(),
            'technician' => fake()->name(),
            'symptoms' => 'No enciende',
            'status' => 'received',
            'estimated_cost' => 1200,
        ];
    }
}
