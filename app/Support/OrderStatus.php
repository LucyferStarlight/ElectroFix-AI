<?php

namespace App\Support;

use App\Enums\OrderStatus as OrderStatusEnum;

class OrderStatus
{
    public const CREATED = OrderStatusEnum::CREATED->value;

    public const DIAGNOSING = OrderStatusEnum::DIAGNOSING->value;

    public const QUOTED = OrderStatusEnum::QUOTED->value;

    public const APPROVED = OrderStatusEnum::APPROVED->value;

    public const IN_REPAIR = OrderStatusEnum::IN_REPAIR->value;

    public const COMPLETED = OrderStatusEnum::COMPLETED->value;

    public const DELIVERED = OrderStatusEnum::DELIVERED->value;

    public const CLOSED = OrderStatusEnum::CLOSED->value;

    public const CANCELED = OrderStatusEnum::CANCELED->value;

    public static function all(): array
    {
        return OrderStatusEnum::values();
    }

    public static function acceptedValues(): array
    {
        return OrderStatusEnum::acceptedValues();
    }

    public static function label(string $status): string
    {
        return OrderStatusEnum::tryFromInput($status)?->label() ?? ucfirst($status);
    }

    public static function normalize(string $status): string
    {
        return OrderStatusEnum::fromInput($status)->value;
    }

    public static function badgeClass(string $status): string
    {
        return match (self::normalize($status)) {
            self::COMPLETED, self::DELIVERED, self::CLOSED => 'badge-ui-success',
            self::QUOTED, self::APPROVED => 'badge-ui-warning',
            self::CANCELED => 'badge-ui-danger',
            default => 'badge-ui-info',
        };
    }
}
