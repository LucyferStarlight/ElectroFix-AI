<?php

namespace Tests\Traits;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;

trait CreatesCompanyWithRoles
{
    protected function createCompanyWithRoles(
        array $companyOverrides = [],
        array $adminOverrides = [],
        array $workerOverrides = []
    ): array {
        $company = Company::factory()->create($companyOverrides);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            ...$adminOverrides,
        ]);

        $worker = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'worker',
            ...$workerOverrides,
        ]);

        return [$company, $admin, $worker];
    }

    protected function createActiveSubscription(Company $company, array $overrides = []): Subscription
    {
        return Subscription::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'plan' => $overrides['plan'] ?? 'starter',
                'status' => $overrides['status'] ?? Subscription::STATUS_ACTIVE,
                'starts_at' => $overrides['starts_at'] ?? now()->startOfMonth(),
                'ends_at' => $overrides['ends_at'] ?? now()->startOfMonth()->addMonth()->endOfMonth(),
                'billing_cycle' => $overrides['billing_cycle'] ?? 'monthly',
                'user_limit' => $overrides['user_limit'] ?? 10,
            ]
        );
    }
}
