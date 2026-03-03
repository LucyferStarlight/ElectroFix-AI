<?php

namespace App\Services;

class AiPlanPolicyService
{
    public const PLAN_ENTERPRISE = 'enterprise';
    public const PLAN_DEVELOPER_TEST = 'developer_test';

    private const QUERY_LIMITS = [
        self::PLAN_ENTERPRISE => 200,
        self::PLAN_DEVELOPER_TEST => 500,
    ];

    private const TOKEN_LIMITS = [
        self::PLAN_ENTERPRISE => 120000,
        self::PLAN_DEVELOPER_TEST => 500000,
    ];

    public function supportsAi(string $plan): bool
    {
        return array_key_exists($plan, self::QUERY_LIMITS);
    }

    public function queryLimit(string $plan): int
    {
        return self::QUERY_LIMITS[$plan] ?? 0;
    }

    public function tokenLimit(string $plan): int
    {
        return self::TOKEN_LIMITS[$plan] ?? 0;
    }
}

