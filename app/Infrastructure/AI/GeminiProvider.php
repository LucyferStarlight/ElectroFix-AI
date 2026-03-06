<?php

namespace App\Infrastructure\AI;

use App\Application\AI\Contracts\AiProviderInterface;
use App\Application\AI\DTO\AiResponse;
use App\Application\AI\DTO\UsageEstimate;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProviderInterface
{
    public function generateSolution(array $context): AiResponse
    {
        $apiKey = (string) config('services.gemini.api_key');
        if ($apiKey === '') {
            return AiResponse::failure('missing_config', 'El servicio de diagnóstico IA no está configurado.');
        }

        $model = (string) config('services.gemini.model', 'gemini-1.5-flash');
        $endpointTemplate = (string) config(
            'services.gemini.endpoint',
            'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent'
        );
        $timeout = (int) config('services.gemini.timeout_seconds', 12);
        $url = str_replace('{model}', $model, $endpointTemplate);
        $prompt = $this->buildPrompt($context);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->retry(2, 250, throw: false)
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                    'safetySettings' => [
                        [
                            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                            'threshold' => 'BLOCK_ONLY_HIGH',
                        ],
                    ],
                ]);
        } catch (ConnectionException) {
            return AiResponse::failure('provider_timeout', 'El servicio de diagnóstico IA no respondió a tiempo.');
        } catch (\Throwable) {
            return AiResponse::failure('provider_error', 'No fue posible contactar el servicio de diagnóstico IA.');
        }

        if (! $response->successful()) {
            return AiResponse::failure('provider_http_error', 'El servicio de diagnóstico IA devolvió un error temporal.');
        }

        try {
            $payload = $response->json();
        } catch (\Throwable) {
            return AiResponse::failure('invalid_json', 'La respuesta del servicio IA no fue válida.');
        }

        $text = $this->extractText($payload);
        if ($text === '') {
            return AiResponse::failure('invalid_payload', 'No se pudo interpretar la respuesta del servicio IA.');
        }

        $parsed = $this->parseStructuredResponse($text);
        if ($parsed === null) {
            return AiResponse::failure('invalid_schema', 'La respuesta del servicio IA fue incompleta.');
        }

        $tokensUsed = Arr::get($payload, 'usageMetadata.totalTokenCount');
        $tokensUsed = is_numeric($tokensUsed) ? (int) $tokensUsed : null;

        return AiResponse::success($parsed, $tokensUsed);
    }

    public function estimateUsage(string $prompt): UsageEstimate
    {
        $promptTokens = (int) max(1, ceil(mb_strlen($prompt) / 4));
        $responseTokens = (int) max(120, ceil($promptTokens * 0.9));

        return new UsageEstimate(
            promptTokensEstimated: $promptTokens,
            responseTokensEstimated: $responseTokens,
            totalTokensEstimated: $promptTokens + $responseTokens
        );
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }

    private function buildPrompt(array $context): string
    {
        $type = (string) ($context['type'] ?? '');
        $brand = (string) ($context['brand'] ?? '');
        $model = (string) ($context['model'] ?? '');
        $symptoms = (string) ($context['symptoms'] ?? '');
        $language = (string) ($context['language'] ?? 'es-MX');

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
- Tipo: {$type}
- Marca: {$brand}
- Modelo: {$model}
- Síntomas: {$symptoms}

Reglas:
- Si no requiere piezas, usa requires_parts_replacement=false y replacement_* en 0.
- confidence_score debe ser numérico de 0 a 100.
PROMPT;
    }

    private function extractText(array $payload): string
    {
        $parts = Arr::get($payload, 'candidates.0.content.parts', []);
        if (! is_array($parts)) {
            return '';
        }

        $segments = [];
        foreach ($parts as $part) {
            $text = is_array($part) ? (string) ($part['text'] ?? '') : '';
            if ($text !== '') {
                $segments[] = $text;
            }
        }

        return trim(implode("\n", $segments));
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
