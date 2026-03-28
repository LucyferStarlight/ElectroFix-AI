<?php

namespace Tests\Unit;

use App\Jobs\ProcessAiDiagnosticJob;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use App\Services\DiagnosisService;
use App\Services\Exceptions\AiQuotaExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessAiDiagnosticJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_quota_exceeded_exception_does_not_rethrow(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'ai_diagnosis_pending' => true,
            'ai_diagnosis_error' => null,
        ]);

        $diagnosisService = $this->createMock(DiagnosisService::class);
        $diagnosisService
            ->expects($this->once())
            ->method('run')
            ->willThrowException(new AiQuotaExceededException('blocked_quota', 'Límite mensual alcanzado'));

        $job = new ProcessAiDiagnosticJob($order, $company, $actor, 'No enciende');

        $job->handle($diagnosisService);

        $order->refresh();

        $this->assertFalse((bool) $order->ai_diagnosis_pending);
        $this->assertNotNull($order->ai_diagnosis_error);
    }

    public function test_transient_throwable_rethrows_for_retry(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'ai_diagnosis_pending' => true,
            'ai_diagnosis_error' => null,
        ]);

        $diagnosisService = $this->createMock(DiagnosisService::class);
        $diagnosisService
            ->expects($this->once())
            ->method('run')
            ->willThrowException(new \RuntimeException('timeout'));

        $job = new ProcessAiDiagnosticJob($order, $company, $actor, 'No enciende');

        $this->expectException(\RuntimeException::class);

        try {
            $job->handle($diagnosisService);
        } catch (\RuntimeException $exception) {
            $order->refresh();
            $this->assertNotNull($order->ai_diagnosis_error);

            throw $exception;
        }
    }
}
