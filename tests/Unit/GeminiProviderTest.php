<?php

namespace Tests\Unit;

use App\Infrastructure\AI\GeminiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiProviderTest extends TestCase
{
    public function test_it_returns_success_with_valid_payload(): void
    {
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model', 'gemini-test');

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'diagnostic_summary' => 'Diagnóstico preliminar',
                                'possible_causes' => ['Causa 1'],
                                'recommended_actions' => ['Acción 1'],
                                'estimated_time' => '2 horas',
                                'suggested_parts' => ['Pieza A'],
                                'technical_advice' => 'Revisión técnica',
                                'requires_parts_replacement' => true,
                                'confidence_score' => 81,
                                'cost_suggestion' => [
                                    'repair_labor_cost' => 500,
                                    'replacement_parts_cost' => 320,
                                    'replacement_total_cost' => 820,
                                ],
                            ], JSON_UNESCAPED_UNICODE),
                        ]],
                    ],
                ]],
                'usageMetadata' => [
                    'totalTokenCount' => 123,
                ],
            ], 200),
        ]);

        $provider = new GeminiProvider();
        $response = $provider->generateSolution([
            'type' => 'Lavadora',
            'brand' => 'Marca',
            'model' => 'M1',
            'symptoms' => 'No enciende',
        ]);

        $this->assertTrue($response->success);
        $this->assertSame(123, $response->tokensUsed);
        $this->assertSame('Diagnóstico preliminar', $response->content['diagnostic_summary']);
    }

    public function test_it_returns_failure_for_invalid_payload(): void
    {
        config()->set('services.gemini.api_key', 'test-key');

        Http::fake([
            '*' => Http::response(['candidates' => []], 200),
        ]);

        $provider = new GeminiProvider();
        $response = $provider->generateSolution([
            'type' => 'Refrigerador',
            'brand' => 'Marca',
            'model' => 'R1',
            'symptoms' => 'No enfría',
        ]);

        $this->assertFalse($response->success);
        $this->assertSame('invalid_payload', $response->errorCode);
    }
}

