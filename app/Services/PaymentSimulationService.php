<?php

namespace App\Services;

use App\Models\RegistrationPaymentSimulation;
use Illuminate\Support\Facades\DB;

class PaymentSimulationService
{
    public function nextResult(): array
    {
        return DB::transaction(function (): array {
            $latest = RegistrationPaymentSimulation::query()
                ->lockForUpdate()
                ->latest('attempt_no')
                ->first();

            $attempt = ((int) ($latest?->attempt_no ?? 0)) + 1;
            $result = $attempt % 2 === 1 ? 'approved' : 'rejected';

            RegistrationPaymentSimulation::query()->create([
                'attempt_no' => $attempt,
                'result' => $result,
            ]);

            return [
                'attempt_no' => $attempt,
                'result' => $result,
            ];
        });
    }
}
