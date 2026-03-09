<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\TechnicianController;
use App\Http\Controllers\Admin\WorkerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController as StripeBillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\Developer\CompanyInsightsController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController as PublicSubscriptionController;
use App\Http\Controllers\Worker\CustomerController;
use App\Http\Controllers\Worker\EquipmentController;
use App\Http\Controllers\Worker\BillingController as WorkerBillingController;
use App\Http\Controllers\Worker\InventoryController;
use App\Http\Controllers\Worker\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSubscriptionController::class, 'index'])->name('landing');
Route::view('/terms-and-conditions', 'terms')->name('terms');
Route::get('/login', [AuthController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.store');
Route::get('/register', [RegistrationController::class, 'showForm'])->middleware('guest')->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->middleware('guest')->name('register.store');
Route::get('/register/confirmation/{token}', [RegistrationController::class, 'confirmation'])->middleware('guest')->name('register.confirmation');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/force-password', [AuthController::class, 'showForcePasswordForm'])->middleware('auth')->name('password.force.edit');
Route::post('/force-password', [AuthController::class, 'updateForcedPassword'])->middleware('auth')->name('password.force.update');
Route::post('/subscribe', [PublicSubscriptionController::class, 'subscribe'])->middleware('guest')->name('subscribe');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

Route::get('/error/generic', fn () => view('generic', ['currentPage' => 'error']))->name('generic.error');

Route::middleware(['auth', 'must_change_password', 'subscription_active'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:worker,admin,developer')->group(function (): void {
        Route::get('/dashboard/worker', [DashboardController::class, 'worker'])->name('dashboard.worker');
        Route::get('/worker/orders', [OrderController::class, 'index'])->name('worker.orders');
        Route::post('/worker/orders', [OrderController::class, 'store'])->name('worker.orders.store');
        Route::patch('/worker/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('worker.orders.status');
        Route::post('/worker/orders/diagnose', [OrderController::class, 'diagnose'])->name('worker.orders.diagnose');

        Route::get('/worker/customers', [CustomerController::class, 'index'])->name('worker.customers');
        Route::post('/worker/customers', [CustomerController::class, 'store'])->name('worker.customers.store');

        Route::get('/worker/equipments', [EquipmentController::class, 'index'])->name('worker.equipments');
        Route::post('/worker/equipments', [EquipmentController::class, 'store'])->name('worker.equipments.store');
    });

    Route::middleware('role:worker,admin,developer')->group(function (): void {
        Route::get('/worker/inventory', [InventoryController::class, 'index'])
            ->middleware('module_access:inventory')
            ->name('worker.inventory');
        Route::post('/worker/inventory', [InventoryController::class, 'store'])
            ->middleware('module_access:inventory')
            ->name('worker.inventory.store');
        Route::patch('/worker/inventory/{item}/stock', [InventoryController::class, 'adjustStock'])
            ->middleware('module_access:inventory')
            ->name('worker.inventory.stock');
        Route::delete('/worker/inventory/{item}', [InventoryController::class, 'destroy'])
            ->middleware('module_access:inventory')
            ->name('worker.inventory.destroy');
        Route::get('/worker/billing', [WorkerBillingController::class, 'index'])
            ->middleware('module_access:billing')
            ->name('worker.billing');
        Route::post('/worker/billing', [WorkerBillingController::class, 'store'])
            ->middleware('module_access:billing')
            ->name('worker.billing.store');
        Route::get('/worker/billing/customers/{customer}/services', [WorkerBillingController::class, 'customerServices'])
            ->middleware('module_access:billing')
            ->name('worker.billing.customer-services');
        Route::get('/worker/billing/{document}', [WorkerBillingController::class, 'show'])
            ->middleware('module_access:billing')
            ->name('worker.billing.show');
        Route::get('/worker/billing/{document}/pdf', [WorkerBillingController::class, 'pdf'])
            ->middleware('module_access:billing')
            ->name('worker.billing.pdf');
    });

    Route::middleware('role:admin')->group(function (): void {
        Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('dashboard.admin');
        Route::get('/admin/company', [CompanyController::class, 'edit'])->name('admin.company.edit');
        Route::put('/admin/company', [CompanyController::class, 'update'])->name('admin.company.update');
        Route::get('/admin/workers', [WorkerController::class, 'index'])->name('admin.workers.index');
        Route::post('/admin/workers', [WorkerController::class, 'store'])->name('admin.workers.store');
        Route::put('/admin/workers/{user}', [WorkerController::class, 'update'])->name('admin.workers.update');
        Route::patch('/admin/workers/{user}/deactivate', [WorkerController::class, 'deactivate'])->name('admin.workers.deactivate');
        Route::delete('/admin/workers/{user}', [WorkerController::class, 'destroy'])->name('admin.workers.destroy');
        Route::get('/admin/technicians', [TechnicianController::class, 'index'])->name('admin.technicians.index');
        Route::post('/admin/technicians', [TechnicianController::class, 'store'])->name('admin.technicians.store');
        Route::put('/admin/technicians/{technician}', [TechnicianController::class, 'update'])->name('admin.technicians.update');
        Route::patch('/admin/technicians/{technician}/deactivate', [TechnicianController::class, 'deactivate'])->name('admin.technicians.deactivate');
        Route::get('/admin/subscription', [SubscriptionController::class, 'edit'])->name('admin.subscription.edit');
        Route::post('/billing/checkout', [StripeBillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('/billing/success', [StripeBillingController::class, 'success'])->name('billing.success');
        Route::get('/billing/cancel', [StripeBillingController::class, 'cancel'])->name('billing.cancel');
        Route::get('/billing/portal', [StripeBillingController::class, 'portal'])->name('billing.portal');
    });

    Route::middleware('role:developer')->group(function (): void {
        Route::get('/dashboard/developer', [DashboardController::class, 'developer'])->name('dashboard.developer');
        Route::get('/developer/companies', [CompanyInsightsController::class, 'index'])->name('developer.companies.index');
        Route::get('/developer/companies/{company}', [CompanyInsightsController::class, 'show'])->name('developer.companies.show');
        Route::get('/developer/subscriptions', [CompanyInsightsController::class, 'subscriptions'])->name('developer.subscriptions');
        Route::get('/developer/test-company', [CompanyInsightsController::class, 'testCompany'])->name('developer.test-company');

        if (app()->environment('local')) {
            Route::post('/developer/companies/{company}/assign-developer-test', [\App\Http\Controllers\Developer\DeveloperSubscriptionController::class, 'assignDeveloperTest'])
                ->name('developer.subscriptions.assign-devtest');
        }
    });
});
