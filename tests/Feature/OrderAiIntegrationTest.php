<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyAiUsage;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Subscription;
use App\Models\User;
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

    public function test_order_ai_is_blocked_when_plan_does_not_include_it(): void
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
            ->assertSessionHas('warning', 'Tu plan actual no incluye Asistente IA.');

        $order = $company->orders()->latest('id')->firstOrFail();
        $this->assertNull($order->ai_diagnosed_at);

        $this->assertDatabaseHas('company_ai_usages', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'status' => 'blocked_plan',
        ]);
    }

    public function test_order_ai_is_blocked_when_query_limit_is_reached(): void
    {
        [$worker, $customer, $equipment, $company] = $this->makeContext('enterprise');

        for ($i = 0; $i < 200; $i++) {
            CompanyAiUsage::query()->create([
                'company_id' => $company->id,
                'order_id' => null,
                'year_month' => now()->format('Y-m'),
                'plan_snapshot' => 'enterprise',
                'prompt_chars' => 100,
                'response_chars' => 100,
                'prompt_tokens_estimated' => 25,
                'response_tokens_estimated' => 25,
                'total_tokens_estimated' => 50,
                'status' => 'success',
                'error_message' => null,
            ]);
        }

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
        [$worker, $customer, $equipment, $company] = $this->makeContext('enterprise');

        CompanyAiUsage::query()->create([
            'company_id' => $company->id,
            'order_id' => null,
            'year_month' => now()->format('Y-m'),
            'plan_snapshot' => 'enterprise',
            'prompt_chars' => 0,
            'response_chars' => 0,
            'prompt_tokens_estimated' => 0,
            'response_tokens_estimated' => 0,
            'total_tokens_estimated' => 119990,
            'status' => 'success',
            'error_message' => null,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician' => 'Tec IA',
                'symptoms' => str_repeat('Falla intermitente. ', 40),
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
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        return [$worker, $customer, $equipment, $company];
    }
}

