<?php

namespace Tests\Feature\Access;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesCompanyWithRoles;

class SubscriptionMiddlewareCompanyStatusTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyWithRoles;

    public function test_suspended_company_is_blocked_from_protected_route(): void
    {
        [$company, $admin] = $this->createCompanyWithRoles([
            'status' => 'suspended',
        ]);
        $this->createActiveSubscription($company);

        $firstHop = $this->actingAs($admin)->get('/dashboard');
        $firstHop->assertRedirect(route('dashboard.admin'));

        $protectedRouteResponse = $this->actingAs($admin)->get(route('dashboard.admin'));
        $protectedRouteResponse->assertStatus(302);
        $protectedRouteResponse->assertRedirect(route('account.suspended'));
    }

    public function test_active_company_passes_through_middleware(): void
    {
        [$company, $admin] = $this->createCompanyWithRoles([
            'status' => 'active',
        ]);
        $this->createActiveSubscription($company, [
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        if ($response->isRedirect()) {
            $location = $response->headers->get('Location');

            $this->assertNotSame(route('account.suspended'), $location);
            $this->assertNotSame(route('login'), $location);
        } else {
            $response->assertStatus(200);
        }
    }
}
