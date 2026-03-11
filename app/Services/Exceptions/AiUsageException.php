<?php

namespace App\Services\Exceptions;

use RuntimeException;

class AiUsageException extends RuntimeException
{
    public function __construct(
        private readonly string $status,
        string $message
    ) {
        parent::__construct($message);
    }

    public function status(): string
    {
        return $this->status;
    }
}

