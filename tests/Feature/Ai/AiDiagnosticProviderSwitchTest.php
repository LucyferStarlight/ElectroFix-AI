<?php

namespace Tests\Feature\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\Services\Ai\GroqProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDiagnosticProviderSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_groq_is_the_only_registered_ai_provider(): void
    {
        config()->set('ai.provider', 'groq');
        config()->set('services.groq.api_key', 'test-key');

        $provider = app(AiDiagnosticProvider::class);

        $this->assertInstanceOf(GroqProvider::class, $provider);
    }
}
