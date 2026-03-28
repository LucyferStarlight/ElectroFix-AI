<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderDiagnostic;
use App\Services\DiagnosticCaseSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticCaseSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_similar_cases_using_normalized_symptoms_and_context(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
        ]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => 'Lavadora',
        ]);

        $matchingOrder = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);

        $otherOrder = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);

        $matchingDiagnostic = OrderDiagnostic::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'order_id' => $matchingOrder->id,
            'equipment_id' => $equipment->id,
            'version' => 1,
            'source' => 'ai',
            'symptoms_snapshot' => 'No enciende y hace ruido fuerte',
            'normalized_symptoms' => 'no enciende y hace ruido fuerte',
            'symptom_keywords' => ['enciende', 'ruido', 'lavadora'],
            'equipment_snapshot' => ['type' => 'Lavadora'],
            'equipment_type' => 'lavadora',
            'diagnostic_summary' => 'Posible falla eléctrica',
            'failure_type' => 'power_failure',
            'diagnostic_signature' => 'lavadora|power_failure|enciende-lavadora-ruido',
            'possible_causes' => ['Tarjeta dañada'],
            'recommended_actions' => ['Revisar tarjeta'],
        ]);

        OrderDiagnostic::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'order_id' => $otherOrder->id,
            'equipment_id' => $equipment->id,
            'version' => 1,
            'source' => 'ai',
            'symptoms_snapshot' => 'Fuga de agua por la base',
            'normalized_symptoms' => 'fuga de agua por la base',
            'symptom_keywords' => ['agua', 'fuga', 'lavadora'],
            'equipment_snapshot' => ['type' => 'Lavadora'],
            'equipment_type' => 'lavadora',
            'diagnostic_summary' => 'Posible fuga en manguera',
            'failure_type' => 'water_leak',
            'diagnostic_signature' => 'lavadora|water_leak|agua-fuga-lavadora',
            'possible_causes' => ['Manguera rota'],
            'recommended_actions' => ['Cambiar manguera'],
        ]);

        $results = app(DiagnosticCaseSearchService::class)->findSimilarCases(
            'La lavadora no enciende y hace ruido',
            [
                'company_id' => $company->id,
                'equipment_type' => 'Lavadora',
            ]
        );

        $this->assertNotEmpty($results);
        $this->assertSame($matchingDiagnostic->id, $results->first()['diagnostic']->id);
        $this->assertGreaterThan(0, $results->first()['similarity_score']);
        $this->assertGreaterThan(0, $results->first()['similarity_percentage']);
        $this->assertContains('ruido', $results->first()['matched_keywords']);
        $this->assertSame(1, $results->first()['relevance_rank']);
    }

    public function test_it_accepts_equipment_id_as_second_argument(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => 'Microondas',
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);

        $diagnostic = OrderDiagnostic::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'equipment_id' => $equipment->id,
            'version' => 1,
            'source' => 'ai',
            'symptoms_snapshot' => 'No calienta y hace chispas',
            'normalized_symptoms' => 'no calienta y hace chispas',
            'symptom_keywords' => ['calienta', 'chispas'],
            'equipment_snapshot' => ['type' => 'Microondas'],
            'equipment_type' => 'microondas',
            'diagnostic_summary' => 'Falla de magnetron',
            'failure_type' => 'heating_failure',
            'diagnostic_signature' => 'microondas|heating_failure|calienta-chispas',
            'possible_causes' => ['Magnetron dañado'],
            'recommended_actions' => ['Cambiar magnetron'],
        ]);

        $results = app(DiagnosticCaseSearchService::class)->findSimilarCases(
            'No calienta y genera chispas',
            $equipment->id
        );

        $this->assertSame($diagnostic->id, $results->first()['diagnostic']->id);
        $this->assertTrue($results->first()['is_exact_equipment_match']);
    }
}
