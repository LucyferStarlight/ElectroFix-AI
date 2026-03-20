<?php

namespace Database\Factories;

use App\Models\BillingDocument;
use App\Models\BillingDocumentItem;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillingDocumentItem>
 */
class BillingDocumentItemFactory extends Factory
{
    protected $model = BillingDocumentItem::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 5);
        $unitPrice = fake()->randomFloat(2, 50, 300);
        $lineSubtotal = round($quantity * $unitPrice, 2);
        $lineVat = round($lineSubtotal * 0.16, 2);

        return [
            'billing_document_id' => BillingDocument::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'order_id' => null,
            'item_kind' => 'product',
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_subtotal' => $lineSubtotal,
            'line_vat' => $lineVat,
            'line_total' => round($lineSubtotal + $lineVat, 2),
        ];
    }
}
