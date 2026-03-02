<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\WorkerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Developer\CompanyInsightsController;
use App\Http\Controllers\Worker\CustomerController;
use App\Http\Controllers\Worker\EquipmentController;
use App\Http\Controllers\Worker\OrderController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');
Route::get('/login', [AuthController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/error/generic', fn () => view('generic', ['currentPage' => 'error']))->name('generic.error');

Route::middleware('auth')->group(function (): void {
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
        Route::get('/worker/inventory', [EquipmentController::class, 'index'])
            ->middleware('module_access:inventory')
            ->name('worker.inventory');
        Route::get('/worker/billing', [OrderController::class, 'index'])
            ->middleware('module_access:billing')
            ->name('worker.billing');
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
        Route::get('/admin/subscription', [SubscriptionController::class, 'edit'])->name('admin.subscription.edit');
        Route::put('/admin/subscription', [SubscriptionController::class, 'update'])->name('admin.subscription.update');
    });

    Route::middleware('role:developer')->group(function (): void {
        Route::get('/dashboard/developer', [DashboardController::class, 'developer'])->name('dashboard.developer');
        Route::get('/developer/companies', [CompanyInsightsController::class, 'index'])->name('developer.companies.index');
        Route::get('/developer/companies/{company}', [CompanyInsightsController::class, 'show'])->name('developer.companies.show');
        Route::get('/developer/subscriptions', [CompanyInsightsController::class, 'subscriptions'])->name('developer.subscriptions');
        Route::get('/developer/test-company', [CompanyInsightsController::class, 'testCompany'])->name('developer.test-company');
    });
});
