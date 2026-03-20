<?php

namespace Tests\Feature\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;
use App\Services\Ai\LocalFallbackProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDiagnosticProviderSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_fallback_is_used_when_gemini_key_is_missing(): void
    {
        config()->set('ai.provider', 'gemini');
        config()->set('services.gemini.api_key', '');

        $provider = app(AiDiagnosticProvider::class);

        $this->assertInstanceOf(LocalFallbackProvider::class, $provider);

        $result = $provider->diagnose('No enciende', 'Lavadora LG X123');

        $this->assertInstanceOf(AiDiagnosticResult::class, $result);
        $this->assertSame('local', $result->provider);
        $this->assertSame(0, $result->tokensUsed);
        $this->assertNotSame('', $result->diagnosis);
    }
}
