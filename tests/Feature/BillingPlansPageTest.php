<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPlansPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_public_plans_page(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'company_id' => $company->id]);

        Subscription::factory()->create([
            'company_id' => $company->id,
            'status' => 'past_due',
        ]);

        $starter = Plan::query()->create([
            'name' => 'starter',
            'is_public' => true,
            'ai_enabled' => true,
            'max_ai_requests' => 100,
            'max_ai_tokens' => 100000,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $starter->id,
            'stripe_price_id' => 'price_test_starter_monthly',
            'billing_period' => 'monthly',
            'currency' => 'mxn',
            'trial_days' => 7,
            'is_active' => true,
        ]);

        Plan::query()->create([
            'name' => 'hidden',
            'is_public' => false,
            'ai_enabled' => false,
            'max_ai_requests' => 0,
            'max_ai_tokens' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('billing.plans'))
            ->assertOk()
            ->assertSee('starter')
            ->assertDontSee('hidden');
    }

    public function test_inactive_admin_is_redirected_to_billing_plans_from_protected_routes(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'company_id' => $company->id]);

        Subscription::factory()->create([
            'company_id' => $company->id,
            'status' => 'past_due',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('billing.plans'));
    }
}
