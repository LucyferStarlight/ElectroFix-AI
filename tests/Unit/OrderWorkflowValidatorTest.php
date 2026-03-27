<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderRepairOutcome;
use App\Services\OrderWorkflowValidator;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_diagnose_when_order_is_active_and_not_processed(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::CREATED,
            'ai_diagnosed_at' => null,
            'ai_diagnosis_pending' => false,
        ]);

        $this->assertTrue(app(OrderWorkflowValidator::class)->canDiagnose($order));
    }

    public function test_cannot_diagnose_canceled_or_already_diagnosed_order(): void
    {
        $canceledOrder = Order::factory()->create([
            'status' => OrderStatus::CANCELED,
        ]);

        $diagnosedOrder = Order::factory()->create([
            'status' => OrderStatus::DIAGNOSING,
            'ai_diagnosed_at' => now(),
        ]);

        $validator = app(OrderWorkflowValidator::class);

        $this->assertFalse($validator->canDiagnose($canceledOrder));
        $this->assertFalse($validator->canDiagnose($diagnosedOrder));
    }

    public function test_can_repair_only_when_order_is_approved_and_not_outside_repair_flow(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => 'customer',
            'approval_channel' => 'whatsapp',
        ]);

        $this->assertTrue(app(OrderWorkflowValidator::class)->canRepair($order));
    }

    public function test_cannot_repair_without_formal_approval(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::APPROVED,
            'approved_at' => null,
            'approval_channel' => null,
        ]);

        $this->assertFalse(app(OrderWorkflowValidator::class)->canRepair($order));
    }

    public function test_can_deliver_only_when_order_is_completed_with_outcome_and_billing(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::COMPLETED,
        ]);

        $document = \App\Models\BillingDocument::factory()->create([
            'company_id' => $order->company_id,
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'document_type' => 'invoice',
        ]);

        \App\Models\BillingDocumentItem::factory()->create([
            'billing_document_id' => $document->id,
            'order_id' => $order->id,
            'inventory_item_id' => null,
            'item_kind' => 'service',
        ]);

        OrderRepairOutcome::query()->create([
            'order_id' => $order->id,
            'billing_document_id' => $document->id,
            'company_id' => $order->company_id,
            'repair_outcome' => 'repaired',
            'work_performed' => 'Reparación completa.',
            'actual_amount_charged' => 500,
            'had_ai_diagnosis' => false,
            'feeds_aris_training' => false,
            'plan_at_close' => 'starter',
        ]);

        $order->load('repairOutcome', 'billingItems');

        $this->assertTrue(app(OrderWorkflowValidator::class)->canDeliver($order));
    }
}
