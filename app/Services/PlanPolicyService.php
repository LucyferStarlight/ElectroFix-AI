<?php

namespace App\Services;

use App\Models\Plan;

class PlanPolicyService
{
    private const DEFAULTS = [
        'starter' => [
            'ai_enabled' => false,
            'max_ai_requests' => 0,
            'max_ai_tokens' => 0,
            'overage_enabled' => false,
            'overage_price_per_request' => 0,
            'overage_price_per_1000_tokens' => 0,
        ],
        'pro' => [
            'ai_enabled' => true,
            'max_ai_requests' => 80,
            'max_ai_tokens' => 50000,
            'overage_enabled' => false,
            'overage_price_per_request' => 0,
            'overage_price_per_1000_tokens' => 0,
        ],
        'enterprise' => [
            'ai_enabled' => true,
            'max_ai_requests' => 200,
            'max_ai_tokens' => 120000,
            'overage_enabled' => true,
            'overage_price_per_request' => 2.5,
            'overage_price_per_1000_tokens' => 0.45,
        ],
        'developer_test' => [
            'ai_enabled' => true,
            'max_ai_requests' => 300,
            'max_ai_tokens' => 500000,
            'overage_enabled' => false,
            'overage_price_per_request' => 0,
            'overage_price_per_1000_tokens' => 0,
        ],
    ];

    public function planFor(string $planName): ?Plan
    {
        try {
            $plan = Plan::query()->where('name', $planName)->first();
            if ($plan) {
                return $plan;
            }
        } catch (\Throwable $e) {
            // Continue with fallback defaults.
        }

        if (! array_key_exists($planName, self::DEFAULTS)) {
            return null;
        }

        $defaults = self::DEFAULTS[$planName];

        return new Plan([
            'name' => $planName,
            'is_public' => $planName !== 'developer_test',
            'ai_enabled' => $defaults['ai_enabled'],
            'max_ai_requests' => $defaults['max_ai_requests'],
            'max_ai_tokens' => $defaults['max_ai_tokens'],
            'overage_enabled' => $defaults['overage_enabled'],
            'overage_price_per_request' => $defaults['overage_price_per_request'],
            'overage_price_per_1000_tokens' => $defaults['overage_price_per_1000_tokens'],
        ]);
    }

    private function effective(string $planName): array
    {
        $defaults = self::DEFAULTS[$planName] ?? self::DEFAULTS['starter'];
        $plan = $this->planFor($planName);
        if (! $plan) {
            return $defaults;
        }

        return [
            'ai_enabled' => (bool) $plan->ai_enabled,
            'max_ai_requests' => (int) $plan->max_ai_requests,
            'max_ai_tokens' => (int) $plan->max_ai_tokens,
            'overage_enabled' => (bool) $plan->overage_enabled,
            'overage_price_per_request' => (float) $plan->overage_price_per_request,
            'overage_price_per_1000_tokens' => (float) $plan->overage_price_per_1000_tokens,
        ];
    }

    public function aiEnabled(string $planName): bool
    {
        return (bool) $this->effective($planName)['ai_enabled'];
    }

    public function maxAiRequests(string $planName): int
    {
        return (int) $this->effective($planName)['max_ai_requests'];
    }

    public function maxAiTokens(string $planName): int
    {
        return (int) $this->effective($planName)['max_ai_tokens'];
    }

    public function overageEnabled(string $planName): bool
    {
        return (bool) $this->effective($planName)['overage_enabled'];
    }

    public function overagePricePerRequest(string $planName): float
    {
        return (float) $this->effective($planName)['overage_price_per_request'];
    }

    public function overagePricePer1000Tokens(string $planName): float
    {
        return (float) $this->effective($planName)['overage_price_per_1000_tokens'];
    }
}
