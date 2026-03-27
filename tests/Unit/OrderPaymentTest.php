<?php

namespace Tests\Unit;

use App\Enums\OrderPaymentStatus;
use App\Models\Order;
use App\Services\Exceptions\OrderPaymentException;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_payment_marks_order_as_partial_until_total_is_covered(): void
    {
        $order = Order::factory()->create([
            'estimated_cost' => 1000,
            'payment_status' => OrderPaymentStatus::PENDING->value,
            'total_paid' => 0,
        ]);

        $order->registerPayment(400);

        $order->refresh();

        $this->assertSame(OrderPaymentStatus::PARTIAL->value, $order->payment_status);
        $this->assertSame(400.0, (float) $order->total_paid);
        $this->assertFalse($order->isFullyPaid());
        $this->assertSame(600.0, $order->outstandingBalance());
    }

    public function test_register_payment_marks_order_as_paid_when_total_is_covered(): void
    {
        $order = Order::factory()->create([
            'estimated_cost' => 1000,
            'payment_status' => OrderPaymentStatus::PENDING->value,
            'total_paid' => 0,
            'status' => OrderStatus::APPROVED,
        ]);

        $order->registerPayment(600);
        $order->registerPayment(400);

        $order->refresh();

        $this->assertSame(OrderPaymentStatus::PAID->value, $order->payment_status);
        $this->assertSame(1000.0, (float) $order->total_paid);
        $this->assertTrue($order->isFullyPaid());
        $this->assertSame(0.0, $order->outstandingBalance());
    }

    public function test_register_refund_marks_order_as_refunded_when_net_paid_returns_to_zero(): void
    {
        $order = Order::factory()->create([
            'estimated_cost' => 800,
            'payment_status' => OrderPaymentStatus::PENDING->value,
            'total_paid' => 0,
        ]);

        $order->registerPayment(800);
        $order->registerRefund(800);

        $order->refresh();

        $this->assertSame(OrderPaymentStatus::REFUNDED->value, $order->payment_status);
        $this->assertSame(0.0, (float) $order->total_paid);
        $this->assertFalse($order->isFullyPaid());
    }

    public function test_cannot_refund_more_than_total_paid(): void
    {
        $order = Order::factory()->create([
            'estimated_cost' => 800,
        ]);

        $order->registerPayment(300);

        $this->expectException(OrderPaymentException::class);

        $order->registerRefund(350);
    }
}
