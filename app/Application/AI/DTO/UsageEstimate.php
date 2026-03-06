<?php

namespace App\Application\AI\DTO;

class UsageEstimate
{
    public function __construct(
        public readonly int $promptTokensEstimated,
        public readonly int $responseTokensEstimated,
        public readonly int $totalTokensEstimated
    ) {
    }
}

