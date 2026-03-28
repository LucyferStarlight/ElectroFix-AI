<?php

namespace App\Observability;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ObservabilityLogger
{
    public function payment(string $event, array $context = []): void
    {
        $this->write('info', $event, array_merge(['category' => 'payments'], $context));
    }

    public function stateChange(string $event, array $context = []): void
    {
        $this->write('info', $event, array_merge(['category' => 'state_changes'], $context));
    }

    public function error(string $event, \Throwable $exception, array $context = []): void
    {
        $this->write('error', $event, $this->withException($exception, $context));
    }

    public function critical(string $event, \Throwable $exception, array $context = []): void
    {
        $this->write('critical', $event, $this->withException($exception, $context));
    }

    public function warning(string $event, array $context = []): void
    {
        $this->write('warning', $event, $context);
    }

    private function write(string $level, string $event, array $context): void
    {
        $payload = array_merge(
            $this->defaultContext(),
            [
                'event' => $event,
                'service' => (string) config('app.name'),
                'env' => (string) config('app.env'),
            ],
            $context
        );

        Log::channel((string) config('observability.channel', 'observability'))
            ->{$level}($event, $payload);
    }

    private function defaultContext(): array
    {
        $requestContext = [];

        if (app()->bound('request')) {
            /** @var Request $request */
            $request = app('request');
            $requestContext = (array) $request->attributes->get('observability.context', []);
        }

        return [
            'order_id' => $requestContext['order_id'] ?? null,
            'user_id' => $requestContext['user_id'] ?? null,
            'action' => $requestContext['action'] ?? null,
        ];
    }

    private function withException(\Throwable $exception, array $context): array
    {
        return array_merge($context, [
            'category' => $context['category'] ?? 'errors',
            'exception_class' => $exception::class,
            'exception_message' => mb_substr($exception->getMessage(), 0, 500),
            'exception_code' => (string) $exception->getCode(),
            'trace_id' => substr(sha1($exception->getFile().':'.$exception->getLine().':'.$exception->getMessage()), 0, 20),
        ]);
    }
}
