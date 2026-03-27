<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Exceptions\OrderWorkflowException;
use App\Support\OrderStatus;

class OrderWorkflowService
{
    public function canDiagnose(Order $order): bool
    {
        $status = (string) $order->status;

        if (in_array($status, [
            OrderStatus::CANCELED,
            OrderStatus::DELIVERED,
            OrderStatus::CLOSED,
        ], true)) {
            return false;
        }

        if ($order->ai_diagnosed_at !== null || (bool) $order->ai_diagnosis_pending) {
            return false;
        }

        return true;
    }

    public function canQuote(Order $order): bool
    {
        return in_array((string) $order->status, [
            OrderStatus::CREATED,
            OrderStatus::DIAGNOSING,
            OrderStatus::QUOTED,
        ], true);
    }

    public function canApprove(Order $order): bool
    {
        if (! in_array((string) $order->status, [
            OrderStatus::QUOTED,
            OrderStatus::APPROVED,
        ], true)) {
            return false;
        }

        $activeQuote = $order->activeQuote()->first();

        if ($activeQuote === null) {
            return true;
        }

        return in_array($activeQuote->status, ['sent', 'approved'], true);
    }

    public function canRepair(Order $order): bool
    {
        $status = (string) $order->status;

        if (! in_array($status, [
            OrderStatus::APPROVED,
            OrderStatus::IN_REPAIR,
        ], true)) {
            return false;
        }

        if (! $order->isApproved()) {
            return false;
        }

        if ($order->hasQuotes() && ! $order->hasApprovedActiveQuote()) {
            return false;
        }

        return true;
    }

    public function canDeliver(Order $order): bool
    {
        $status = (string) $order->status;

        if (! in_array($status, [
            OrderStatus::COMPLETED,
            OrderStatus::DELIVERED,
        ], true)) {
            return false;
        }

        if (! $order->repairOutcome || ! $order->billingItems()->exists()) {
            return false;
        }

        return $order->repairOutcome->delivered_at === null;
    }

    public function canClose(Order $order): bool
    {
        if (! in_array((string) $order->status, [
            OrderStatus::COMPLETED,
            OrderStatus::DELIVERED,
            OrderStatus::CANCELED,
        ], true)) {
            return false;
        }

        if (! config('orders.require_payment_before_close', false)) {
            return true;
        }

        return $order->isFullyPaid() || (float) $order->paymentDueAmount() <= 0;
    }

    public function ensureCanDiagnose(Order $order): void
    {
        if (! $this->canDiagnose($order)) {
            throw OrderWorkflowException::cannotDiagnose();
        }
    }

    public function ensureCanQuote(Order $order): void
    {
        if (! $this->canQuote($order)) {
            throw OrderWorkflowException::cannotQuote();
        }
    }

    public function ensureCanApprove(Order $order): void
    {
        if (! $this->canApprove($order)) {
            throw OrderWorkflowException::cannotApprove();
        }
    }

    public function ensureCanRepair(Order $order): void
    {
        if (! $this->canRepair($order)) {
            throw OrderWorkflowException::cannotRepair();
        }
    }

    public function ensureCanDeliver(Order $order): void
    {
        if (! $this->canDeliver($order)) {
            throw OrderWorkflowException::cannotDeliver();
        }
    }

    public function ensureCanClose(Order $order): void
    {
        if (! $this->canClose($order)) {
            throw OrderWorkflowException::cannotCloseUntilPaid();
        }
    }
}
