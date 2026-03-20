<?php

namespace Tests\Feature;

use App\Models\AiUsage;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\ApiAbility;
use App\Support\TechnicianStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderAiQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_with_ai_respects_monthly_quota(): void
    {
        $company = Company::factory()->create();

        Plan::query()->updateOrCreate(['name' => 'pro'], [
            'name' => 'pro',
            'is_public' => false,
            'ai_enabled' => true,
            'max_ai_requests' => 1,
            'max_ai_tokens' => 500,
            'overage_enabled' => false,
            'overage_price_per_request' => 0,
            'overage_price_per_1000_tokens' => 0,
        ]);

        Subscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'pro',
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->endOfMonth(),
        ]);

        AiUsage::query()->create([
            'company_id' => $company->id,
            'ai_requests_used' => 1,
            'ai_tokens_used' => 0,
            'overage_requests' => 0,
            'overage_tokens' => 0,
            'current_cycle_start' => now()->startOfMonth()->toDateString(),
            'current_cycle_end' => now()->endOfMonth()->toDateString(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'company_id' => $company->id,
        ]);

        $technicianUser = User::factory()->create([
            'role' => 'worker',
            'company_id' => $company->id,
        ]);

        $technician = TechnicianProfile::query()->create([
            'company_id' => $company->id,
            'user_id' => $technicianUser->id,
            'employee_code' => 'EMP-001',
            'display_name' => $technicianUser->name,
            'specialties' => ['electrodomesticos'],
            'status' => TechnicianStatus::AVAILABLE,
            'max_concurrent_orders' => 5,
            'hourly_cost' => 0,
            'is_assignable' => true,
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
        ]);

        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        Sanctum::actingAs($admin, [ApiAbility::ORDERS_WRITE]);

        $response = $this->postJson('/api/v1/orders', [
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'symptoms' => 'No enciende desde ayer',
            'status' => 'received',
            'estimated_cost' => 1200,
            'request_ai_diagnosis' => true,
            'technician_profile_id' => $technician->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('meta.ai_warning', 'Se alcanzó el límite mensual de consultas IA para tu empresa.');
    }
}
