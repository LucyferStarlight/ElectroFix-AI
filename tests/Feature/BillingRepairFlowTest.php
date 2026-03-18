<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Support\ApiAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingRepairFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_billing_flow_from_order_to_pdf(): void
    {
        $company = Company::factory()->create([
            'vat_percentage' => 16,
        ]);

        Subscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'starter',
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->endOfMonth(),
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'company_id' => $company->id,
            'can_access_billing' => true,
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
        ]);

        $equipment = Equipment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $order = Order::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'status' => 'received',
            'estimated_cost' => 1500,
        ]);

        Sanctum::actingAs($user, [ApiAbility::BILLING_WRITE]);

        $response = $this->postJson('/api/v1/billing/documents', [
            'document_type' => 'invoice',
            'source' => 'repair',
            'customer_mode' => 'registered',
            'customer_id' => $customer->id,
            'tax_mode' => 'included',
            'items' => [
                [
                    'description' => 'Reparación de equipo',
                    'quantity' => 1,
                    'unit_price' => 1500,
                    'order_id' => $order->id,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $document = BillingDocument::query()->find($response->json('data.id'));
        $this->assertNotNull($document);

        $order->refresh();
        $this->assertSame('ready', $order->status);

        $this->actingAs($user);

        $pdfResponse = $this->get(route('worker.billing.pdf', $document));
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }
}
