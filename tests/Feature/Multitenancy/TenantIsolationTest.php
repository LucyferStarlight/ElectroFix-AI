<?php

namespace Tests\Feature\Multitenancy;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class TenantIsolationTest extends TestCase
{
    use CreatesCompanyWithRoles;
    use RefreshDatabase;

    public function test_admin_cannot_view_other_company_customers(): void
    {
        [$companyA, $adminA] = $this->createCompanyWithRoles();
        [$companyB, $adminB] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($companyA);
        $this->createActiveSubscription($companyB);

        Customer::factory()->create(['company_id' => $companyA->id, 'name' => 'Cliente A']);
        Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Cliente B']);

        $response = $this->actingAs($adminA)->get(route('worker.customers'));

        $response->assertOk();
        $response->assertSee('Cliente A');
        $response->assertDontSee('Cliente B');
    }

    public function test_admin_cannot_edit_order_from_other_company(): void
    {
        [$companyA, $adminA] = $this->createCompanyWithRoles();
        [$companyB, $adminB] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($companyA);
        $this->createActiveSubscription($companyB);

        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);
        $equipmentB = Equipment::factory()->create(['company_id' => $companyB->id, 'customer_id' => $customerB->id]);
        $orderB = Order::factory()->create([
            'company_id' => $companyB->id,
            'customer_id' => $customerB->id,
            'equipment_id' => $equipmentB->id,
        ]);

        $response = $this->actingAs($adminA)
            ->patch(route('worker.orders.status', $orderB), [
                'status' => 'completed',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_view_other_company_inventory(): void
    {
        [$companyA, $adminA] = $this->createCompanyWithRoles();
        [$companyB, $adminB] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($companyA);
        $this->createActiveSubscription($companyB);

        InventoryItem::factory()->create(['company_id' => $companyA->id, 'name' => 'Item A']);
        InventoryItem::factory()->create(['company_id' => $companyB->id, 'name' => 'Item B']);

        $response = $this->actingAs($adminA)->get(route('worker.inventory'));

        $response->assertOk();
        $response->assertSee('Item A');
        $response->assertDontSee('Item B');
    }

    public function test_developer_can_view_data_from_both_companies(): void
    {
        [$companyA] = $this->createCompanyWithRoles();
        [$companyB] = $this->createCompanyWithRoles();

        Customer::factory()->create(['company_id' => $companyA->id, 'name' => 'Cliente A']);
        Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Cliente B']);

        $developer = User::factory()->create(['role' => 'developer']);

        $response = $this->actingAs($developer)->get(route('worker.customers'));

        $response->assertOk();
        $response->assertSee('Cliente A');
        $response->assertSee('Cliente B');
    }
}
