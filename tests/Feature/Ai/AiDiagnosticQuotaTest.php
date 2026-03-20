<?php

namespace Tests\Feature\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\Models\AiUsage;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AiDiagnosticService;
use App\Services\Exceptions\AiQuotaExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDiagnosticQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_diagnostic_is_blocked_when_monthly_quota_is_exceeded(): void
    {
        $company = Company::factory()->create();

        Plan::query()->updateOrCreate(['name' => 'pro'], [
            'name' => 'pro',
            'is_public' => false,
            'ai_enabled' => true,
            'max_ai_requests' => 1,
            'max_ai_tokens' => 500,
            'overage_enabled' => false,
            'overage_price_per_request' => 0,
            'overage_price_per_1000_tokens' => 0,
        ]);

        Subscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'pro',
            'status' => 'active',
        ]);

        AiUsage::query()->create([
            'company_id' => $company->id,
            'ai_requests_used' => 1,
            'ai_tokens_used' => 0,
            'overage_requests' => 0,
            'overage_tokens' => 0,
            'current_cycle_start' => now()->startOfMonth()->toDateString(),
            'current_cycle_end' => now()->endOfMonth()->toDateString(),
        ]);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);
        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
        ]);
        $actor = User::factory()->create(['company_id' => $company->id]);

        $provider = $this->mock(AiDiagnosticProvider::class);
        $provider->shouldNotReceive('diagnose');

        $service = app(AiDiagnosticService::class);

        try {
            $service->diagnose($order, $company, $actor, 'No enciende desde ayer');
            $this->fail('Expected AiQuotaExceededException to be thrown.');
        } catch (AiQuotaExceededException $exception) {
            $this->assertSame('blocked_quota', $exception->status());
        }

        $this->assertDatabaseHas('company_ai_usages', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'status' => 'blocked_quota',
        ]);
    }
}
