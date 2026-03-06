<?php

namespace Tests\Unit;

use App\Models\AiUsage;
use App\Models\Company;
use App\Services\AiOverageMeteringService;
use App\Services\AiQuotaGuardService;
use App\Services\AiTokenEstimator;
use App\Services\AiUsageCycleService;
use App\Services\AiUsageService;
use App\Services\Exceptions\AiUsageException;
use App\Services\PlanPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_blocks_when_plan_has_no_ai_access(): void
    {
        $service = $this->makeService();
        $company = Company::factory()->create();

        $this->expectException(AiUsageException::class);
        $this->expectExceptionMessage('Tu plan actual no incluye Asistente IA.');

        $service->validateBeforeUsage($company, 'starter', 100);
    }

    public function test_it_blocks_when_monthly_query_limit_is_reached_for_pro(): void
    {
        $service = $this->makeService();
        $company = Company::factory()->create();

        AiUsage::query()->create([
            'company_id' => $company->id,
            'ai_requests_used' => 80,
            'ai_tokens_used' => 1200,
            'current_cycle_start' => now()->startOfMonth()->toDateString(),
            'current_cycle_end' => now()->endOfMonth()->toDateString(),
            'overage_requests' => 0,
            'overage_tokens' => 0,
        ]);

        $this->expectException(AiUsageException::class);
        $this->expectExceptionMessage('Se alcanzó el límite mensual de consultas IA para tu empresa.');

        $service->validateBeforeUsage($company, 'pro', 20);
    }

    public function test_it_blocks_when_monthly_token_limit_is_reached_for_pro(): void
    {
        $service = $this->makeService();
        $company = Company::factory()->create();

        AiUsage::query()->create([
            'company_id' => $company->id,
            'ai_requests_used' => 8,
            'ai_tokens_used' => 49990,
            'current_cycle_start' => now()->startOfMonth()->toDateString(),
            'current_cycle_end' => now()->endOfMonth()->toDateString(),
            'overage_requests' => 0,
            'overage_tokens' => 0,
        ]);

        $this->expectException(AiUsageException::class);
        $this->expectExceptionMessage('Se alcanzó el límite mensual de consumo IA para tu empresa.');

        $service->validateBeforeUsage($company, 'pro', 20);
    }

    private function makeService(): AiUsageService
    {
        $quotaGuard = new AiQuotaGuardService(
            new AiUsageCycleService(),
            new AiOverageMeteringService()
        );

        return new AiUsageService(
            new PlanPolicyService(),
            new AiTokenEstimator(),
            $quotaGuard
        );
    }
}

