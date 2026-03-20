<?php

namespace Tests\Unit;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AiDiagnosticService;
use App\Services\Exceptions\AiProviderException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDiagnosticServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_provider_response_to_domain_shape(): void
    {
        [$company, $order, $actor] = $this->makeContext();

        $payload = [
            'diagnostic_summary' => 'Resumen',
            'possible_causes' => ['Causa A'],
            'recommended_actions' => ['Acción A'],
            'estimated_time' => '3 horas',
            'suggested_parts' => ['Pieza A'],
            'technical_advice' => 'Consejo',
            'requires_parts_replacement' => true,
            'confidence_score' => 80,
            'cost_suggestion' => [
                'repair_labor_cost' => 700,
                'replacement_parts_cost' => 400,
                'replacement_total_cost' => 1100,
            ],
            'provider' => 'gemini',
            'model' => 'gemini-1.5-flash',
        ];

        $provider = $this->mock(AiDiagnosticProvider::class);
        $provider->shouldReceive('diagnose')
            ->once()
            ->andReturn(new AiDiagnosticResult(
                diagnosis: 'Resumen',
                estimatedCost: 1100.0,
                requiresParts: true,
                provider: 'gemini',
                tokensUsed: 200,
                payload: $payload
            ));

        $service = app(AiDiagnosticService::class);
        $result = $service->diagnose($order, $company, $actor, 'No enciende');

        $this->assertSame('gemini', $result->provider);
        $this->assertTrue($result->requiresParts);

        $order->refresh();
        $this->assertNotNull($order->ai_diagnosed_at);
        $this->assertSame('gemini', $order->ai_provider);
        $this->assertSame('1100.00', (string) $order->ai_cost_replacement_total);
    }

    public function test_it_falls_back_when_provider_fails(): void
    {
        [$company, $order, $actor] = $this->makeContext();

        $provider = $this->mock(AiDiagnosticProvider::class);
        $provider->shouldReceive('diagnose')
            ->once()
            ->andThrow(new AiProviderException('provider_timeout', 'Proveedor no disponible.'));

        $service = app(AiDiagnosticService::class);
        $result = $service->diagnose($order, $company, $actor, 'No calienta');

        $this->assertSame('local', $result->provider);
        $this->assertNotSame('', $result->diagnosis);
        $this->assertSame(0, $result->tokensUsed);
    }

    private function makeContext(): array
    {
        $company = Company::factory()->create();
        Subscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'enterprise',
            'status' => 'active',
        ]);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => 'Lavadora',
            'brand' => 'LG',
            'model' => 'X1',
        ]);
        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);
        $actor = User::factory()->create(['company_id' => $company->id]);

        return [$company, $order, $actor];
    }
}
