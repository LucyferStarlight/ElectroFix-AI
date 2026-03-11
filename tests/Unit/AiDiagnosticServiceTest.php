<?php

namespace Tests\Unit;

use App\Application\AI\Contracts\AiProviderInterface;
use App\Application\AI\DTO\AiResponse;
use App\Application\AI\DTO\UsageEstimate;
use App\Services\AiDiagnosticService;
use Tests\TestCase;

class AiDiagnosticServiceTest extends TestCase
{
    public function test_it_maps_provider_response_to_domain_shape(): void
    {
        $provider = new class implements AiProviderInterface
        {
            public function generateSolution(array $context): AiResponse
            {
                return AiResponse::success([
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
                ], 200);
            }

            public function estimateUsage(string $prompt): UsageEstimate
            {
                return new UsageEstimate(10, 20, 30);
            }

            public function getProviderName(): string
            {
                return 'gemini';
            }
        };

        $service = new AiDiagnosticService($provider);
        $analysis = $service->analyze('Lavadora', 'LG', 'X1', 'No enciende');

        $this->assertTrue($analysis['success']);
        $this->assertSame('gemini', $analysis['provider']);
        $this->assertTrue($analysis['requires_parts_replacement']);
        $this->assertSame(1100.0, $analysis['cost_suggestion']['replacement_total_cost']);
    }

    public function test_it_falls_back_when_provider_fails(): void
    {
        $provider = new class implements AiProviderInterface
        {
            public function generateSolution(array $context): AiResponse
            {
                return AiResponse::failure('provider_timeout', 'Proveedor no disponible.');
            }

            public function estimateUsage(string $prompt): UsageEstimate
            {
                return new UsageEstimate(10, 20, 30);
            }

            public function getProviderName(): string
            {
                return 'gemini';
            }
        };

        $service = new AiDiagnosticService($provider);
        $analysis = $service->analyze('Horno', 'Whirlpool', 'Z9', 'No calienta');

        $this->assertTrue($analysis['success']);
        $this->assertSame('provider_timeout', $analysis['error_code']);
        $this->assertSame('gemini_fallback', $analysis['provider']);
        $this->assertNotEmpty($analysis['possible_causes']);
    }
}
