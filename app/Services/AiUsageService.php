<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyAiUsage;
use App\Models\Order;
use App\Services\Exceptions\AiUsageException;

class AiUsageService
{
    public function __construct(
        private readonly AiPlanPolicyService $planPolicyService,
        private readonly AiTokenEstimator $tokenEstimator
    ) {
    }

    public function validateBeforeUsage(Company $company, string $plan, int $projectedPromptTokens, ?string $yearMonth = null): void
    {
        $yearMonth ??= now()->format('Y-m');

        if (! $this->planPolicyService->supportsAi($plan)) {
            throw new AiUsageException('blocked_plan', 'Tu plan actual no incluye Asistente IA.');
        }

        $usage = $this->monthlyUsage($company, $yearMonth);

        if ($usage['queries_used'] >= $this->planPolicyService->queryLimit($plan)) {
            throw new AiUsageException('blocked_quota', 'Se alcanzó el límite mensual de consultas IA para tu empresa.');
        }

        if (($usage['tokens_used'] + $projectedPromptTokens) > $this->planPolicyService->tokenLimit($plan)) {
            throw new AiUsageException('blocked_tokens', 'Se alcanzó el límite mensual de consumo IA para tu empresa.');
        }
    }

    public function validateAfterUsage(Company $company, string $plan, int $realTotalTokens, ?string $yearMonth = null): void
    {
        $yearMonth ??= now()->format('Y-m');
        $usage = $this->monthlyUsage($company, $yearMonth);

        if (($usage['tokens_used'] + $realTotalTokens) > $this->planPolicyService->tokenLimit($plan)) {
            throw new AiUsageException('blocked_tokens', 'Se alcanzó el límite mensual de consumo IA para tu empresa.');
        }
    }

    public function monthlyUsage(Company $company, ?string $yearMonth = null): array
    {
        $yearMonth ??= now()->format('Y-m');

        $successRows = CompanyAiUsage::query()
            ->where('company_id', $company->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'success');

        return [
            'queries_used' => (int) (clone $successRows)->count(),
            'tokens_used' => (int) (clone $successRows)->sum('total_tokens_estimated'),
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
        $yearMonth ??= now()->format('Y-m');
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
        $yearMonth ??= now()->format('Y-m');

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
}
