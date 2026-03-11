<?php

namespace App\Support;

class ApiAbility
{
    public const ORDERS_READ = 'orders:read';
    public const ORDERS_WRITE = 'orders:write';
    public const BILLING_WRITE = 'billing:write';
    public const INVENTORY_WRITE = 'inventory:write';
    public const AI_USE = 'ai:use';

    public static function all(): array
    {
        return [
            self::ORDERS_READ,
            self::ORDERS_WRITE,
            self::BILLING_WRITE,
            self::INVENTORY_WRITE,
            self::AI_USE,
        ];
    }
}

