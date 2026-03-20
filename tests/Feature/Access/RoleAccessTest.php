<?php

namespace Tests\Feature\Access;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyWithRoles;

    public function test_worker_cannot_access_admin_routes(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $response = $this->actingAs($worker)->get(route('admin.company.edit'));

        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    public function test_admin_cannot_access_developer_routes(): void
    {
        [$company, $admin] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $response = $this->actingAs($admin)->get(route('developer.companies.index'));

        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    public function test_worker_can_access_dashboard(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $this->actingAs($worker)
            ->get(route('dashboard.worker'))
            ->assertOk();
    }

    public function test_guest_redirects_to_login_on_protected_routes(): void
    {
        $routes = [
            route('dashboard'),
            route('worker.orders'),
            route('worker.billing'),
            route('admin.company.edit'),
        ];

        foreach ($routes as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_admin_only_sees_orders_from_own_company(): void
    {
        [$companyA, $adminA] = $this->createCompanyWithRoles();
        [$companyB, $adminB] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($companyA);
        $this->createActiveSubscription($companyB);

        $customerA = Customer::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Cliente A Visible',
        ]);
        $equipmentA = Equipment::factory()->create([
            'company_id' => $companyA->id,
            'customer_id' => $customerA->id,
        ]);
        Order::factory()->create([
            'company_id' => $companyA->id,
            'customer_id' => $customerA->id,
            'equipment_id' => $equipmentA->id,
            'technician' => 'Tech A',
        ]);

        $customerB = Customer::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Cliente B Oculto',
        ]);
        $equipmentB = Equipment::factory()->create([
            'company_id' => $companyB->id,
            'customer_id' => $customerB->id,
        ]);
        Order::factory()->create([
            'company_id' => $companyB->id,
            'customer_id' => $customerB->id,
            'equipment_id' => $equipmentB->id,
            'technician' => 'Tech B',
        ]);

        $response = $this->actingAs($adminA)->get(route('worker.orders'));

        $response->assertOk();
        $response->assertSee('Cliente A Visible');
        $response->assertDontSee('Cliente B Oculto');
    }
}
