<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use App\Services\DiagnosisService;
use App\Services\Exceptions\AiQuotaExceededException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAiDiagnosticJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $backoff = 5;

    public function __construct(
        private readonly Order $order,
        private readonly Company $company,
        private readonly User $actor,
        private readonly string $symptoms
    ) {
    }

    public function handle(DiagnosisService $diagnosisService): void
    {
        try {
            $diagnosisService->run($this->order, $this->company, $this->actor, $this->symptoms);

            $this->order->update([
                'ai_diagnosis_pending' => false,
                'ai_diagnosis_error' => null,
            ]);
        } catch (AiQuotaExceededException|\Throwable $exception) {
            $this->order->update([
                'ai_diagnosis_pending' => false,
                'ai_diagnosis_error' => $exception->getMessage(),
            ]);
        }
    }
}
