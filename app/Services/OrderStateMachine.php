<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Exceptions\InvalidOrderStatusTransitionException;
use App\Services\Exceptions\OrderWorkflowException;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($order, $newStatus): Order {
            $lockedOrder = Order::query()
                ->with(['customer:id,company_id', 'equipment:id,company_id,customer_id'])
                ->lockForUpdate()
                ->find($order->id);

            if (! $lockedOrder) {
                throw OrderWorkflowException::orderNotFoundForTransition();
            }

            $lockedOrder->assertCriticalRelations();

            $rawStatus = (string) $lockedOrder->getRawOriginal('status');
            $fromStatus = OrderStatus::fromInput($rawStatus);
            $toStatus = OrderStatus::fromInput($newStatus);

            if (! $this->canTransition($fromStatus, $toStatus)) {
                throw new InvalidOrderStatusTransitionException(
                    $fromStatus,
                    $toStatus,
                    $lockedOrder,
                    $this->allowedTransitions($fromStatus)
                );
            }

            $this->orderWorkflowService->ensureCanTransitionTo($lockedOrder, $toStatus->value);

            if ($fromStatus === $toStatus && $rawStatus === $toStatus->value) {
                return $lockedOrder;
            }

            $lockedOrder->status = $toStatus->value;
            $lockedOrder->save();

            return $lockedOrder;
        });
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

}
