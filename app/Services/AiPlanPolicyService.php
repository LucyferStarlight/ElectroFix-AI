<?php

namespace App\Services;

class AiPlanPolicyService
{
    public function __construct(private readonly ?PlanPolicyService $planPolicyService = null)
    {
    }

    public function supportsAi(string $plan): bool
    {
        return $this->policy()->aiEnabled($plan);
    }

    public function queryLimit(string $plan): int
    {
        return $this->policy()->maxAiRequests($plan);
    }

    public function tokenLimit(string $plan): int
    {
        return $this->policy()->maxAiTokens($plan);
    }

    private function policy(): PlanPolicyService
    {
        return $this->planPolicyService ?? app(PlanPolicyService::class);
    }
}
