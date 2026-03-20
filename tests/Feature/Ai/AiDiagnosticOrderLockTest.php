<?php

namespace Tests\Feature\Ai;

use App\Contracts\AiDiagnosticProvider;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\User;
use App\Services\AiDiagnosticService;
use App\Services\Exceptions\AiQuotaExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDiagnosticOrderLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_with_ai_diagnosed_at_is_not_processed_again(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);
        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'ai_diagnosed_at' => now(),
        ]);
        $actor = User::factory()->create(['company_id' => $company->id]);

        $provider = $this->mock(AiDiagnosticProvider::class);
        $provider->shouldNotReceive('diagnose');

        $service = app(AiDiagnosticService::class);

        try {
            $service->diagnose($order, $company, $actor, 'No enciende');
            $this->fail('Expected AiQuotaExceededException to be thrown.');
        } catch (AiQuotaExceededException $exception) {
            $this->assertSame('already_diagnosed', $exception->status());
        }

        $this->assertDatabaseCount('company_ai_usages', 0);
    }
}
