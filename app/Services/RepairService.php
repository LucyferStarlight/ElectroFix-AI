<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BillingDocument;
use App\Models\Order;
use App\Models\OrderRepairOutcome;
use App\Models\User;

class RepairService
{
    public function __construct(private readonly RepairOutcomeService $repairOutcomeService) {}

    public function closeFromBilling(BillingDocument $document, array $data): OrderRepairOutcome
    {
        return $this->repairOutcomeService->closeFromBillingDocument($document, $data);
    }

    public function markDelivered(Order $order, User $actor): OrderRepairOutcome
    {
        return $this->repairOutcomeService->markDelivered($order, $actor);
    }

    public function updateFeedback(Order $order, array $data): OrderRepairOutcome
    {
        return $this->repairOutcomeService->updateFeedback($order, $data);
    }
}
