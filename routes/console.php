<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Company;
use App\Services\AiUsageCycleService;
use App\Services\CompanySubscriptionService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai-usage:rollover', function (AiUsageCycleService $cycleService) {
    $companies = Company::query()->with(['subscription', 'aiUsageSummary'])->get();

    foreach ($companies as $company) {
        if (! $company->subscription) {
            continue;
        }

        app(\App\Services\AiQuotaGuardService::class)->ensureUsageRow($company);
    }

    $this->info('Ciclos IA verificados y actualizados.');
})->purpose('Reinicia ciclo IA mensual por empresa según ancla de suscripción');

Artisan::command('subscriptions:apply-deferred', function (CompanySubscriptionService $service) {
    $companies = Company::query()->with('subscription')->get();
    $applied = 0;

    foreach ($companies as $company) {
        if ($service->applyDueChanges($company)) {
            $applied++;
        }
    }

    $this->info("Cambios diferidos aplicados: {$applied}");
})->purpose('Aplica downgrades/cambios pendientes al final del ciclo');
