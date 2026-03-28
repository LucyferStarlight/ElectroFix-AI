<?php

namespace App\Enums;

use InvalidArgumentException;

enum OrderStatus: string
{
    case CREATED = 'created';
    case DIAGNOSING = 'diagnosing';
    case QUOTED = 'quoted';
    case APPROVED = 'approved';
    case IN_REPAIR = 'in_repair';
    case COMPLETED = 'completed';
    case DELIVERED = 'delivered';
    case CLOSED = 'closed';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Creada',
            self::DIAGNOSING => 'Diagnóstico',
            self::QUOTED => 'Cotizada',
            self::APPROVED => 'Aprobada',
            self::IN_REPAIR => 'En reparación',
            self::COMPLETED => 'Completada',
            self::DELIVERED => 'Entregada',
            self::CLOSED => 'Cerrada',
            self::CANCELED => 'Cancelada',
        };
    }

    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }

    public static function acceptedValues(): array
    {
        return self::values();
    }

    public static function tryFromInput(self|string|null $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return self::tryFrom($normalized);
    }

    public static function fromInput(self|string $value): self
    {
        $status = self::tryFromInput($value);

        if ($status) {
            return $status;
        }

        throw new InvalidArgumentException(sprintf('Invalid order status [%s].', (string) $value));
    }
}
