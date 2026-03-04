<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\RegistrationConfirmation;
use App\Models\Subscription;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\TechnicianStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    public function __construct(
        private readonly PaymentSimulationService $paymentSimulationService,
        private readonly WorkerCredentialService $workerCredentialService,
        private readonly AiQuotaGuardService $aiQuotaGuardService
    ) {
    }

    public function register(array $payload): RegistrationConfirmation
    {
        return DB::transaction(function () use ($payload): RegistrationConfirmation {
            $adminData = $payload['admin'];
            $companyData = $payload['company'];
            $subscriptionData = $payload['subscription'];
            $workers = $payload['workers'] ?? [];
            $strategy = (string) ($payload['worker_password_strategy'] ?? 'manual');
            $commonPassword = $payload['common_worker_password'] ?? null;

            $company = Company::query()->create([
                'name' => $companyData['name'],
                'owner_name' => $adminData['name'],
                'owner_email' => $adminData['email'],
                'owner_phone' => $companyData['owner_phone'] ?? 'PENDIENTE',
                'billing_email' => $adminData['email'],
                'address_line' => $companyData['address_line'],
                'city' => $companyData['city'] ?? null,
                'state' => $companyData['state'] ?? null,
                'country' => $companyData['country'] ?? 'MX',
                'postal_code' => $companyData['postal_code'] ?? null,
                'currency' => 'MXN',
            ]);

            $admin = User::query()->create([
                'company_id' => $company->id,
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => $adminData['password'],
                'role' => 'admin',
                'is_active' => true,
                'can_access_billing' => true,
                'can_access_inventory' => true,
            ]);

            $this->createTechnicianProfile($company->id, $admin);

            $workerSummary = [];
            foreach ($workers as $index => $worker) {
                $passwordInfo = $this->workerCredentialService->resolvePassword($strategy, $commonPassword, $worker);

                $user = User::query()->create([
                    'company_id' => $company->id,
                    'name' => $worker['name'],
                    'email' => $worker['email'],
                    'password' => $passwordInfo['password'],
                    'role' => 'worker',
                    'is_active' => true,
                    'can_access_billing' => false,
                    'can_access_inventory' => false,
                ]);

                $this->createTechnicianProfile($company->id, $user);

                $workerSummary[] = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $passwordInfo['password'],
                    'generated' => $passwordInfo['generated'],
                    'index' => $index + 1,
                ];
            }

            $plan = Plan::query()->where('name', $subscriptionData['plan'])->firstOrFail();
            $period = (string) $subscriptionData['billing_period'];
            $trialEnabled = (bool) $subscriptionData['trial_enabled'];

            $periodEnd = match ($period) {
                'semiannual' => now()->addMonthsNoOverflow(6),
                'annual' => now()->addYearNoOverflow(),
                default => now()->addMonthNoOverflow(),
            };

            $payment = ['attempt_no' => null, 'result' => 'trial'];
            $status = 'trial';
            if (! $trialEnabled) {
                $payment = $this->paymentSimulationService->nextResult();
                $status = $payment['result'] === 'approved' ? 'active' : 'past_due';
            }

            $subscription = Subscription::query()->create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'plan' => $plan->name,
                'status' => $status,
                'starts_at' => now()->toDateString(),
                'ends_at' => $periodEnd->toDateString(),
                'billing_cycle' => $period === 'annual' ? 'yearly' : 'monthly',
                'billing_period' => $period,
                'current_period_end' => $periodEnd,
                'cancel_at_period_end' => false,
                'user_limit' => max(10, count($workerSummary) + 1),
            ]);

            $company->setRelation('subscription', $subscription);
            $this->aiQuotaGuardService->ensureUsageRow($company);

            $accessToken = Str::random(80);

            return RegistrationConfirmation::query()->create([
                'company_id' => $company->id,
                'admin_user_id' => $admin->id,
                'access_token' => $accessToken,
                'payload_snapshot' => [
                    'company' => [
                        'name' => $company->name,
                    ],
                    'admin' => [
                        'name' => $admin->name,
                        'email' => $admin->email,
                    ],
                    'workers' => $workerSummary,
                    'subscription' => [
                        'plan' => $subscription->plan,
                        'billing_period' => $subscription->billing_period,
                        'status' => $subscription->status,
                        'trial_enabled' => $trialEnabled,
                    ],
                    'payment_simulation' => $payment,
                ],
                'expires_at' => now()->addHours(24),
            ]);
        });
    }

    private function createTechnicianProfile(int $companyId, User $user): void
    {
        TechnicianProfile::query()->create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'employee_code' => 'USR-'.$user->id,
            'display_name' => $user->name,
            'specialties' => [],
            'status' => TechnicianStatus::AVAILABLE,
            'max_concurrent_orders' => 5,
            'hourly_cost' => 0,
            'is_assignable' => true,
        ]);
    }
}
