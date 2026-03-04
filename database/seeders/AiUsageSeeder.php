<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\AiQuotaGuardService;
use Illuminate\Database\Seeder;

class AiUsageSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->with('subscription')->get();

        foreach ($companies as $company) {
            if (! $company->subscription) {
                continue;
            }

            app(AiQuotaGuardService::class)->ensureUsageRow($company);
        }
    }
}
