<?php

namespace App\Services;

use App\Models\Order;

class OrderWorkflowValidator
{
    public function __construct(private readonly OrderWorkflowService $workflowService) {}

    public function canDiagnose(Order $order): bool
    {
        return $this->workflowService->canDiagnose($order);
    }

    public function canQuote(Order $order): bool
    {
        return $this->workflowService->canQuote($order);
    }

    public function canApprove(Order $order): bool
    {
        return $this->workflowService->canApprove($order);
    }

    public function canRepair(Order $order): bool
    {
        return $this->workflowService->canRepair($order);
    }

    public function canDeliver(Order $order): bool
    {
        return $this->workflowService->canDeliver($order);
    }

    public function canClose(Order $order): bool
    {
        return $this->workflowService->canClose($order);
    }

    public function ensureCanDiagnose(Order $order): void
    {
        $this->workflowService->ensureCanDiagnose($order);
    }

    public function ensureCanQuote(Order $order): void
    {
        $this->workflowService->ensureCanQuote($order);
    }

    public function ensureCanApprove(Order $order): void
    {
        $this->workflowService->ensureCanApprove($order);
    }

    public function ensureCanRepair(Order $order): void
    {
        $this->workflowService->ensureCanRepair($order);
    }

    public function ensureCanDeliver(Order $order): void
    {
        $this->workflowService->ensureCanDeliver($order);
    }

    public function ensureCanClose(Order $order): void
    {
        $this->workflowService->ensureCanClose($order);
    }
}
