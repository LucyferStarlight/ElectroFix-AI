<?php

namespace Tests\Unit;

use App\Models\AiUsage;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
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

    public function test_starter_plan_is_allowed_with_its_monthly_limit(): void
    {
        $service = $this->makeService();
        $company = Company::factory()->create();
        $this->attachSubscription($company);
        $this->ensurePlan('starter', [
            'ai_enabled' => true,
            'max_ai_requests' => 10,
            'max_ai_tokens' => 8000,
            'overage_enabled' => false,
        ]);

        $service->validateBeforeUsage($company, 'starter', 100);

        $this->assertTrue(true);
    }

    public function test_it_blocks_when_monthly_query_limit_is_reached_for_pro(): void
    {
        $service = $this->makeService();
        $company = Company::factory()->create();
        $this->attachSubscription($company);
        $this->ensurePlan('pro', [
            'ai_enabled' => true,
            'max_ai_requests' => 75,
            'max_ai_tokens' => 50000,
            'overage_enabled' => false,
        ]);

        AiUsage::query()->create([
            'company_id' => $company->id,
            'ai_requests_used' => 75,
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
        $this->attachSubscription($company);
        $this->ensurePlan('pro', [
            'ai_enabled' => true,
            'max_ai_requests' => 75,
            'max_ai_tokens' => 50000,
            'overage_enabled' => false,
        ]);

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

    private function ensurePlan(string $name, array $overrides): void
    {
        Plan::query()->updateOrCreate(
            ['name' => $name],
            array_merge([
                'is_public' => true,
                'ai_enabled' => false,
                'max_ai_requests' => 0,
                'max_ai_tokens' => 0,
                'overage_enabled' => false,
                'overage_price_per_request' => 0,
                'overage_price_per_1000_tokens' => 0,
            ], $overrides)
        );
    }

    private function attachSubscription(Company $company): void
    {
        Subscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'pro',
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->endOfMonth(),
        ]);
    }
}
