<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Jobs\ProcessAiDiagnosticJob;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Support\ApiAbility;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class OrderDiagnosticRequestApiTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyWithRoles;

    public function test_diagnostic_request_is_sanitized_and_dispatched(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        $this->createActiveSubscription($company, ['plan' => 'starter']);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'status' => OrderStatus::CREATED,
            'ai_diagnosed_at' => null,
            'ai_diagnosis_pending' => false,
        ]);

        Sanctum::actingAs($worker, [ApiAbility::AI_USE]);
        Queue::fake();

        $response = $this->postJson('/api/v1/orders/'.$order->id.'/diagnostics', [
            'symptoms' => 'No enciende <script>alert(1)</script> y vibra demasiado',
        ]);

        $response->assertStatus(202);

        Queue::assertPushed(ProcessAiDiagnosticJob::class);
        $this->assertTrue((bool) $order->fresh()->ai_diagnosis_pending);
    }
}
