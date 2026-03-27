<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\OrderStatus;
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

    public function test_payment_intent_webhook_registers_order_payment(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);
        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'estimated_cost' => 1000,
            'status' => OrderStatus::APPROVED,
        ]);

        $payload = [
            'id' => 'evt_pi_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount_received' => 40000,
                    'currency' => 'mxn',
                    'latest_charge' => 'ch_test_123',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                    ],
                ],
            ],
        ];

        $response = $this->postSignedWebhook($payload);

        $response->assertStatus(200);
        $this->assertSame('partial', $order->fresh()->payment_status);
        $this->assertSame(400.0, (float) $order->fresh()->total_paid);
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'direction' => 'payment',
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);
    }

    public function test_charge_refunded_webhook_marks_order_as_refunded(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);
        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'estimated_cost' => 500,
            'status' => OrderStatus::APPROVED,
        ]);

        $order->registerPayment(500, [
            'source' => 'stripe',
            'currency' => 'mxn',
            'stripe_payment_intent_id' => 'pi_refund_123',
            'stripe_charge_id' => 'ch_refund_123',
            'status' => 'succeeded',
        ]);

        $payload = [
            'id' => 'evt_ref_123',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_refund_123',
                    'payment_intent' => 'pi_refund_123',
                    'amount_refunded' => 50000,
                    'currency' => 'mxn',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                    ],
                    'refunds' => [
                        'data' => [
                            ['id' => 're_test_123'],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postSignedWebhook($payload);

        $response->assertStatus(200);
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame(0.0, (float) $order->fresh()->total_paid);
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'direction' => 'refund',
            'stripe_refund_id' => 're_test_123',
        ]);
    }

    private function postSignedWebhook(array $payload)
    {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payloadJson, 'whsec_test');
        $signatureHeader = "t={$timestamp},v1={$signature}";

        return $this->withHeader('Stripe-Signature', $signatureHeader)
            ->postJson('/api/billing/stripe/webhook', $payload);
    }
}
