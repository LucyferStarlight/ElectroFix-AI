<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_session_completed_updates_company_plan(): void
    {
        Plan::query()->create([
            'name' => 'pro',
            'is_public' => true,
            'ai_enabled' => true,
            'max_ai_requests' => 100,
            'max_ai_tokens' => 60000,
            'overage_enabled' => false,
            'overage_price_per_request' => 0,
            'overage_price_per_1000_tokens' => 0,
        ]);

        $company = Company::factory()->create([
            'stripe_id' => 'cus_test_123',
        ]);

        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $payload = [
            'id' => 'evt_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_123',
                    'subscription' => 'sub_123',
                    'metadata' => [
                        'plan' => 'pro',
                        'billing_period' => 'monthly',
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payloadJson, 'whsec_test');
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $response = $this->withHeader('Stripe-Signature', $signatureHeader)
            ->postJson('/api/billing/stripe/webhook', $payload);

        $response->assertStatus(200);

        $subscription = Subscription::query()->where('company_id', $company->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('pro', $subscription->plan);
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame('sub_123', $subscription->stripe_subscription_id);
    }
}
