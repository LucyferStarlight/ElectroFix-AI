<?php

namespace Tests\Feature\Orders;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Support\ApiAbility;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class OrderStatusTransitionTest extends TestCase
{
    use CreatesCompanyWithRoles;
    use RefreshDatabase;

    public function test_api_rejects_invalid_order_status_transition(): void
    {
        [$company, $admin] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'status' => OrderStatus::CREATED,
        ]);

        Sanctum::actingAs($admin, [ApiAbility::ORDERS_WRITE]);

        $response = $this->patchJson('/api/v1/orders/'.$order->id.'/status', [
            'status' => OrderStatus::DELIVERED,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_STATUS_TRANSITION');
        $this->assertSame(OrderStatus::CREATED, $order->fresh()->status);
    }

    public function test_api_accepts_valid_transition_with_legacy_input(): void
    {
        [$company, $admin] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'status' => 'received',
        ]);

        Sanctum::actingAs($admin, [ApiAbility::ORDERS_WRITE]);

        $response = $this->patchJson('/api/v1/orders/'.$order->id.'/status', [
            'status' => 'diagnostic',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', OrderStatus::DIAGNOSING);
        $response->assertJsonPath('data.status_label', 'Diagnóstico');
        $this->assertSame(OrderStatus::DIAGNOSING, $order->fresh()->status);
    }

    public function test_api_can_approve_order_with_context(): void
    {
        [$company, $admin] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'status' => OrderStatus::QUOTED,
        ]);

        Sanctum::actingAs($admin, [ApiAbility::ORDERS_WRITE]);

        $response = $this->patchJson('/api/v1/orders/'.$order->id.'/approve', [
            'approved_by' => 'customer',
            'approval_channel' => 'whatsapp',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', OrderStatus::APPROVED);
        $response->assertJsonPath('data.approved_by', 'customer');
        $response->assertJsonPath('data.approval_channel', 'whatsapp');
        $response->assertJsonPath('data.is_approved', true);
    }

    public function test_api_rejects_repair_transition_when_order_has_not_been_approved(): void
    {
        [$company, $admin] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'status' => OrderStatus::APPROVED,
            'approved_at' => null,
            'approval_channel' => null,
        ]);

        Sanctum::actingAs($admin, [ApiAbility::ORDERS_WRITE]);

        $response = $this->patchJson('/api/v1/orders/'.$order->id.'/status', [
            'status' => OrderStatus::IN_REPAIR,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'INVALID_STATUS_TRANSITION');
        $this->assertSame(OrderStatus::APPROVED, $order->fresh()->status);
    }
}
