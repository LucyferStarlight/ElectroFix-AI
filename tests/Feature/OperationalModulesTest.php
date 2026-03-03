<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_can_create_customer_and_equipment_and_order(): void
    {
        $company = Company::factory()->create();
        $worker = User::factory()->create(['role' => 'worker', 'company_id' => $company->id]);

        $this->actingAs($worker)
            ->post(route('worker.customers.store'), [
                'name' => 'Cliente QA',
                'email' => 'clienteqa@example.com',
                'phone' => '555-1000',
                'address' => 'CDMX',
            ])
            ->assertSessionHasNoErrors();

        $customer = Customer::query()->where('email', 'clienteqa@example.com')->firstOrFail();

        $this->actingAs($worker)
            ->post(route('worker.equipments.store'), [
                'customer_id' => $customer->id,
                'type' => 'Lavadora',
                'brand' => 'Samsung',
                'model' => 'WF45R',
                'serial_number' => 'SN-TEST-01',
            ])
            ->assertSessionHasNoErrors();

        $equipment = Equipment::query()->where('serial_number', 'SN-TEST-01')->firstOrFail();

        $this->actingAs($worker)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician' => 'Operador QA',
                'symptoms' => 'No enciende y hace ruido',
                'estimated_cost' => 1200,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'technician' => 'Operador QA',
        ]);
    }

    public function test_worker_cannot_register_equipment_for_other_company_customer(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $worker = User::factory()->create(['role' => 'worker', 'company_id' => $companyA->id]);
        $foreignCustomer = Customer::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($worker)
            ->post(route('worker.equipments.store'), [
                'customer_id' => $foreignCustomer->id,
                'type' => 'Refrigerador',
                'brand' => 'LG',
            ])
            ->assertForbidden();
    }

    public function test_admin_assigns_worker_as_technician_when_creating_order(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'company_id' => $company->id]);
        $worker = User::factory()->create(['role' => 'worker', 'company_id' => $company->id, 'name' => 'Worker Técnico']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        $this->actingAs($admin)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician_user_id' => $worker->id,
                'symptoms' => 'No arranca',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'technician' => 'Worker Técnico',
        ]);
    }

    public function test_admin_can_assign_himself_as_technician_when_creating_order(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'company_id' => $company->id, 'name' => 'Admin Técnico']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        $this->actingAs($admin)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician_user_id' => $admin->id,
                'symptoms' => 'No enfría',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'technician' => 'Admin Técnico',
        ]);
    }

    public function test_order_diagnose_endpoint_is_disabled_for_direct_consumption(): void
    {
        $company = Company::factory()->create();
        $worker = User::factory()->create(['role' => 'worker', 'company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        $this->actingAs($worker)
            ->postJson(route('worker.orders.diagnose'), [
                'equipment_id' => $equipment->id,
                'symptoms' => 'No enciende, hay ruido y fuga de agua',
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'La consulta directa está deshabilitada. Activa "Solicitar diagnóstico IA" y guarda la orden.',
            ]);
    }
}
