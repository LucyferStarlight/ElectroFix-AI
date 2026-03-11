<?php

namespace App\Services;

use Illuminate\Support\Str;

class WorkerCredentialService
{
    public function resolvePassword(string $strategy, ?string $commonPassword, array $worker): array
    {
        $manual = trim((string) ($worker['password'] ?? ''));

        return match ($strategy) {
            'generated' => [
                'password' => $manual !== '' ? $manual : Str::password(14),
                'generated' => $manual === '',
            ],
            'common' => [
                'password' => $manual !== '' ? $manual : (string) $commonPassword,
                'generated' => false,
            ],
            default => [
                'password' => $manual,
                'generated' => false,
            ],
        };
    }
}
