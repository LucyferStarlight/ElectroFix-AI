<?php

namespace Tests\Feature\Orders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\ApiAbility;
use App\Support\TechnicianStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyWithRoles;

    public function test_admin_creates_order_and_assigns_technician(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $technician = $this->createAssignableTechnicianProfile($company, $worker, 'Tech Uno');
        [$customer, $equipment] = $this->createCustomerAndEquipment($company);

        $this->actingAs($admin)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician_profile_id' => $technician->id,
                'symptoms' => 'No enciende',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'technician_profile_id' => $technician->id,
            'technician' => 'Tech Uno',
        ]);
    }

    public function test_admin_cannot_assign_technician_from_other_company(): void
    {
        [$companyA, $adminA] = $this->createCompanyWithRoles();
        [$companyB, $adminB, $workerB] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($companyA);
        $this->createActiveSubscription($companyB);

        $technicianB = $this->createAssignableTechnicianProfile($companyB, $workerB, 'Tech B');
        [$customerA, $equipmentA] = $this->createCustomerAndEquipment($companyA);

        $response = $this->actingAs($adminA)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customerA->id,
                'equipment_id' => $equipmentA->id,
                'technician_profile_id' => $technicianB->id,
                'symptoms' => 'No arranca',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_worker_creates_order_and_is_auto_assigned(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $workerProfile = $this->createAssignableTechnicianProfile($company, $worker, 'Worker Auto');
        [$customer, $equipment] = $this->createCustomerAndEquipment($company);

        $this->actingAs($worker)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'symptoms' => 'No enfría',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'company_id' => $company->id,
            'technician_profile_id' => $workerProfile->id,
            'technician' => 'Worker Auto',
        ]);
    }

    public function test_worker_cannot_change_assigned_technician_from_ui(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $workerProfile = $this->createAssignableTechnicianProfile($company, $worker, 'Worker Uno');
        $otherWorker = User::factory()->create(['company_id' => $company->id, 'role' => 'worker']);
        $otherProfile = $this->createAssignableTechnicianProfile($company, $otherWorker, 'Worker Dos');
        [$customer, $equipment] = $this->createCustomerAndEquipment($company);

        $this->actingAs($worker)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician_profile_id' => $otherProfile->id,
                'symptoms' => 'No arranca',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'company_id' => $company->id,
            'technician_profile_id' => $workerProfile->id,
            'technician' => 'Worker Uno',
        ]);
    }

    public function test_order_with_existing_ai_diagnosis_cannot_request_again(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);
        $this->createAssignableTechnicianProfile($company, $worker, 'Tech Uno');

        [$customer, $equipment] = $this->createCustomerAndEquipment($company);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'technician' => 'Tech Uno',
            'ai_diagnosed_at' => now(),
        ]);

        Sanctum::actingAs($worker, [ApiAbility::AI_USE]);

        $response = $this->postJson('/api/v1/orders/'.$order->id.'/diagnostics', [
            'symptoms' => 'No enciende',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => [
                'code' => 'DIAGNOSTIC_ALREADY_EXISTS',
                'message' => 'Esta orden ya cuenta con un diagnóstico IA.',
            ],
        ]);
    }

    public function test_order_symptoms_over_600_chars_are_rejected(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $technician = $this->createAssignableTechnicianProfile($company, $worker, 'Tech Uno');
        [$customer, $equipment] = $this->createCustomerAndEquipment($company);

        $tooLong = str_repeat('a', 601);

        $this->actingAs($admin)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician_profile_id' => $technician->id,
                'request_ai_diagnosis' => 1,
                'symptoms' => $tooLong,
            ])
            ->assertSessionHasErrors('symptoms');
    }

    private function createAssignableTechnicianProfile(Company $company, User $user, string $displayName): TechnicianProfile
    {
        return TechnicianProfile::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'employee_code' => strtoupper($user->id.'-QA'),
            'display_name' => $displayName,
            'specialties' => ['General'],
            'status' => TechnicianStatus::AVAILABLE,
            'max_concurrent_orders' => 3,
            'hourly_cost' => 100,
            'is_assignable' => true,
        ]);
    }

    private function createCustomerAndEquipment(Company $company): array
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        return [$customer, $equipment];
    }
}
