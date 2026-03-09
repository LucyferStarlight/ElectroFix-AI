<?php

namespace App\Services;

use App\Models\Company;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\TechnicianStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StripeSignupService
{
    public function __construct(
        private readonly CompanySubscriptionService $companySubscriptionService,
        private readonly AiQuotaGuardService $aiQuotaGuardService
    ) {
    }

    public function createOrSyncFromCheckout(array $payload): ?Company
    {
        $customerId = (string) Arr::get($payload, 'data.object.customer');
        $subscriptionId = (string) Arr::get($payload, 'data.object.subscription');
        $email = (string) (Arr::get($payload, 'data.object.customer_details.email')
            ?: Arr::get($payload, 'data.object.customer_email')
            ?: Arr::get($payload, 'data.object.metadata.admin_email'));

        if ($customerId === '' || $subscriptionId === '' || $email === '') {
            return null;
        }

        $plan = (string) Arr::get($payload, 'data.object.metadata.plan', 'starter');
        $billingPeriod = (string) Arr::get($payload, 'data.object.metadata.billing_period', 'monthly');
        $companyName = trim((string) Arr::get($payload, 'data.object.metadata.company_name', ''));
        $adminName = trim((string) Arr::get($payload, 'data.object.metadata.admin_name', ''));

        if (! in_array($plan, ['starter', 'pro', 'enterprise'], true)) {
            $plan = 'starter';
        }

        if (! in_array($billingPeriod, ['monthly', 'semiannual', 'annual'], true)) {
            $billingPeriod = 'monthly';
        }

        $businessStatus = (string) Arr::get($payload, 'data.object.payment_status') === 'paid'
            ? 'active'
            : 'trialing';

        return DB::transaction(function () use ($customerId, $subscriptionId, $email, $companyName, $adminName, $plan, $billingPeriod, $businessStatus): Company {
            $company = Company::query()->where('stripe_id', $customerId)->first();

            $existingUser = User::query()->where('email', $email)->first();
            if (! $company && $existingUser?->company) {
                $company = $existingUser->company;
            }

            if (! $company) {
                $derivedName = Str::of($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->toString();
                $resolvedCompanyName = $companyName !== '' ? $companyName : 'Empresa '.$derivedName;
                $resolvedAdminName = $adminName !== '' ? $adminName : ($derivedName !== '' ? $derivedName : 'Administrador');

                $company = Company::query()->create([
                    'name' => $resolvedCompanyName,
                    'owner_name' => $resolvedAdminName,
                    'owner_email' => $email,
                    'owner_phone' => 'PENDIENTE',
                    'billing_email' => $email,
                    'address_line' => 'PENDIENTE',
                    'city' => null,
                    'state' => null,
                    'country' => 'MX',
                    'postal_code' => null,
                    'currency' => 'MXN',
                    'stripe_id' => $customerId,
                ]);
            } else {
                $company->update([
                    'stripe_id' => $company->stripe_id ?: $customerId,
                    'owner_email' => $company->owner_email ?: $email,
                    'billing_email' => $company->billing_email ?: $email,
                ]);
            }

            $admin = $existingUser;
            if (! $admin) {
                $password = Str::password(16);
                $admin = User::query()->create([
                    'company_id' => $company->id,
                    'name' => $company->owner_name ?: 'Administrador',
                    'email' => $email,
                    'password' => $password,
                    'role' => 'admin',
                    'is_active' => true,
                    'can_access_billing' => true,
                    'can_access_inventory' => true,
                    'must_change_password' => true,
                ]);
            } elseif ($admin->company_id !== $company->id) {
                $admin->update(['company_id' => $company->id]);
            }

            TechnicianProfile::query()->updateOrCreate(
                ['user_id' => $admin->id],
                [
                    'company_id' => $company->id,
                    'employee_code' => 'USR-'.$admin->id,
                    'display_name' => $admin->name,
                    'specialties' => [],
                    'status' => TechnicianStatus::AVAILABLE,
                    'max_concurrent_orders' => 5,
                    'hourly_cost' => 0,
                    'is_assignable' => true,
                ]
            );

            $this->companySubscriptionService->syncBusinessSubscription(
                $company,
                $plan,
                $billingPeriod,
                $subscriptionId,
                $businessStatus
            );

            $this->aiQuotaGuardService->ensureUsageRow($company);

            return $company;
        });
    }
}
