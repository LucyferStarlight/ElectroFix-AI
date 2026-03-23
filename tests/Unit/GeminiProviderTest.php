<?php

namespace Tests\Unit;

use App\Services\Ai\AiDiagnosticFormatter;
use App\Services\Ai\GeminiProvider;
use App\Services\Exceptions\AiProviderException;
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

        $provider = new GeminiProvider(new AiDiagnosticFormatter());
        $response = $provider->diagnose('No enciende', 'Lavadora Marca M1');

        $this->assertSame('gemini', $response->provider);
        $this->assertSame(123, $response->tokensUsed);
        $this->assertSame('Diagnóstico preliminar', $response->payload['diagnostic_summary']);
    }

    public function test_it_returns_failure_for_invalid_payload(): void
    {
        config()->set('services.gemini.api_key', 'test-key');

        Http::fake([
            '*' => Http::response(['candidates' => []], 200),
        ]);

        $provider = new GeminiProvider(new AiDiagnosticFormatter());

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('No se pudo interpretar la respuesta del servicio IA.');

        try {
            $provider->diagnose('No enfría', 'Refrigerador Marca R1');
        } catch (AiProviderException $e) {
            $this->assertSame('invalid_payload', $e->status());
            throw $e;
        }
    }
}
