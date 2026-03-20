<?php

namespace Tests\Feature\Onboarding;

use App\Mail\CompanyWelcomeMail;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\StripeCheckoutService;
use App\Services\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class CompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_form_creates_pending_company(): void
    {
        $this->mockStripeCheckout();

        $response = $this->post(route('register.store'), [
            'company_name' => 'Taller QA',
            'admin_name' => 'Admin QA',
            'email' => 'admin@qa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan' => 'starter',
            'terms' => 1,
        ]);

        $response->assertRedirect('https://checkout.test/session');

        $company = Company::query()->where('name', 'Taller QA')->firstOrFail();
        $this->assertSame('pending_payment', $company->status);
        $this->assertSame('starter', $company->pending_plan);
        $this->assertSame('cs_test_123', $company->stripe_checkout_session_id);
    }

    public function test_invalid_email_returns_validation_error(): void
    {
        $this->mockStripeCheckout();

        $response = $this->from(route('register'))
            ->post(route('register.store'), [
                'company_name' => 'Taller QA',
                'admin_name' => 'Admin QA',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'plan' => 'starter',
                'terms' => 1,
            ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
    }

    public function test_terms_are_required(): void
    {
        $this->mockStripeCheckout();

        $response = $this->from(route('register'))
            ->post(route('register.store'), [
                'company_name' => 'Taller QA',
                'admin_name' => 'Admin QA',
                'email' => 'admin@qa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'plan' => 'starter',
            ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('terms');
    }

    public function test_form_does_not_require_rfc_fields(): void
    {
        $this->mockStripeCheckout();

        $response = $this->post(route('register.store'), [
            'company_name' => 'Taller QA',
            'admin_name' => 'Admin QA',
            'email' => 'admin2@qa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan' => 'starter',
            'terms' => 1,
        ]);

        $response->assertRedirect('https://checkout.test/session');
    }

    public function test_webhook_activates_company_and_sends_welcome_email(): void
    {
        Mail::fake();

        Plan::factory()->create([
            'name' => 'starter',
            'ai_enabled' => false,
        ]);

        $company = Company::factory()->create([
            'status' => 'pending_payment',
            'pending_plan' => 'starter',
            'stripe_id' => 'cus_test_123',
            'stripe_checkout_session_id' => 'cs_test_123',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'email' => 'admin@qa.test',
        ]);

        $payload = [
            'id' => 'evt_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_123',
                    'subscription' => 'sub_123',
                    'metadata' => [
                        'plan' => 'starter',
                        'billing_period' => 'monthly',
                        'company_id' => $company->id,
                    ],
                ],
            ],
        ];

        app(StripeWebhookService::class)->handle($payload);

        $company->refresh();
        $this->assertSame('active', $company->status);
        $this->assertNull($company->stripe_checkout_session_id);
        $this->assertNotNull(Subscription::query()->where('company_id', $company->id)->first());

        Mail::assertQueued(CompanyWelcomeMail::class, function (CompanyWelcomeMail $mail) use ($admin): bool {
            return $mail->hasTo($admin->email);
        });
    }

    public function test_pending_company_is_redirected_by_middleware(): void
    {
        $company = Company::factory()->create(['status' => 'pending_payment']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('dashboard.admin'));

        $response->assertRedirect(route('account.suspended'));
    }

    public function test_active_company_can_access_dashboard(): void
    {
        $company = Company::factory()->create(['status' => 'active']);
        Subscription::factory()->create([
            'company_id' => $company->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('dashboard.admin'));

        $response->assertOk();
    }

    private function mockStripeCheckout(): void
    {
        $mock = Mockery::mock(StripeCheckoutService::class);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test_123');
        $mock->shouldReceive('createCheckoutSession')->andReturn([
            'id' => 'cs_test_123',
            'url' => 'https://checkout.test/session',
        ]);

        $this->app->instance(StripeCheckoutService::class, $mock);
    }
}
