<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_redirects_per_role(): void
    {
        $company = Company::factory()->create();
        $this->createActiveSubscription($company);

        $worker = User::factory()->create(['role' => 'worker', 'company_id' => $company->id]);
        $admin = User::factory()->create(['role' => 'admin', 'company_id' => $company->id]);
        $developer = User::factory()->create(['role' => 'developer', 'company_id' => $company->id]);

        $this->actingAs($worker)->get('/dashboard')->assertRedirect(route('dashboard.worker'));
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('dashboard.admin'));
        $this->actingAs($developer)->get('/dashboard')->assertRedirect(route('dashboard.developer'));
    }

    public function test_worker_cannot_access_admin_routes(): void
    {
        $company = Company::factory()->create();
        $this->createActiveSubscription($company);
        $worker = User::factory()->create(['role' => 'worker', 'company_id' => $company->id]);

        $this->actingAs($worker)
            ->get(route('admin.workers.index'))
            ->assertForbidden();
    }

    public function test_admin_cannot_update_workers_from_other_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $this->createActiveSubscription($companyA);
        $this->createActiveSubscription($companyB);

        $admin = User::factory()->create(['role' => 'admin', 'company_id' => $companyA->id]);
        $foreignWorker = User::factory()->create(['role' => 'worker', 'company_id' => $companyB->id]);

        $this->actingAs($admin)
            ->put(route('admin.workers.update', $foreignWorker), [
                'name' => 'Updated',
                'email' => 'updated@example.com',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_own_subscription(): void
    {
        $company = Company::factory()->create();
        $subscription = $this->createActiveSubscription($company);
        $admin = User::factory()->create(['role' => 'admin', 'company_id' => $company->id]);

        $this->actingAs($admin)
            ->put(route('admin.subscription.update'), [
                'plan' => 'enterprise',
                'status' => 'active',
                'starts_at' => '2026-03-01',
                'ends_at' => '2026-06-01',
                'billing_cycle' => 'yearly',
                'user_limit' => 100,
            ])
            ->assertSessionHasNoErrors();

        $subscription->refresh();

        $this->assertSame('enterprise', $subscription->plan);
        $this->assertSame('active', $subscription->status);
    }

    public function test_inactive_worker_cannot_login(): void
    {
        $company = Company::factory()->create();

        User::factory()->create([
            'company_id' => $company->id,
            'role' => 'worker',
            'email' => 'inactive-worker@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => 'inactive-worker@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_worker_special_modules_require_delegated_permissions(): void
    {
        $company = Company::factory()->create();
        $this->createActiveSubscription($company);
        $worker = User::factory()->create([
            'role' => 'worker',
            'company_id' => $company->id,
            'can_access_billing' => false,
            'can_access_inventory' => false,
        ]);

        $this->actingAs($worker)->get(route('worker.billing'))->assertForbidden();
        $this->actingAs($worker)->get(route('worker.inventory'))->assertForbidden();

        $worker->update([
            'can_access_billing' => true,
            'can_access_inventory' => true,
        ]);

        $this->actingAs($worker)->get(route('worker.billing'))->assertOk();
        $this->actingAs($worker)->get(route('worker.inventory'))->assertOk();
    }

    public function test_worker_dashboard_shows_only_delegated_orders_to_worker(): void
    {
        $company = Company::factory()->create();
        $this->createActiveSubscription($company);
        $worker = User::factory()->create(['role' => 'worker', 'company_id' => $company->id, 'name' => 'Worker Uno']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'technician' => 'Worker Uno',
        ]);

        Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'technician' => 'Otro Técnico',
        ]);

        $this->actingAs($worker)
            ->get(route('dashboard.worker'))
            ->assertOk()
            ->assertSee('Órdenes delegadas')
            ->assertDontSee('No tienes órdenes delegadas actualmente.');
    }

    private function createActiveSubscription(Company $company): Subscription
    {
        return Subscription::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'plan' => 'starter',
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->startOfMonth()->addMonth()->endOfMonth(),
                'billing_cycle' => 'monthly',
                'user_limit' => 10,
            ]
        );
    }
}
