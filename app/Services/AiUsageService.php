<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyAiUsage;
use App\Models\Order;
use App\Services\Exceptions\AiUsageException;

class AiUsageService
{
    public function __construct(
        private readonly PlanPolicyService $planPolicyService,
        private readonly AiTokenEstimator $tokenEstimator,
        private readonly AiQuotaGuardService $quotaGuardService
    ) {
    }

    public function validateBeforeUsage(Company $company, string $plan, int $projectedPromptTokens, ?string $yearMonth = null): void
    {
        $planModel = $this->planPolicyService->planFor($plan);
        if (! $planModel || ! $planModel->ai_enabled) {
            throw new AiUsageException('blocked_plan', 'Tu plan actual no incluye Asistente IA.');
        }

        $this->quotaGuardService->validateBeforeUsage($company, $planModel, $projectedPromptTokens);
    }

    public function validateAfterUsage(Company $company, string $plan, int $realTotalTokens, ?string $yearMonth = null): void
    {
        $planModel = $this->planPolicyService->planFor($plan);
        if (! $planModel || ! $planModel->ai_enabled) {
            throw new AiUsageException('blocked_plan', 'Tu plan actual no incluye Asistente IA.');
        }

        // Definitive transactional check + increment to avoid race conditions.
        $this->quotaGuardService->reserveAndCommitUsage($company, $planModel, $realTotalTokens);
    }

    public function commitSuccessfulUsage(
        Company $company,
        Order $order,
        string $plan,
        int $promptChars,
        int $responseChars,
        int $realTotalTokens
    ): CompanyAiUsage {
        $planModel = $this->planPolicyService->planFor($plan);
        if (! $planModel || ! $planModel->ai_enabled) {
            throw new AiUsageException('blocked_plan', 'Tu plan actual no incluye Asistente IA.');
        }

        $this->quotaGuardService->reserveAndCommitUsage($company, $planModel, $realTotalTokens);

        return $this->registerSuccess($company, $order, $plan, $promptChars, $responseChars);
    }

    public function monthlyUsage(Company $company, ?string $yearMonth = null): array
    {
        $summary = $this->quotaGuardService->ensureUsageRow($company);

        return [
            'queries_used' => (int) $summary->ai_requests_used,
            'tokens_used' => (int) $summary->ai_tokens_used,
            'overage_requests' => (int) $summary->overage_requests,
            'overage_tokens' => (int) $summary->overage_tokens,
        ];
    }

    public function registerSuccess(
        Company $company,
        Order $order,
        string $plan,
        int $promptChars,
        int $responseChars,
        ?string $yearMonth = null
    ): CompanyAiUsage {
        $summary = $this->quotaGuardService->ensureUsageRow($company);
        $yearMonth = $summary->current_cycle_start->format('Y-m');

        $promptTokens = $this->tokenEstimator->estimateFromChars($promptChars);
        $responseTokens = $this->tokenEstimator->estimateFromChars($responseChars);
        $totalTokens = $promptTokens + $responseTokens;

        return CompanyAiUsage::query()->create([
            'company_id' => $company->id,
            'order_id' => $order->id,
            'year_month' => $yearMonth,
            'plan_snapshot' => $plan,
            'prompt_chars' => $promptChars,
            'response_chars' => $responseChars,
            'prompt_tokens_estimated' => $promptTokens,
            'response_tokens_estimated' => $responseTokens,
            'total_tokens_estimated' => $totalTokens,
            'status' => 'success',
            'error_message' => null,
        ]);
    }

    public function registerBlocked(
        Company $company,
        ?Order $order,
        string $plan,
        string $status,
        string $errorMessage,
        int $promptChars = 0,
        int $responseChars = 0,
        ?string $yearMonth = null
    ): CompanyAiUsage {
        $summary = $this->quotaGuardService->ensureUsageRow($company);
        $yearMonth = $summary->current_cycle_start->format('Y-m');

        return CompanyAiUsage::query()->create([
            'company_id' => $company->id,
            'order_id' => $order?->id,
            'year_month' => $yearMonth,
            'plan_snapshot' => $plan,
            'prompt_chars' => $promptChars,
            'response_chars' => $responseChars,
            'prompt_tokens_estimated' => $this->tokenEstimator->estimateFromChars($promptChars),
            'response_tokens_estimated' => $this->tokenEstimator->estimateFromChars($responseChars),
            'total_tokens_estimated' => $this->tokenEstimator->estimateFromChars($promptChars + $responseChars),
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    public function calculateEnterpriseOverageCost(string $plan, int $overageRequests, int $overageTokens): array
    {
        $extraRequestsCost = $overageRequests * $this->planPolicyService->overagePricePerRequest($plan);
        $extraTokensCost = ($overageTokens / 1000) * $this->planPolicyService->overagePricePer1000Tokens($plan);

        return [
            'extra_requests_cost' => round($extraRequestsCost, 2),
            'extra_tokens_cost' => round($extraTokensCost, 2),
            'total_overage_monthly' => round($extraRequestsCost + $extraTokensCost, 2),
        ];
    }
}
