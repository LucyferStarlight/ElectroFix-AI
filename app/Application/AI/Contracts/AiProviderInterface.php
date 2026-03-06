<?php

namespace App\Application\AI\Contracts;

use App\Application\AI\DTO\AiResponse;
use App\Application\AI\DTO\UsageEstimate;

interface AiProviderInterface
{
    public function generateSolution(array $context): AiResponse;

    public function estimateUsage(string $prompt): UsageEstimate;

    public function getProviderName(): string;
}

