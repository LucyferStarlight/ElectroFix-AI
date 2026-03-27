<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Exceptions\InvalidOrderStatusTransitionException;
use App\Services\Exceptions\OrderApprovalException;
use App\Services\Exceptions\OrderWorkflowException;

class OrderStateMachine
{
    public function __construct(private readonly OrderWorkflowService $orderWorkflowService) {}

    public function canTransition(OrderStatus|string|null $from, OrderStatus|string $to): bool
    {
        $fromStatus = OrderStatus::tryFromInput($from);
        $toStatus = OrderStatus::fromInput($to);

        if ($fromStatus === null) {
            return true;
        }

        if ($fromStatus === $toStatus) {
            return true;
        }

        return in_array($toStatus, $this->allowedTransitions($fromStatus), true);
    }

    public function transition(Order $order, OrderStatus|string $newStatus): Order
    {
        $rawStatus = (string) $order->getRawOriginal('status');
        $fromStatus = OrderStatus::fromInput($rawStatus);
        $toStatus = OrderStatus::fromInput($newStatus);

        $this->guardApprovalRules($order, $toStatus);

        if (! $this->canTransition($fromStatus, $toStatus)) {
            throw new InvalidOrderStatusTransitionException(
                $fromStatus,
                $toStatus,
                $order,
                $this->allowedTransitions($fromStatus)
            );
        }

        if ($fromStatus === $toStatus && $rawStatus === $toStatus->value) {
            return $order;
        }

        $order->status = $toStatus->value;
        $order->save();

        return $order;
    }

    public function availableTransitions(OrderStatus|string|null $from): array
    {
        $fromStatus = OrderStatus::tryFromInput($from);

        if ($fromStatus === null) {
            return OrderStatus::cases();
        }

        return $this->allowedTransitions($fromStatus);
    }

    private function allowedTransitions(OrderStatus $from): array
    {
        return match ($from) {
            OrderStatus::CREATED => [
                OrderStatus::DIAGNOSING,
                OrderStatus::QUOTED,
                OrderStatus::APPROVED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELED,
            ],
            OrderStatus::DIAGNOSING => [
                OrderStatus::QUOTED,
                OrderStatus::APPROVED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELED,
            ],
            OrderStatus::QUOTED => [
                OrderStatus::APPROVED,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELED,
            ],
            OrderStatus::APPROVED => [
                OrderStatus::IN_REPAIR,
                OrderStatus::COMPLETED,
                OrderStatus::CANCELED,
            ],
            OrderStatus::IN_REPAIR => [
                OrderStatus::COMPLETED,
                OrderStatus::CANCELED,
            ],
            OrderStatus::COMPLETED => [
                OrderStatus::DELIVERED,
                OrderStatus::CLOSED,
            ],
            OrderStatus::DELIVERED => [
                OrderStatus::CLOSED,
            ],
            OrderStatus::CANCELED => [
                OrderStatus::CLOSED,
            ],
            OrderStatus::CLOSED => [],
        };
    }

    private function guardApprovalRules(Order $order, OrderStatus $toStatus): void
    {
        if ($toStatus === OrderStatus::APPROVED && ! $order->isApproved()) {
            throw OrderApprovalException::approvalContextRequired();
        }

        if ($toStatus === OrderStatus::IN_REPAIR) {
            if (! $order->isApproved()) {
                throw OrderApprovalException::approvalRequiredForRepair();
            }

            if (! $this->orderWorkflowService->canRepair($order)) {
                throw OrderWorkflowException::cannotRepair();
            }
        }

        if ($toStatus === OrderStatus::CLOSED && ! $this->orderWorkflowService->canClose($order)) {
            throw OrderWorkflowException::cannotCloseUntilPaid();
        }
    }
}
