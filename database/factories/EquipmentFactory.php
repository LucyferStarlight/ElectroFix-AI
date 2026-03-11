<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'type' => fake()->randomElement(['Lavadora', 'Microondas', 'Refrigerador']),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('MDL-###??')),
            'serial_number' => strtoupper(fake()->bothify('SN-####-????')),
        ];
    }
}
