<?php

namespace Database\Seeders;

use App\Models\BillingDocument;
use App\Models\BillingDocumentItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('name', 'ElectroFix Cliente Demo')->firstOrFail();
        $user = User::query()->where('email', 'admin@electrofix.ai')->firstOrFail();
        $customer = Customer::query()->where('company_id', $company->id)->first();
        $inventoryItem = InventoryItem::query()->where('company_id', $company->id)->first();

        $document = BillingDocument::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'document_number' => 'DOC-'.str_pad((string) $company->id, 3, '0', STR_PAD_LEFT).'-000001',
            ],
            [
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
                'document_type' => 'quote',
                'customer_mode' => 'registered',
                'walk_in_name' => null,
                'source' => 'mixed',
                'tax_mode' => 'excluded',
                'vat_percentage' => $company->vat_percentage,
                'subtotal' => 1000,
                'vat_amount' => 160,
                'total' => 1160,
                'notes' => 'Documento demo de facturación.',
                'issued_at' => now(),
            ]
        );

        BillingDocumentItem::query()->updateOrCreate(
            [
                'billing_document_id' => $document->id,
                'description' => 'Diagnóstico técnico inicial',
            ],
            [
                'inventory_item_id' => null,
                'item_kind' => 'service',
                'quantity' => 1,
                'unit_price' => 600,
                'line_subtotal' => 600,
                'line_vat' => 96,
                'line_total' => 696,
            ]
        );

        if ($inventoryItem) {
            BillingDocumentItem::query()->updateOrCreate(
                [
                    'billing_document_id' => $document->id,
                    'description' => $inventoryItem->name,
                ],
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'item_kind' => 'product',
                    'quantity' => 1,
                    'unit_price' => 400,
                    'line_subtotal' => 400,
                    'line_vat' => 64,
                    'line_total' => 464,
                ]
            );
        }
    }
}
