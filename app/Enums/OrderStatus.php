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
        return array_values(array_unique([
            ...self::values(),
            ...array_keys(self::legacyMap()),
        ]));
    }

    public static function legacyMap(): array
    {
        return [
            'received' => self::CREATED,
            'diagnostic' => self::DIAGNOSING,
            'quote' => self::QUOTED,
            'repairing' => self::IN_REPAIR,
            'ready' => self::COMPLETED,
            'not_repaired' => self::CANCELED,
        ];
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

        return self::tryFrom($normalized)
            ?? self::legacyMap()[$normalized]
            ?? null;
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
