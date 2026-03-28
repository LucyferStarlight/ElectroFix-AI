<?php

declare(strict_types=1);

use App\Exceptions\Handler as ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\AttachObservabilityContext::class);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->throttleApi('api');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'module_access' => \App\Http\Middleware\EnsureModuleAccess::class,
            'token_ability' => \App\Http\Middleware\EnsureTokenAbility::class,
            'subscription_active' => \App\Http\Middleware\EnsureCompanySubscriptionActive::class,
            'must_change_password' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'company_active' => \App\Http\Middleware\EnsureCompanyActive::class,
            'stripe_signature' => \App\Http\Middleware\VerifyStripeWebhookSignature::class,
            'order_workflow' => \App\Http\Middleware\EnsureOrderWorkflowAction::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): void {
            app(ExceptionHandler::class)->report($exception);
        });
    })->create();
