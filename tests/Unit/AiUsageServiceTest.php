<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\CompanyAiUsage;
use App\Services\AiPlanPolicyService;
use App\Services\AiTokenEstimator;
use App\Services\AiUsageService;
use App\Services\Exceptions\AiUsageException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_blocks_when_plan_has_no_ai_access(): void
    {
        $service = new AiUsageService(new AiPlanPolicyService(), new AiTokenEstimator());
        $company = Company::factory()->create();

        $this->expectException(AiUsageException::class);
        $this->expectExceptionMessage('Tu plan actual no incluye Asistente IA.');

        $service->validateBeforeUsage($company, 'starter', 100);
    }

    public function test_it_blocks_when_monthly_query_limit_is_reached(): void
    {
        $service = new AiUsageService(new AiPlanPolicyService(), new AiTokenEstimator());
        $company = Company::factory()->create();

        for ($i = 0; $i < 200; $i++) {
            CompanyAiUsage::query()->create([
                'company_id' => $company->id,
                'order_id' => null,
                'year_month' => now()->format('Y-m'),
                'plan_snapshot' => 'enterprise',
                'prompt_chars' => 100,
                'response_chars' => 200,
                'prompt_tokens_estimated' => 25,
                'response_tokens_estimated' => 50,
                'total_tokens_estimated' => 75,
                'status' => 'success',
                'error_message' => null,
            ]);
        }

        $this->expectException(AiUsageException::class);
        $this->expectExceptionMessage('Se alcanzó el límite mensual de consultas IA para tu empresa.');

        $service->validateBeforeUsage($company, 'enterprise', 20);
    }

    public function test_it_blocks_when_monthly_token_limit_is_reached(): void
    {
        $service = new AiUsageService(new AiPlanPolicyService(), new AiTokenEstimator());
        $company = Company::factory()->create();

        CompanyAiUsage::query()->create([
            'company_id' => $company->id,
            'order_id' => null,
            'year_month' => now()->format('Y-m'),
            'plan_snapshot' => 'enterprise',
            'prompt_chars' => 0,
            'response_chars' => 0,
            'prompt_tokens_estimated' => 0,
            'response_tokens_estimated' => 0,
            'total_tokens_estimated' => 119950,
            'status' => 'success',
            'error_message' => null,
        ]);

        $this->expectException(AiUsageException::class);
        $this->expectExceptionMessage('Se alcanzó el límite mensual de consumo IA para tu empresa.');

        $service->validateBeforeUsage($company, 'enterprise', 60);
    }
}

