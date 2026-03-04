<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $clientCompany = Company::query()->where('name', 'ElectroFix Cliente Demo')->firstOrFail();
        $developerLab = Company::query()->where('name', 'ElectroFix Developer Lab')->firstOrFail();
        $proPlan = Plan::query()->where('name', 'pro')->first();
        $devPlan = Plan::query()->where('name', 'developer_test')->first();

        Subscription::query()->updateOrCreate(
            ['company_id' => $clientCompany->id],
            [
                'plan' => 'pro',
                'plan_id' => $proPlan?->id,
                'status' => 'active',
                'starts_at' => now()->startOfMonth()->toDateString(),
                'ends_at' => now()->addMonthsNoOverflow(1)->endOfMonth()->toDateString(),
                'billing_period' => 'monthly',
                'billing_cycle' => 'monthly',
                'current_period_end' => now()->addMonthsNoOverflow(1)->endOfMonth(),
                'user_limit' => 25,
            ]
        );

        Subscription::query()->updateOrCreate(
            ['company_id' => $developerLab->id],
            [
                'plan' => 'developer_test',
                'plan_id' => $devPlan?->id,
                'status' => 'active',
                'starts_at' => now()->startOfMonth()->toDateString(),
                'ends_at' => now()->addYearsNoOverflow(1)->endOfMonth()->toDateString(),
                'billing_period' => 'annual',
                'billing_cycle' => 'yearly',
                'current_period_end' => now()->addYearsNoOverflow(1)->endOfMonth(),
                'user_limit' => 999,
            ]
        );
    }
}
