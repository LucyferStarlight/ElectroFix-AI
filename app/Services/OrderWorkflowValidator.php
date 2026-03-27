<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Exceptions\OrderWorkflowException;
use App\Support\OrderStatus;

class OrderWorkflowValidator
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

    public function canRepair(Order $order): bool
    {
        $status = (string) $order->status;

        if (! $order->isApproved()) {
            return false;
        }

        return in_array($status, [
            OrderStatus::APPROVED,
            OrderStatus::IN_REPAIR,
        ], true);
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

    public function ensureCanDiagnose(Order $order): void
    {
        if (! $this->canDiagnose($order)) {
            throw OrderWorkflowException::cannotDiagnose();
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
}
