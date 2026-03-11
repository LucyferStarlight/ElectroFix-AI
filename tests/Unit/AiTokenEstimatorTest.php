<?php

namespace Tests\Unit;

use App\Services\AiTokenEstimator;
use PHPUnit\Framework\TestCase;

class AiTokenEstimatorTest extends TestCase
{
    public function test_it_estimates_tokens_from_chars(): void
    {
        $service = new AiTokenEstimator();

        $this->assertSame(0, $service->estimateFromChars(0));
        $this->assertSame(1, $service->estimateFromChars(1));
        $this->assertSame(1, $service->estimateFromChars(4));
        $this->assertSame(2, $service->estimateFromChars(5));
        $this->assertSame(25, $service->estimateFromChars(100));
    }
}

