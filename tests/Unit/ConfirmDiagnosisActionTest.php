<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\ConfirmDiagnosisAction;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderRepairOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmDiagnosisActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_validation_payload_with_ai_comparison(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);
        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);

        $outcome = OrderRepairOutcome::query()->create([
            'order_id' => $order->id,
            'company_id' => $company->id,
            'repair_outcome' => 'repaired',
            'work_performed' => 'Cambio de tarjeta',
            'actual_amount_charged' => 500.00,
            'plan_at_close' => 'starter',
            'ai_diagnosis' => [
                'failure_type' => 'motor quemado',
                'confidence_score' => 82.5,
            ],
        ]);

        $payload = app(ConfirmDiagnosisAction::class)->execute($order, $outcome, [
            'diagnostic_accuracy' => 'correct',
            'technician_notes' => 'Se confirmó daño en motor',
            'actual_causes' => ['motor quemado'],
            'validated' => true,
        ]);

        $this->assertTrue($payload['validated']);
        $this->assertSame(82.5, (float) $payload['confidence_score']);
        $this->assertSame(100, $payload['real_diagnosis']['ai_comparison']['score']);
        $this->assertTrue($payload['real_diagnosis']['ai_comparison']['match']);
    }
}
