<?php

namespace Tests\Feature\Billing;

use App\Models\Company;
use App\Models\BillingDocument;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderDiagnostic;
use App\Models\OrderRepairOutcome;
use App\Services\Exceptions\OutcomeNotFoundException;
use App\Services\RepairOutcomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class RepairOutcomeTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyWithRoles;

    public function test_billing_repair_creates_outcome_record(): void
    {
        [$company, $worker, $order, $customer] = $this->makeBillingContext('starter');

        $this->actingAs($worker)
            ->post(route('worker.billing.store'), $this->repairPayload($order, $customer))
            ->assertRedirect();

        $this->assertDatabaseHas('order_repair_outcomes', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'repair_outcome' => 'repaired',
        ]);
    }

    public function test_starter_plan_always_feeds_aris(): void
    {
        [$company, $worker, $order, $customer] = $this->makeBillingContext('starter', false);

        $this->actingAs($worker)
            ->post(route('worker.billing.store'), $this->repairPayload($order, $customer))
            ->assertRedirect();

        $this->assertDatabaseHas('order_repair_outcomes', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'had_ai_diagnosis' => false,
            'feeds_aris_training' => true,
            'plan_at_close' => 'starter',
        ]);
    }

    public function test_pro_with_ai_diagnosis_feeds_aris(): void
    {
        [$company, $worker, $order, $customer] = $this->makeBillingContext('pro', true);

        $this->actingAs($worker)
            ->post(route('worker.billing.store'), $this->repairPayload($order, $customer))
            ->assertRedirect();

        $this->assertDatabaseHas('order_repair_outcomes', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'had_ai_diagnosis' => true,
            'feeds_aris_training' => true,
            'plan_at_close' => 'pro',
        ]);
    }

    public function test_pro_without_ai_diagnosis_does_not_feed_aris(): void
    {
        [$company, $worker, $order, $customer] = $this->makeBillingContext('pro', false);

        $this->actingAs($worker)
            ->post(route('worker.billing.store'), $this->repairPayload($order, $customer))
            ->assertRedirect();

        $this->assertDatabaseHas('order_repair_outcomes', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'had_ai_diagnosis' => false,
            'feeds_aris_training' => false,
            'plan_at_close' => 'pro',
        ]);
    }

    public function test_partial_outcome_requires_notes(): void
    {
        [, $worker, $order, $customer] = $this->makeBillingContext('starter');

        $this->actingAs($worker)
            ->from(route('worker.billing'))
            ->post(route('worker.billing.store'), $this->repairPayload($order, $customer, [
                'repair_outcome' => 'partial',
                'outcome_notes' => null,
            ]))
            ->assertRedirect(route('worker.billing'))
            ->assertSessionHasErrors('outcome_notes');
    }

    public function test_repaired_outcome_does_not_require_notes(): void
    {
        [$company, $worker, $order, $customer] = $this->makeBillingContext('starter');

        $this->actingAs($worker)
            ->post(route('worker.billing.store'), $this->repairPayload($order, $customer, [
                'repair_outcome' => 'repaired',
                'outcome_notes' => null,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('order_repair_outcomes', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'repair_outcome' => 'repaired',
            'outcome_notes' => null,
        ]);
    }

    public function test_aris_estimated_cost_copied_from_diagnosis(): void
    {
        [$company, $worker, $order, $customer] = $this->makeBillingContext('enterprise', true);

        OrderDiagnostic::query()->create([
            'company_id' => $company->id,
            'order_id' => $order->id,
            'version' => 1,
            'source' => 'ai',
            'replacement_total_cost' => 1325.75,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.billing.store'), $this->repairPayload($order, $customer))
            ->assertRedirect();

        $this->assertDatabaseHas('order_repair_outcomes', [
            'company_id' => $company->id,
            'order_id' => $order->id,
            'aris_estimated_cost' => 1325.75,
        ]);
    }

    public function test_deliver_marks_delivered_at(): void
    {
        [$company, $worker, $order, $customer] = $this->makeBillingContext('starter');

        $this->actingAs($worker)
            ->post(route('worker.billing.store'), $this->repairPayload($order, $customer))
            ->assertRedirect();

        $outcome = OrderRepairOutcome::query()->where('company_id', $company->id)->where('order_id', $order->id)->firstOrFail();
        $this->assertNull($outcome->delivered_at);

        app(RepairOutcomeService::class)->markDelivered($order->fresh(), $worker);

        $this->assertNotNull($outcome->fresh()->delivered_at);
    }

    public function test_cannot_deliver_without_outcome(): void
    {
        [, $worker, $order] = $this->makeBillingContext('starter');

        $this->expectException(OutcomeNotFoundException::class);
        app(RepairOutcomeService::class)->markDelivered($order->fresh(), $worker);
    }

    public function test_outcome_is_scoped_to_company(): void
    {
        [$companyA, $workerA, $orderA, $customerA] = $this->makeBillingContext('starter');
        [$companyB, $workerB, $orderB, $customerB] = $this->makeBillingContext('starter');

        $this->actingAs($workerA)
            ->post(route('worker.billing.store'), $this->repairPayload($orderA, $customerA))
            ->assertRedirect();
        $this->actingAs($workerB)
            ->post(route('worker.billing.store'), $this->repairPayload($orderB, $customerB))
            ->assertRedirect();

        $companyAOutcomes = OrderRepairOutcome::query()->forCompany($companyA->id)->get();
        $this->assertCount(1, $companyAOutcomes);
        $this->assertTrue($companyAOutcomes->every(fn (OrderRepairOutcome $outcome) => $outcome->company_id === $companyA->id));
        $this->assertFalse($companyAOutcomes->contains(fn (OrderRepairOutcome $outcome) => $outcome->company_id === $companyB->id));
    }

    public function test_scope_for_aris_training_returns_only_flagged_records(): void
    {
        [$companyA, $workerA, $orderA, $customerA] = $this->makeBillingContext('starter', false);
        [$companyB, $workerB, $orderB, $customerB] = $this->makeBillingContext('pro', false);

        $this->actingAs($workerA)
            ->post(route('worker.billing.store'), $this->repairPayload($orderA, $customerA))
            ->assertRedirect();
        $this->actingAs($workerB)
            ->post(route('worker.billing.store'), $this->repairPayload($orderB, $customerB))
            ->assertRedirect();

        $trainingRows = OrderRepairOutcome::query()->forArisTraining()->get();

        $this->assertCount(1, $trainingRows);
        $this->assertSame($companyA->id, $trainingRows->first()->company_id);
        $this->assertTrue((bool) $trainingRows->first()->feeds_aris_training);
    }

    public function test_billing_venta_does_not_create_outcome(): void
    {
        [$company, $admin] = $this->createCompanyWithRoles([], [], ['can_access_billing' => true]);
        $this->createActiveSubscription($company, ['plan' => 'starter']);

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $item = \App\Models\InventoryItem::factory()->create([
            'company_id' => $company->id,
            'quantity' => 10,
            'is_sale_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('worker.billing.store'), [
                'document_type' => 'invoice',
                'source' => 'sale',
                'customer_mode' => 'registered',
                'customer_id' => $customer->id,
                'tax_mode' => 'excluded',
                'items' => [
                    [
                        'item_kind' => 'product',
                        'description' => 'Venta mostrador',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'inventory_item_id' => $item->id,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(0, OrderRepairOutcome::query()->where('company_id', $company->id)->count());
    }

    private function makeBillingContext(string $plan, bool $withAiDiagnosis = false): array
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles([], [], ['can_access_billing' => true]);
        $this->createActiveSubscription($company, ['plan' => $plan]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
        ]);

        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => 'customer',
            'approval_channel' => 'whatsapp',
            'ai_diagnosed_at' => $withAiDiagnosis ? now() : null,
        ]);

        BillingDocument::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'document_type' => 'quote',
            'source' => 'repair',
            'status' => 'approved',
            'version' => 1,
            'is_active' => true,
        ]);

        return [$company, $worker, $order, $customer, $admin];
    }

    private function repairPayload(Order $order, Customer $customer, array $overrides = []): array
    {
        return array_merge([
            'document_type' => 'invoice',
            'source' => 'repair',
            'customer_mode' => 'registered',
            'customer_id' => $customer->id,
            'tax_mode' => 'excluded',
            'repair_outcome' => 'repaired',
            'work_performed' => 'Reemplazo de componente, limpieza interna y prueba de funcionamiento.',
            'actual_amount_charged' => 700.00,
            'items' => [
                [
                    'item_kind' => 'service',
                    'description' => 'Servicio de reparación',
                    'quantity' => 1,
                    'unit_price' => 700,
                    'order_id' => $order->id,
                ],
            ],
        ], $overrides);
    }
}
