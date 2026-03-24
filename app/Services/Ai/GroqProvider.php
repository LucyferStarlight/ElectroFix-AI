<?php

namespace App\Services\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;
use App\Services\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GroqProvider implements AiDiagnosticProvider
{
    public function __construct(private readonly AiDiagnosticFormatter $formatter)
    {
    }

    public function diagnose(string $symptoms, string $deviceInfo): AiDiagnosticResult
    {
        $apiKey = (string) config('services.groq.api_key');
        if ($apiKey === '') {
            throw new AiProviderException('missing_config', 'El servicio de diagnóstico IA no está configurado.');
        }

        $model = (string) config('services.groq.model', 'llama-3.3-70b-versatile');
        $timeout = (int) config('services.groq.timeout_seconds', 15);
        $prompt = $this->buildPrompt($deviceInfo, $symptoms);
        $systemPrompt = (string) config(
            'services.groq.system_prompt',
            'Eres un asistente técnico de reparación de electrodomésticos. Responde únicamente en es-MX. Devuelve SOLO JSON válido.'
        );

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->retry(2, 250, throw: false)
                ->withToken($apiKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_object',
                    ],
                    'temperature' => 0.2,
                ]);
        } catch (ConnectionException) {
            throw new AiProviderException('provider_timeout', 'El servicio de diagnóstico IA no respondió a tiempo.');
        } catch (\Throwable) {
            throw new AiProviderException('provider_error', 'No fue posible contactar el servicio de diagnóstico IA.');
        }

        if (! $response->successful()) {
            throw new AiProviderException('provider_http_error', 'El servicio de diagnóstico IA devolvió un error temporal.');
        }

        try {
            $payload = $response->json();
        } catch (\Throwable) {
            throw new AiProviderException('invalid_json', 'La respuesta del servicio IA no fue válida.');
        }

        $text = trim((string) Arr::get($payload, 'choices.0.message.content', ''));
        if ($text === '') {
            throw new AiProviderException('invalid_schema', 'La respuesta del servicio IA fue incompleta.');
        }

        $parsed = $this->parseStructuredResponse($text);
        if ($parsed === null) {
            throw new AiProviderException('invalid_schema', 'La respuesta del servicio IA fue incompleta.');
        }

        $tokensUsed = Arr::get($payload, 'usage.total_tokens');
        $tokensUsed = is_numeric($tokensUsed) ? (int) $tokensUsed : 0;

        $normalized = $this->formatter->normalizeProviderContent(
            $parsed,
            $deviceInfo,
            $symptoms,
            'groq',
            $model,
            $tokensUsed
        );

        return AiDiagnosticResult::fromPayload($normalized, 'groq', $tokensUsed);
    }

    public static function buildSystemPrompt(): string
    {
        return 'Eres ARIS, sistema experto de diagnóstico de electrodomésticos de ElectroFix. '
            .'Responde únicamente en es-MX. Sé más específico y técnico que un asistente genérico. '
            .'Devuelve SOLO JSON válido con el schema definido.';
    }

    private function buildPrompt(string $deviceInfo, string $symptoms): string
    {
        $language = 'es-MX';

        return <<<PROMPT
Eres un asistente técnico de reparación de electrodomésticos.
Responde únicamente en {$language} y devuelve SOLO JSON válido con esta estructura:
{
  "diagnostic_summary": "string",
  "possible_causes": ["string"],
  "recommended_actions": ["string"],
  "estimated_time": "string",
  "suggested_parts": ["string"],
  "technical_advice": "string",
  "requires_parts_replacement": true,
  "confidence_score": 0,
  "cost_suggestion": {
    "repair_labor_cost": 0,
    "replacement_parts_cost": 0,
    "replacement_total_cost": 0
  }
}

Equipo:
- {$deviceInfo}
- Síntomas: {$symptoms}

Reglas:
- Si no requiere piezas, usa requires_parts_replacement=false y replacement_* en 0.
- confidence_score debe ser numérico de 0 a 100.
PROMPT;
    }

    private function parseStructuredResponse(string $text): ?array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?/i', '', $clean) ?? $clean;
        $clean = preg_replace('/```$/', '', $clean) ?? $clean;
        $clean = trim($clean);

        try {
            $decoded = json_decode($clean, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        if (! array_key_exists('possible_causes', $decoded)
            || ! array_key_exists('recommended_actions', $decoded)
            || ! array_key_exists('cost_suggestion', $decoded)) {
            return null;
        }

        return $decoded;
    }
}
