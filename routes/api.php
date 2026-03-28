<?php

use App\Http\Controllers\Api\Billing\StripeController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BillingDocumentApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\DashboardMetricsApiController;
use App\Http\Controllers\Api\V1\EquipmentApiController;
use App\Http\Controllers\Api\V1\InventoryItemApiController;
use App\Http\Controllers\Api\V1\OrderApiController;
use App\Http\Controllers\Api\V1\ProfileApiController;
use App\Http\Controllers\Api\V1\RepairOutcomeApiController;
use App\Http\Controllers\Api\V1\SubscriptionApiController;
use App\Support\ApiAbility;
use Illuminate\Support\Facades\Route;

Route::post('/billing/stripe/webhook', [StripeController::class, 'webhook'])
    ->middleware('stripe_signature')
    ->withoutMiddleware('throttle:api');

Route::middleware(['auth:sanctum'])->group(function (): void {});

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/tokens', [AuthTokenController::class, 'store']);

    Route::middleware(['auth:sanctum', 'company_active', 'subscription_active'])->group(function (): void {
        Route::get('/me', [ProfileApiController::class, 'show']);

        Route::get('/customers', [CustomerApiController::class, 'index'])->middleware('token_ability:'.ApiAbility::ORDERS_READ);
        Route::post('/customers', [CustomerApiController::class, 'store'])->middleware('token_ability:'.ApiAbility::ORDERS_WRITE);

        Route::get('/equipments', [EquipmentApiController::class, 'index'])->middleware('token_ability:'.ApiAbility::ORDERS_READ);
        Route::post('/equipments', [EquipmentApiController::class, 'store'])->middleware('token_ability:'.ApiAbility::ORDERS_WRITE);

        Route::get('/orders', [OrderApiController::class, 'index'])->middleware('token_ability:'.ApiAbility::ORDERS_READ);
        Route::post('/orders', [OrderApiController::class, 'store'])->middleware('token_ability:'.ApiAbility::ORDERS_WRITE);
        Route::patch('/orders/{order}/status', [OrderApiController::class, 'updateStatus'])->middleware('token_ability:'.ApiAbility::ORDERS_WRITE);
        Route::patch('/orders/{order}/approve', [OrderApiController::class, 'approve'])->middleware('token_ability:'.ApiAbility::ORDERS_WRITE);
        Route::patch('/orders/{order}/reject', [OrderApiController::class, 'reject'])->middleware('token_ability:'.ApiAbility::ORDERS_WRITE);
        Route::post('/orders/{order}/diagnostics', [OrderApiController::class, 'storeDiagnostic'])
            ->middleware(['token_ability:'.ApiAbility::AI_USE, 'throttle:ai-diagnostics']);
        Route::get('/orders/{order}/diagnostics/latest', [OrderApiController::class, 'showLatestDiagnostic'])
            ->middleware('token_ability:'.ApiAbility::ORDERS_READ);
        Route::post('/orders/diagnostics/similar', [OrderApiController::class, 'similarCases'])
            ->middleware(['token_ability:'.ApiAbility::ORDERS_READ, 'throttle:ai-similar-cases']);
        Route::get('/orders/diagnostics/insights', [OrderApiController::class, 'diagnosticInsights'])
            ->middleware('token_ability:'.ApiAbility::ORDERS_READ);
        Route::patch('/orders/{order}/repair-outcome/feedback', [RepairOutcomeApiController::class, 'update'])
            ->middleware('token_ability:'.ApiAbility::ORDERS_WRITE);

        Route::get('/inventory/items', [InventoryItemApiController::class, 'index'])->middleware('token_ability:'.ApiAbility::INVENTORY_WRITE);
        Route::post('/inventory/items', [InventoryItemApiController::class, 'store'])->middleware('token_ability:'.ApiAbility::INVENTORY_WRITE);

        Route::get('/billing/documents', [BillingDocumentApiController::class, 'index'])->middleware('token_ability:'.ApiAbility::BILLING_WRITE);
        Route::post('/billing/documents', [BillingDocumentApiController::class, 'store'])->middleware('token_ability:'.ApiAbility::BILLING_WRITE);

        Route::get('/dashboard/metrics', [DashboardMetricsApiController::class, 'show'])->middleware('token_ability:'.ApiAbility::ORDERS_READ);

        Route::get('/billing/subscription', [SubscriptionApiController::class, 'show'])->middleware('token_ability:'.ApiAbility::ORDERS_READ);
        Route::get('/billing/subscription/plans', [SubscriptionApiController::class, 'plans'])->middleware('token_ability:'.ApiAbility::ORDERS_READ);
    });
});
