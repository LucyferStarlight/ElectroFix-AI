<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $clientCompany = Company::query()->where('name', 'ElectroFix Cliente Demo')->firstOrFail();
        $developerLab = Company::query()->where('name', 'ElectroFix Developer Lab')->firstOrFail();

        Subscription::query()->updateOrCreate(
            ['company_id' => $clientCompany->id],
            [
                'plan' => 'pro',
                'status' => 'active',
                'starts_at' => now()->startOfMonth()->toDateString(),
                'ends_at' => now()->addMonthsNoOverflow(1)->endOfMonth()->toDateString(),
                'billing_cycle' => 'monthly',
                'user_limit' => 25,
            ]
        );

        Subscription::query()->updateOrCreate(
            ['company_id' => $developerLab->id],
            [
                'plan' => 'developer_test',
                'status' => 'active',
                'starts_at' => now()->startOfMonth()->toDateString(),
                'ends_at' => now()->addYearsNoOverflow(1)->endOfMonth()->toDateString(),
                'billing_cycle' => 'yearly',
                'user_limit' => 999,
            ]
        );
    }
}
