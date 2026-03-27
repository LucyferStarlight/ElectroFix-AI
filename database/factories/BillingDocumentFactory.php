<?php

namespace Database\Factories;

use App\Models\BillingDocument;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillingDocument>
 */
class BillingDocumentFactory extends Factory
{
    protected $model = BillingDocument::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory()->state([
                'role' => 'worker',
            ]),
            'customer_id' => Customer::factory(),
            'order_id' => null,
            'document_number' => 'DOC-TEST-'.fake()->unique()->numberBetween(1000, 9999),
            'document_type' => 'invoice',
            'version' => 1,
            'status' => 'approved',
            'is_active' => false,
            'customer_mode' => 'registered',
            'walk_in_name' => null,
            'source' => 'mixed',
            'tax_mode' => 'excluded',
            'vat_percentage' => 16.00,
            'subtotal' => 0,
            'vat_amount' => 0,
            'total' => 0,
            'notes' => null,
            'issued_at' => now(),
        ];
    }
}
