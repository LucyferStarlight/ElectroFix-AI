<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\TechnicianController;
use App\Http\Controllers\Admin\WorkerController;
use App\Http\Controllers\Auth\CompanyRegistrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController as StripeBillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Developer\CompanyInsightsController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController as PublicSubscriptionController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\Worker\BillingController as WorkerBillingController;
use App\Http\Controllers\Worker\CustomerController;
use App\Http\Controllers\Worker\EquipmentController;
use App\Http\Controllers\Worker\InventoryController;
use App\Http\Controllers\Worker\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSubscriptionController::class, 'index'])->name('landing');
Route::get('/sitemap.xml', function () {
    $now = now()->toAtomString();
    $urls = [
        ['loc' => route('landing'), 'lastmod' => $now, 'changefreq' => 'daily', 'priority' => '1.0'],
        ['loc' => route('register'), 'lastmod' => $now, 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['loc' => route('login'), 'lastmod' => $now, 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['loc' => route('support'), 'lastmod' => $now, 'changefreq' => 'weekly', 'priority' => '0.7'],
        ['loc' => route('terms'), 'lastmod' => $now, 'changefreq' => 'monthly', 'priority' => '0.5'],
    ];

    return response()
        ->view('sitemap', ['urls' => $urls])
        ->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');
Route::view('/terms-and-conditions', 'terms')->name('terms');
Route::get('/login', [AuthController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.store');
Route::get('/register', [CompanyRegistrationController::class, 'showForm'])->middleware('guest')->name('register');
Route::post('/register', [CompanyRegistrationController::class, 'store'])->middleware('guest')->name('register.store');
Route::get('/onboarding/success', [CompanyRegistrationController::class, 'success'])->middleware('guest')->name('onboarding.success');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/force-password', [AuthController::class, 'showForcePasswordForm'])->middleware('auth')->name('password.force.edit');
Route::post('/force-password', [AuthController::class, 'updateForcedPassword'])->middleware('auth')->name('password.force.update');
Route::get('/support', [SupportController::class, 'show'])->name('support');
Route::post('/support', [SupportController::class, 'store'])->name('support.store');
Route::post('/subscribe', [PublicSubscriptionController::class, 'subscribe'])->middleware('guest')->name('subscribe');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->middleware('stripe_signature')
    ->name('stripe.webhook');

Route::get('/error/generic', fn () => view('generic', ['currentPage' => 'error']))->name('generic.error');

Route::get('/dev/errors', function () {
    $user = auth()->user();
    $isAllowed = app()->environment(['local', 'testing'])
        || ($user && in_array((string) $user->role, ['developer', 'admin'], true));

    abort_unless($isAllowed, 404);

    $codes = [400, 401, 403, 404, 405, 408, 419, 422, 429, 500, 502, 503, 504];
    $links = array_map(
        fn (int $code): string => sprintf('<li><a href="%s">/dev/errors/%d</a></li>', url("/dev/errors/{$code}"), $code),
        $codes
    );

    return response('<h1>Error previews</h1><ul>'.implode('', $links).'</ul>', 200)
        ->header('Content-Type', 'text/html; charset=UTF-8');
})->name('dev.errors.index');

Route::get('/dev/errors/{code}', function (int $code) {
    $user = auth()->user();
    $isAllowed = app()->environment(['local', 'testing'])
        || ($user && in_array((string) $user->role, ['developer', 'admin'], true));

    abort_unless($isAllowed, 404);

    $allowed = [400, 401, 403, 404, 405, 408, 419, 422, 429, 500, 502, 503, 504];
    abort_unless(in_array($code, $allowed, true), 404);

    abort($code);
})->whereNumber('code')->name('dev.errors.show');

Route::middleware(['auth', 'must_change_password'])->group(function (): void {
    Route::get('/account/suspended', [CompanyRegistrationController::class, 'suspended'])->name('account.suspended');
    Route::post('/account/suspended/checkout', [CompanyRegistrationController::class, 'retryCheckout'])->name('onboarding.retry');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware(['role:worker,admin,developer', 'company_active', 'subscription_active'])->group(function (): void {
        Route::get('/dashboard/worker', [DashboardController::class, 'worker'])->name('dashboard.worker');
        Route::get('/worker/orders', [OrderController::class, 'index'])->name('worker.orders');
        Route::post('/worker/orders', [OrderController::class, 'store'])->name('worker.orders.store');
        Route::patch('/worker/orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->middleware('order_workflow:transition')
            ->name('worker.orders.status');
        Route::patch('/worker/orders/{order}/approve', [OrderController::class, 'approve'])
            ->middleware('order_workflow:approve')
            ->name('worker.orders.approve');
        Route::patch('/worker/orders/{order}/reject', [OrderController::class, 'reject'])->name('worker.orders.reject');
        Route::post('/worker/orders/{order}/deliver', [OrderController::class, 'deliver'])
            ->middleware('order_workflow:deliver')
            ->name('worker.orders.deliver');
        Route::post('/worker/orders/diagnose', [OrderController::class, 'diagnose'])->name('worker.orders.diagnose');

        Route::get('/worker/customers', [CustomerController::class, 'index'])->name('worker.customers');
        Route::post('/worker/customers', [CustomerController::class, 'store'])->name('worker.customers.store');

        Route::get('/worker/equipments', [EquipmentController::class, 'index'])->name('worker.equipments');
        Route::post('/worker/equipments', [EquipmentController::class, 'store'])->name('worker.equipments.store');
        Route::patch('/worker/equipments/{equipment}/assign-customer', [EquipmentController::class, 'assignCustomer'])->name('worker.equipments.assign-customer');
    });

    Route::middleware(['role:worker,admin,developer', 'company_active', 'subscription_active'])->group(function (): void {
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

    Route::middleware(['role:admin', 'company_active', 'subscription_active'])->group(function (): void {
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
        Route::put('/admin/subscription', [SubscriptionController::class, 'update'])->name('admin.subscription.update');
        Route::post('/billing/checkout', [StripeBillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('/billing/success', [StripeBillingController::class, 'success'])->name('billing.success');
        Route::get('/billing/cancel', [StripeBillingController::class, 'cancel'])->name('billing.cancel');
        Route::get('/billing/portal', [StripeBillingController::class, 'portal'])->name('billing.portal');
    });

    Route::middleware(['role:developer', 'company_active', 'subscription_active'])->group(function (): void {
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
