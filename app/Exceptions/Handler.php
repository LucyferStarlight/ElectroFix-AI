<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Observability\ObservabilityLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler
{
    public function __construct(private readonly ObservabilityLogger $observability) {}

    public function report(Throwable $exception): void
    {
        $request = app()->bound('request') ? request() : null;
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        $context = [
            'status_code' => $statusCode,
            'path' => $request?->path(),
            'method' => $request?->method(),
        ];

        if ($exception instanceof ValidationException) {
            Log::channel('stack')->warning('Validation error', [
                'errors' => $exception->errors(),
                ...$context,
            ]);
            $this->observability->warning('app.invalid_input', [
                'category' => 'errors',
                ...$context,
                'validation_errors' => $exception->errors(),
            ]);

            return;
        }

        if (str_contains($exception::class, 'Ai') || str_contains($exception::class, 'AI')) {
            Log::channel('stack')->error('AI failure', ['error' => $exception->getMessage(), ...$context]);
            $this->observability->error('app.ai_error', $exception, [
                'category' => 'errors',
                ...$context,
            ]);

            return;
        }

        if ($statusCode >= 500) {
            Log::channel('stack')->critical('Unhandled exception', ['error' => $exception->getMessage(), ...$context]);
            $this->observability->critical('app.unhandled_exception', $exception, [
                'category' => 'errors',
                ...$context,
            ]);

            return;
        }

        Log::channel('stack')->error('Request exception', ['error' => $exception->getMessage(), ...$context]);
        $this->observability->error('app.request_exception', $exception, [
            'category' => 'errors',
            ...$context,
        ]);
    }
}
