<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\Exceptions\InvalidOrderStatusTransitionException;
use App\Services\Exceptions\OrderApprovalException;
use App\Services\OrderStateMachine;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_transition_through_supported_flow(): void
    {
        $machine = app(OrderStateMachine::class);

        $this->assertTrue($machine->canTransition(OrderStatus::CREATED, OrderStatus::DIAGNOSING));
        $this->assertTrue($machine->canTransition(OrderStatus::QUOTED, OrderStatus::APPROVED));
        $this->assertTrue($machine->canTransition(OrderStatus::APPROVED, OrderStatus::IN_REPAIR));
        $this->assertTrue($machine->canTransition(OrderStatus::COMPLETED, OrderStatus::DELIVERED));
        $this->assertTrue($machine->canTransition(OrderStatus::DELIVERED, OrderStatus::CLOSED));
    }

    public function test_rejects_invalid_transition(): void
    {
        $machine = app(OrderStateMachine::class);

        $this->assertFalse($machine->canTransition(OrderStatus::CREATED, OrderStatus::DELIVERED));
        $this->assertFalse($machine->canTransition(OrderStatus::CREATED, OrderStatus::IN_REPAIR));
    }

    public function test_transition_normalizes_legacy_statuses_before_persisting(): void
    {
        $order = Order::factory()->create([
            'status' => 'received',
        ]);

        app(OrderStateMachine::class)->transition($order, 'diagnostic');

        $this->assertSame(OrderStatus::DIAGNOSING, $order->fresh()->status);
    }

    public function test_transition_throws_exception_when_status_change_is_invalid(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::CREATED,
        ]);

        $this->expectException(InvalidOrderStatusTransitionException::class);

        app(OrderStateMachine::class)->transition($order, OrderStatus::DELIVERED);
    }

    public function test_in_repair_requires_formal_approval_context(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::APPROVED,
            'approved_at' => null,
            'approval_channel' => null,
        ]);

        $this->expectException(OrderApprovalException::class);

        app(OrderStateMachine::class)->transition($order, OrderStatus::IN_REPAIR);
    }

    public function test_order_approve_records_context_and_updates_status(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::QUOTED,
        ]);

        $order->approve('customer', 'whatsapp');

        $order->refresh();

        $this->assertSame(OrderStatus::APPROVED, $order->status);
        $this->assertTrue($order->isApproved());
        $this->assertSame('customer', $order->approved_by);
        $this->assertSame('whatsapp', $order->approval_channel);
        $this->assertNotNull($order->approved_at);
        $this->assertNull($order->rejected_at);
    }

    public function test_order_reject_records_reason_and_cancels_order(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::QUOTED,
            'approved_at' => now(),
            'approved_by' => 'customer',
            'approval_channel' => 'whatsapp',
        ]);

        $order->reject('El cliente no aceptó el presupuesto.');

        $order->refresh();

        $this->assertSame(OrderStatus::CANCELED, $order->status);
        $this->assertFalse($order->isApproved());
        $this->assertNotNull($order->rejected_at);
        $this->assertSame('El cliente no aceptó el presupuesto.', $order->rejection_reason);
        $this->assertNull($order->approved_at);
        $this->assertNull($order->approved_by);
        $this->assertNull($order->approval_channel);
    }
}
