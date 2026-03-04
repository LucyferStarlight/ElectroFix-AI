<?php

namespace App\Services;

use App\Models\AiUsage;
use App\Models\Company;
use App\Models\Plan;
use App\Services\Exceptions\AiUsageException;
use Illuminate\Support\Facades\DB;

class AiQuotaGuardService
{
    public function __construct(
        private readonly AiUsageCycleService $cycleService,
        private readonly AiOverageMeteringService $overageMeteringService
    ) {
    }

    public function ensureUsageRow(Company $company): AiUsage
    {
        $cycle = $this->cycleService->currentCycle($company);

        $usage = AiUsage::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'current_cycle_start' => $cycle['start']->toDateString(),
                'current_cycle_end' => $cycle['end']->toDateString(),
                'ai_requests_used' => 0,
                'ai_tokens_used' => 0,
                'overage_requests' => 0,
                'overage_tokens' => 0,
            ]
        );

        if ($usage->current_cycle_start->toDateString() !== $cycle['start']->toDateString()
            || $usage->current_cycle_end->toDateString() !== $cycle['end']->toDateString()) {
            $usage->update([
                'ai_requests_used' => 0,
                'ai_tokens_used' => 0,
                'overage_requests' => 0,
                'overage_tokens' => 0,
                'current_cycle_start' => $cycle['start']->toDateString(),
                'current_cycle_end' => $cycle['end']->toDateString(),
            ]);

            $usage->refresh();
        }

        return $usage;
    }

    public function validateBeforeUsage(Company $company, Plan $plan, int $projectedPromptTokens): void
    {
        if (! $plan->ai_enabled) {
            throw new AiUsageException('blocked_plan', 'Tu plan actual no incluye Asistente IA.');
        }

        $usage = $this->ensureUsageRow($company);

        $nextRequests = $usage->ai_requests_used + 1;
        $nextTokens = $usage->ai_tokens_used + $projectedPromptTokens;

        if (! $plan->overage_enabled && $nextRequests > (int) $plan->max_ai_requests) {
            throw new AiUsageException('blocked_quota', 'Se alcanzó el límite mensual de consultas IA para tu empresa.');
        }

        if (! $plan->overage_enabled && $nextTokens > (int) $plan->max_ai_tokens) {
            throw new AiUsageException('blocked_tokens', 'Se alcanzó el límite mensual de consumo IA para tu empresa.');
        }
    }

    public function incrementUsage(Company $company, Plan $plan, int $tokensUsed): array
    {
        return DB::transaction(function () use ($company, $plan, $tokensUsed): array {
            $this->ensureUsageRow($company);

            $usage = AiUsage::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newRequests = $usage->ai_requests_used + 1;
            $newTokens = $usage->ai_tokens_used + $tokensUsed;

            if (! $plan->overage_enabled && $newRequests > (int) $plan->max_ai_requests) {
                throw new AiUsageException('blocked_quota', 'Se alcanzó el límite mensual de consultas IA para tu empresa.');
            }

            if (! $plan->overage_enabled && $newTokens > (int) $plan->max_ai_tokens) {
                throw new AiUsageException('blocked_tokens', 'Se alcanzó el límite mensual de consumo IA para tu empresa.');
            }

            $previousOverageRequests = (int) $usage->overage_requests;
            $previousOverageTokens = (int) $usage->overage_tokens;
            $overageRequests = max(0, $newRequests - (int) $plan->max_ai_requests);
            $overageTokens = max(0, $newTokens - (int) $plan->max_ai_tokens);

            $usage->update([
                'ai_requests_used' => $newRequests,
                'ai_tokens_used' => $newTokens,
                'overage_requests' => $overageRequests,
                'overage_tokens' => $overageTokens,
            ]);

            if ($plan->overage_enabled) {
                $this->overageMeteringService->reportEnterpriseOverage(
                    $company,
                    max(0, $overageRequests - $previousOverageRequests),
                    max(0, $overageTokens - $previousOverageTokens)
                );
            }

            return [
                'requests_used' => $newRequests,
                'tokens_used' => $newTokens,
                'overage_requests' => $overageRequests,
                'overage_tokens' => $overageTokens,
                'cycle_start' => $usage->current_cycle_start,
                'cycle_end' => $usage->current_cycle_end,
            ];
        });
    }
}
