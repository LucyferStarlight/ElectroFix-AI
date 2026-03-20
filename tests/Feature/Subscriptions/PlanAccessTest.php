<?php

namespace Tests\Feature\Subscriptions;

use App\Models\AiUsage;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class PlanAccessTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyWithRoles;

    public function test_company_without_active_subscription_cannot_access_billing(): void
    {
        [$company, $admin] = $this->createCompanyWithRoles();

        $response = $this->actingAs($admin)->get(route('worker.billing'));

        $response->assertRedirect(route('admin.subscription.edit'));
    }

    public function test_starter_plan_cannot_activate_ai(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        Plan::factory()->create([
            'name' => 'starter',
            'ai_enabled' => false,
            'max_ai_requests' => 0,
            'max_ai_tokens' => 0,
        ]);
        $this->createActiveSubscription($company, ['plan' => 'starter']);
        $technician = $this->createAssignableTechnicianProfile($company, $worker, 'Tech Uno');
        [$customer, $equipment] = $this->createCustomerAndEquipment($company);

        $this->actingAs($admin)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician_profile_id' => $technician->id,
                'request_ai_diagnosis' => 1,
                'symptoms' => 'No enciende',
            ])
            ->assertSessionHas('warning', 'Tu plan actual no incluye Asistente IA.');

        $order = Order::query()->where('company_id', $company->id)->latest()->first();
        $this->assertNull($order?->ai_diagnosed_at);
    }

    public function test_enterprise_plan_can_activate_ai(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        Plan::factory()->create([
            'name' => 'enterprise',
            'ai_enabled' => true,
            'max_ai_requests' => 200,
            'max_ai_tokens' => 120000,
        ]);
        $this->createActiveSubscription($company, ['plan' => 'enterprise']);
        $technician = $this->createAssignableTechnicianProfile($company, $worker, 'Tech Uno');
        [$customer, $equipment] = $this->createCustomerAndEquipment($company);

        $this->actingAs($admin)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician_profile_id' => $technician->id,
                'request_ai_diagnosis' => 1,
                'symptoms' => 'No enciende',
            ])
            ->assertSessionHasNoErrors();

        $order = Order::query()->where('company_id', $company->id)->latest()->firstOrFail();
        $this->assertNotNull($order->ai_diagnosed_at);
    }

    public function test_ai_quota_limit_blocks_with_functional_message(): void
    {
        [$company, $admin, $worker] = $this->createCompanyWithRoles();
        Plan::factory()->create([
            'name' => 'pro',
            'ai_enabled' => true,
            'max_ai_requests' => 1,
            'max_ai_tokens' => 60000,
        ]);
        $this->createActiveSubscription($company, ['plan' => 'pro']);
        $technician = $this->createAssignableTechnicianProfile($company, $worker, 'Tech Uno');
        [$customer, $equipment] = $this->createCustomerAndEquipment($company);

        $cycle = app(\App\Services\AiUsageCycleService::class)->currentCycle($company);

        AiUsage::query()->create([
            'company_id' => $company->id,
            'ai_requests_used' => 1,
            'ai_tokens_used' => 0,
            'overage_requests' => 0,
            'overage_tokens' => 0,
            'current_cycle_start' => $cycle['start']->toDateString(),
            'current_cycle_end' => $cycle['end']->toDateString(),
        ]);

        $this->actingAs($admin)
            ->post(route('worker.orders.store'), [
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician_profile_id' => $technician->id,
                'request_ai_diagnosis' => 1,
                'symptoms' => 'No enciende',
            ])
            ->assertSessionHas('warning', 'Se alcanzó el límite mensual de consultas IA para tu empresa.');
    }

    public function test_stripe_subscription_deleted_disables_company_subscription(): void
    {
        $company = Company::factory()->create(['stripe_id' => 'cus_test_456']);
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'status' => Subscription::STATUS_ACTIVE,
            'stripe_subscription_id' => 'sub_456',
        ]);

        $payload = [
            'id' => 'evt_sub_deleted',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_456',
                ],
            ],
        ];

        app(StripeWebhookService::class)->handle($payload);

        $subscription->refresh();
        $this->assertSame(Subscription::STATUS_CANCELED, $subscription->status);
        $this->assertTrue((bool) $subscription->cancel_at_period_end);
    }

    private function createAssignableTechnicianProfile(Company $company, User $user, string $displayName)
    {
        return \App\Models\TechnicianProfile::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'employee_code' => strtoupper($user->id.'-QA'),
            'display_name' => $displayName,
            'specialties' => ['General'],
            'status' => \App\Support\TechnicianStatus::AVAILABLE,
            'max_concurrent_orders' => 3,
            'hourly_cost' => 100,
            'is_assignable' => true,
        ]);
    }

    private function createCustomerAndEquipment(Company $company): array
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        return [$customer, $equipment];
    }
}
