<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderRepairOutcome;
use App\Support\ApiAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class RepairOutcomeFeedbackApiTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyWithRoles;

    public function test_feedback_endpoint_persists_real_diagnosis_and_validation(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
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
            'status' => 'completed',
        ]);

        OrderRepairOutcome::query()->create([
            'order_id' => $order->id,
            'company_id' => $company->id,
            'repair_outcome' => 'repaired',
            'work_performed' => 'Reemplazo de compresor',
            'actual_amount_charged' => 1200.00,
            'plan_at_close' => 'starter',
            'ai_diagnosis' => [
                'failure_type' => 'compresor dañado',
                'confidence_score' => 76.25,
            ],
        ]);

        Sanctum::actingAs($worker, [ApiAbility::ORDERS_WRITE]);

        $response = $this->patchJson('/api/v1/orders/'.$order->id.'/repair-outcome/feedback', [
            'diagnostic_accuracy' => 'correct',
            'technician_notes' => 'Compresor sustituido y pruebas OK',
            'actual_causes' => ['compresor dañado'],
            'repair_applied' => 'Cambio de compresor y recarga de gas',
            'real_diagnosis' => [
                'summary' => 'Falla en compresor confirmada',
                'root_cause' => 'Compresor quemado por sobrecalentamiento',
            ],
            'validated' => true,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('order_repair_outcomes', [
            'order_id' => $order->id,
            'diagnostic_accuracy' => 'correct',
            'validated' => 1,
            'repair_applied' => 'Cambio de compresor y recarga de gas',
        ]);

        $outcome = OrderRepairOutcome::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('Falla en compresor confirmada', $outcome->real_diagnosis['summary'] ?? null);
        $this->assertSame(100, $outcome->real_diagnosis['ai_comparison']['score'] ?? null);
    }
}
