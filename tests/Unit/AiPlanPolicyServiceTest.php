<?php

namespace Tests\Unit;

use App\Services\AiPlanPolicyService;
use PHPUnit\Framework\TestCase;

class AiPlanPolicyServiceTest extends TestCase
{
    public function test_plan_support_and_limits_are_defined(): void
    {
        $service = new AiPlanPolicyService();

        $this->assertTrue($service->supportsAi('enterprise'));
        $this->assertTrue($service->supportsAi('developer_test'));
        $this->assertFalse($service->supportsAi('starter'));

        $this->assertSame(200, $service->queryLimit('enterprise'));
        $this->assertSame(300, $service->queryLimit('developer_test'));
        $this->assertSame(0, $service->queryLimit('starter'));

        $this->assertSame(120000, $service->tokenLimit('enterprise'));
        $this->assertSame(500000, $service->tokenLimit('developer_test'));
        $this->assertSame(0, $service->tokenLimit('starter'));
    }
}
