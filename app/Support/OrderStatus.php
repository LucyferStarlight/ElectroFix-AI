<?php

namespace App\Support;

class OrderStatus
{
    public const RECEIVED = 'received';
    public const DIAGNOSTIC = 'diagnostic';
    public const REPAIRING = 'repairing';
    public const QUOTE = 'quote';
    public const READY = 'ready';
    public const DELIVERED = 'delivered';
    public const NOT_REPAIRED = 'not_repaired';

    public static function all(): array
    {
        return [
            self::RECEIVED,
            self::DIAGNOSTIC,
            self::REPAIRING,
            self::QUOTE,
            self::READY,
            self::DELIVERED,
            self::NOT_REPAIRED,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::RECEIVED => 'Recibido',
            self::DIAGNOSTIC => 'Diagnóstico',
            self::REPAIRING => 'Reparación',
            self::QUOTE => 'Cotización',
            self::READY => 'Listo',
            self::DELIVERED => 'Entregado',
            self::NOT_REPAIRED => 'No reparado',
            default => ucfirst($status),
        };
    }
}
