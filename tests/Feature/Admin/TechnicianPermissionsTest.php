<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\TechnicianStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delegate_billing_and_inventory_permissions_when_creating_technician(): void
    {
        $company = Company::factory()->create();
        $this->createActiveSubscription($company);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.technicians.store'), [
                'display_name' => 'Tecnico Uno',
                'email' => 'tecnico@example.com',
                'employee_code' => 'TEC-01',
                'hourly_cost' => 100,
                'max_concurrent_orders' => 5,
                'status' => TechnicianStatus::AVAILABLE,
                'specialties' => ['Refrigeración'],
                'is_assignable' => 1,
                'can_access_billing' => 1,
                'can_access_inventory' => 1,
            ])
            ->assertRedirect(route('admin.technicians.index'));

        $technicianUser = User::query()->where('email', 'tecnico@example.com')->firstOrFail();

        $this->assertTrue($technicianUser->can_access_billing);
        $this->assertTrue($technicianUser->can_access_inventory);
    }

    public function test_permissions_default_to_false_when_not_sent_in_creation_or_update(): void
    {
        $company = Company::factory()->create();
        $this->createActiveSubscription($company);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.technicians.store'), [
                'display_name' => 'Tecnico Dos',
                'email' => 'tecnico2@example.com',
                'employee_code' => 'TEC-02',
                'hourly_cost' => 100,
                'max_concurrent_orders' => 4,
                'status' => TechnicianStatus::AVAILABLE,
                'specialties' => ['Electricidad'],
                'is_assignable' => 1,
            ])
            ->assertRedirect(route('admin.technicians.index'));

        $technicianUser = User::query()->where('email', 'tecnico2@example.com')->firstOrFail();
        $this->assertFalse($technicianUser->can_access_billing);
        $this->assertFalse($technicianUser->can_access_inventory);

        $profile = TechnicianProfile::query()->where('user_id', $technicianUser->id)->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.technicians.update', $profile), [
                'display_name' => 'Tecnico Dos Editado',
                'specialties' => ['Electricidad'],
                'status' => TechnicianStatus::AVAILABLE,
                'max_concurrent_orders' => 4,
                'hourly_cost' => 100,
                'is_assignable' => 1,
            ])
            ->assertRedirect();

        $technicianUser->refresh();

        $this->assertFalse($technicianUser->can_access_billing);
        $this->assertFalse($technicianUser->can_access_inventory);
    }

    private function createActiveSubscription(Company $company): Subscription
    {
        return Subscription::factory()->create([
            'company_id' => $company->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }
}
