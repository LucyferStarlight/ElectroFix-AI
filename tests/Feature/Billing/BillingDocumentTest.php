<?php

namespace Tests\Feature\Billing;

use App\Models\BillingDocument;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class BillingDocumentTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyWithRoles;

    public function test_can_create_sale_document_with_inventory_products(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles([], [], ['can_access_billing' => true]);
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $item = InventoryItem::factory()->create([
            'company_id' => $company->id,
            'quantity' => 10,
            'is_sale_enabled' => true,
        ]);

        $response = $this->actingAs($worker)
            ->post(route('worker.billing.store'), [
                'document_type' => 'invoice',
                'source' => 'sale',
                'customer_mode' => 'registered',
                'customer_id' => $customer->id,
                'tax_mode' => 'excluded',
                'items' => [
                    [
                        'item_kind' => 'product',
                        'description' => 'Venta de '.$item->name,
                        'quantity' => 2,
                        'unit_price' => 150,
                        'inventory_item_id' => $item->id,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('billing_documents', [
            'company_id' => $company->id,
            'source' => 'sale',
            'document_type' => 'invoice',
        ]);
        $this->assertDatabaseHas('billing_document_items', [
            'inventory_item_id' => $item->id,
            'item_kind' => 'product',
        ]);
    }

    public function test_cannot_sell_more_than_stock_available(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles([], [], ['can_access_billing' => true]);
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $item = InventoryItem::factory()->create([
            'company_id' => $company->id,
            'quantity' => 1,
            'is_sale_enabled' => true,
        ]);

        $response = $this->actingAs($worker)
            ->post(route('worker.billing.store'), [
                'document_type' => 'invoice',
                'source' => 'sale',
                'customer_mode' => 'registered',
                'customer_id' => $customer->id,
                'tax_mode' => 'excluded',
                'items' => [
                    [
                        'item_kind' => 'product',
                        'description' => 'Venta de '.$item->name,
                        'quantity' => 3,
                        'unit_price' => 150,
                        'inventory_item_id' => $item->id,
                    ],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_can_create_repair_document_associated_to_order(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles([], [], ['can_access_billing' => true]);
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'status' => OrderStatus::RECEIVED,
        ]);

        $response = $this->actingAs($worker)
            ->post(route('worker.billing.store'), [
                'document_type' => 'invoice',
                'source' => 'repair',
                'customer_mode' => 'registered',
                'customer_id' => $customer->id,
                'tax_mode' => 'excluded',
                'repair_outcome' => 'repaired',
                'work_performed' => 'Reemplazo de componente y pruebas finales.',
                'actual_amount_charged' => 500,
                'items' => [
                    [
                        'item_kind' => 'service',
                        'description' => 'Servicio de reparación',
                        'quantity' => 1,
                        'unit_price' => 500,
                        'order_id' => $order->id,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('billing_document_items', [
            'order_id' => $order->id,
            'item_kind' => 'service',
        ]);
    }

    public function test_can_create_mixed_document(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles([], [], ['can_access_billing' => true]);
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);
        $item = InventoryItem::factory()->create([
            'company_id' => $company->id,
            'quantity' => 10,
            'is_sale_enabled' => true,
        ]);

        $response = $this->actingAs($worker)
            ->post(route('worker.billing.store'), [
                'document_type' => 'invoice',
                'source' => 'mixed',
                'customer_mode' => 'registered',
                'customer_id' => $customer->id,
                'tax_mode' => 'excluded',
                'repair_outcome' => 'partial',
                'outcome_notes' => 'Cliente autorizó solo reparación parcial.',
                'work_performed' => 'Ajuste y reemplazo de componente principal.',
                'actual_amount_charged' => 520,
                'items' => [
                    [
                        'item_kind' => 'service',
                        'description' => 'Servicio mixto',
                        'quantity' => 1,
                        'unit_price' => 400,
                        'order_id' => $order->id,
                    ],
                    [
                        'item_kind' => 'product',
                        'description' => 'Producto mixto',
                        'quantity' => 1,
                        'unit_price' => 120,
                        'inventory_item_id' => $item->id,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('billing_documents', [
            'company_id' => $company->id,
            'source' => 'mixed',
        ]);
    }

    public function test_stock_is_decremented_on_sale_invoice(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles([], [], ['can_access_billing' => true]);
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $item = InventoryItem::factory()->create([
            'company_id' => $company->id,
            'quantity' => 5,
            'is_sale_enabled' => true,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.billing.store'), [
                'document_type' => 'invoice',
                'source' => 'sale',
                'customer_mode' => 'registered',
                'customer_id' => $customer->id,
                'tax_mode' => 'excluded',
                'items' => [
                    [
                        'item_kind' => 'product',
                        'description' => 'Venta de '.$item->name,
                        'quantity' => 2,
                        'unit_price' => 100,
                        'inventory_item_id' => $item->id,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_vat_is_calculated_based_on_company_configuration(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles(['vat_percentage' => 16.00], [], ['can_access_billing' => true]);
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $item = InventoryItem::factory()->create([
            'company_id' => $company->id,
            'quantity' => 5,
            'is_sale_enabled' => true,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.billing.store'), [
                'document_type' => 'invoice',
                'source' => 'sale',
                'customer_mode' => 'registered',
                'customer_id' => $customer->id,
                'tax_mode' => 'excluded',
                'items' => [
                    [
                        'item_kind' => 'product',
                        'description' => 'Venta de '.$item->name,
                        'quantity' => 1,
                        'unit_price' => 100,
                        'inventory_item_id' => $item->id,
                    ],
                ],
            ])
            ->assertRedirect();

        $document = BillingDocument::query()->where('company_id', $company->id)->latest()->firstOrFail();

        $this->assertSame(100.00, (float) $document->subtotal);
        $this->assertSame(16.00, (float) $document->vat_amount);
        $this->assertSame(116.00, (float) $document->total);
    }

    public function test_billing_document_does_not_require_cfdi_fields(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles([], [], ['can_access_billing' => true]);
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $item = InventoryItem::factory()->create([
            'company_id' => $company->id,
            'quantity' => 5,
            'is_sale_enabled' => true,
        ]);

        $response = $this->actingAs($worker)
            ->post(route('worker.billing.store'), [
                'document_type' => 'invoice',
                'source' => 'sale',
                'customer_mode' => 'registered',
                'customer_id' => $customer->id,
                'tax_mode' => 'excluded',
                'items' => [
                    [
                        'item_kind' => 'product',
                        'description' => 'Venta sin CFDI',
                        'quantity' => 1,
                        'unit_price' => 80,
                        'inventory_item_id' => $item->id,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('billing_documents', [
            'company_id' => $company->id,
            'source' => 'sale',
        ]);
    }
}
