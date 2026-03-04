<?php

namespace App\Support;

class TechnicianStatus
{
    public const AVAILABLE = 'available';
    public const ASSIGNED = 'assigned';
    public const INACTIVE = 'inactive';

    public static function all(): array
    {
        return [
            self::AVAILABLE,
            self::ASSIGNED,
            self::INACTIVE,
        ];
    }
}

