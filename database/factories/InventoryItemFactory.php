<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'company_id' => Company::factory(),
            'name' => ucfirst($name),
            'internal_code' => strtoupper(Str::random(8)),
            'quantity' => fake()->numberBetween(5, 25),
            'low_stock_threshold' => fake()->numberBetween(1, 5),
            'is_sale_enabled' => true,
            'sale_price' => fake()->randomFloat(2, 50, 500),
        ];
    }
}
