<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderDiagnostic;
use App\Models\OrderRepairOutcome;
use App\Services\DiagnosticInsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticInsightsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_frequent_failures_and_average_costs(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $orderA = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);

        $orderB = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);

        foreach ([[$orderA, 500], [$orderB, 700]] as [$order, $amount]) {
            OrderDiagnostic::query()->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'equipment_id' => $equipment->id,
                'version' => 1,
                'source' => 'ai',
                'symptoms_snapshot' => 'No enciende',
                'normalized_symptoms' => 'no enciende',
                'symptom_keywords' => ['enciende'],
                'equipment_snapshot' => ['type' => 'Laptop'],
                'equipment_type' => 'laptop',
                'diagnostic_summary' => 'Falla de energía',
                'failure_type' => 'power_failure',
                'diagnostic_signature' => 'laptop|power_failure|enciende',
                'possible_causes' => ['Fuente dañada'],
                'recommended_actions' => ['Cambiar fuente'],
            ]);

            OrderRepairOutcome::query()->create([
                'order_id' => $order->id,
                'company_id' => $company->id,
                'repair_outcome' => 'repaired',
                'work_performed' => 'Cambio de fuente',
                'actual_amount_charged' => $amount,
                'had_ai_diagnosis' => true,
                'feeds_aris_training' => true,
                'plan_at_close' => 'starter',
            ]);
        }

        $service = app(DiagnosticInsightsService::class);

        $frequentFailures = $service->getFrequentFailures($company->id);
        $averageCosts = $service->getAverageRepairCostByIssue($company->id);

        $this->assertSame('power_failure', $frequentFailures->first()['failure_type']);
        $this->assertSame(2, $frequentFailures->first()['cases_count']);
        $this->assertSame('power_failure', $averageCosts->first()['failure_type']);
        $this->assertSame(600.0, $averageCosts->first()['average_repair_cost']);
        $this->assertSame(2, $averageCosts->first()['cases_count']);
    }
}
