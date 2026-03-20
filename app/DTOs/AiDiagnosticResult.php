<?php

namespace App\DTOs;

class AiDiagnosticResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $diagnosis,
        public ?float $estimatedCost,
        public bool $requiresParts,
        public string $provider,
        public int $tokensUsed,
        public array $payload = []
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload, string $provider, int $tokensUsed): self
    {
        $requiresParts = (bool) ($payload['requires_parts_replacement'] ?? false);
        $estimatedCost = $requiresParts
            ? (float) ($payload['cost_suggestion']['replacement_total_cost'] ?? 0.0)
            : (float) ($payload['cost_suggestion']['repair_labor_cost'] ?? 0.0);

        return new self(
            diagnosis: (string) ($payload['diagnostic_summary'] ?? ''),
            estimatedCost: $estimatedCost,
            requiresParts: $requiresParts,
            provider: $provider,
            tokensUsed: $tokensUsed,
            payload: $payload
        );
    }
}
