<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'owner_name' => fake()->name(),
            'owner_email' => fake()->unique()->safeEmail(),
            'owner_phone' => fake()->phoneNumber(),
            'tax_id' => fake()->bothify('RFC########'),
            'billing_email' => fake()->safeEmail(),
            'billing_phone' => fake()->phoneNumber(),
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'MX',
            'postal_code' => fake()->postcode(),
            'currency' => 'MXN',
            'notes' => fake()->sentence(),
        ];
    }
}
