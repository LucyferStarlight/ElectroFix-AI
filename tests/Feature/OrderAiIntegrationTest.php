<?php

namespace Tests\Feature;

use App\Models\AiUsage;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Subscription;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\TechnicianStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_with_ai_is_generated_for_enterprise_plan(): void
    {
        [$worker, $customer, $equipment, $company] = $this->makeContext('enterprise');

        $this->actingAs($worker)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician' => 'Tec IA',
                'symptoms' => 'No enciende y hace ruido fuerte',
                'request_ai_diagnosis' => 1,
                'estimated_cost' => 1200,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);

        $order = $company->orders()->latest('id')->firstOrFail();
        $this->assertNotNull($order->ai_diagnosed_at);
        $this->assertNotNull($order->ai_tokens_used);
        $this->assertNotNull($order->ai_cost_repair_labor);
        $this->assertTrue((bool) $order->ai_requires_parts_replacement);
        $this->assertGreaterThan(0, (float) $order->ai_cost_replacement_total);

        $this->assertDatabaseHas('company_ai_usages', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'status' => 'success',
            'plan_snapshot' => 'enterprise',
        ]);
    }

    public function test_order_with_ai_is_generated_for_starter_plan(): void
    {
        [$worker, $customer, $equipment, $company] = $this->makeContext('starter');

        $this->actingAs($worker)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician' => 'Tec IA',
                'symptoms' => 'No enciende',
                'request_ai_diagnosis' => 1,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $order = $company->orders()->latest('id')->firstOrFail();
        $this->assertNotNull($order->ai_diagnosed_at);

        $this->assertDatabaseHas('company_ai_usages', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'status' => 'success',
        ]);
    }

    public function test_order_ai_is_blocked_when_query_limit_is_reached(): void
    {
        [$worker, $customer, $equipment, $company] = $this->makeContext('pro');
        AiUsage::query()->create([
            'company_id' => $company->id,
            'ai_requests_used' => 75,
            'ai_tokens_used' => 500,
            'current_cycle_start' => now()->startOfMonth()->toDateString(),
            'current_cycle_end' => now()->endOfMonth()->toDateString(),
            'overage_requests' => 0,
            'overage_tokens' => 0,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician' => 'Tec IA',
                'symptoms' => 'No enciende',
                'request_ai_diagnosis' => 1,
            ])
            ->assertSessionHas('warning', 'Se alcanzó el límite mensual de consultas IA para tu empresa.');

        $order = $company->orders()->latest('id')->firstOrFail();
        $this->assertNull($order->ai_diagnosed_at);
    }

    public function test_order_ai_is_blocked_when_token_limit_is_reached(): void
    {
        [$worker, $customer, $equipment, $company] = $this->makeContext('pro');
        AiUsage::query()->create([
            'company_id' => $company->id,
            'ai_requests_used' => 10,
            'ai_tokens_used' => 49990,
            'current_cycle_start' => now()->startOfMonth()->toDateString(),
            'current_cycle_end' => now()->endOfMonth()->toDateString(),
            'overage_requests' => 0,
            'overage_tokens' => 0,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician' => 'Tec IA',
                'symptoms' => str_repeat('Falla intermitente. ', 20),
                'request_ai_diagnosis' => 1,
            ])
            ->assertSessionHas('warning', 'Se alcanzó el límite mensual de consumo IA para tu empresa.');

        $order = $company->orders()->latest('id')->firstOrFail();
        $this->assertNull($order->ai_diagnosed_at);
    }

    public function test_symptoms_length_validation_for_ai_request(): void
    {
        [$worker, $customer, $equipment] = $this->makeContext('enterprise');

        $this->actingAs($worker)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician' => 'Tec IA',
                'request_ai_diagnosis' => 1,
                'symptoms' => str_repeat('A', 601),
            ])
            ->assertSessionHasErrors(['symptoms']);
    }

    public function test_cost_output_is_conditional_by_requires_parts_flag(): void
    {
        [$worker, $customer, $equipment, $company] = $this->makeContext('developer_test');

        $this->actingAs($worker)->post(route('worker.orders.store'), [
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'technician' => 'Tec IA',
            'request_ai_diagnosis' => 1,
            'symptoms' => 'Requiere limpieza general y ajuste de calibración.',
        ])->assertSessionHasNoErrors();

        $laborOnlyOrder = $company->orders()->latest('id')->firstOrFail();
        $this->assertFalse((bool) $laborOnlyOrder->ai_requires_parts_replacement);
        $this->assertSame('0.00', (string) $laborOnlyOrder->ai_cost_replacement_parts);
        $this->assertSame('0.00', (string) $laborOnlyOrder->ai_cost_replacement_total);
        $this->assertGreaterThan(0, (float) $laborOnlyOrder->ai_cost_repair_labor);
    }

    private function makeContext(string $plan): array
    {
        $company = Company::factory()->create();
        Subscription::factory()->create([
            'company_id' => $company->id,
            'plan' => $plan,
            'status' => 'active',
        ]);
        $worker = User::factory()->create(['role' => 'worker', 'company_id' => $company->id]);
        TechnicianProfile::query()->create([
            'company_id' => $company->id,
            'user_id' => $worker->id,
            'employee_code' => 'EMP-001',
            'display_name' => $worker->name,
            'specialties' => ['electrodomesticos'],
            'status' => TechnicianStatus::AVAILABLE,
            'max_concurrent_orders' => 5,
            'hourly_cost' => 0,
            'is_assignable' => true,
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        return [$worker, $customer, $equipment, $company];
    }
}
