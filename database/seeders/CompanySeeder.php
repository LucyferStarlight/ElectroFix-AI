<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->updateOrCreate(
            ['name' => 'ElectroFix Cliente Demo'],
            [
                'owner_name' => 'Carlos Mendoza',
                'owner_email' => 'owner@cliente-demo.com',
                'owner_phone' => '+52 55 1000 1000',
                'tax_id' => 'RFC123456AB1',
                'billing_email' => 'billing@cliente-demo.com',
                'billing_phone' => '+52 55 1000 1001',
                'address_line' => 'Av. Reforma 100',
                'city' => 'CDMX',
                'state' => 'CDMX',
                'country' => 'MX',
                'postal_code' => '06600',
                'currency' => 'MXN',
                'vat_percentage' => 16,
                'notes' => 'Empresa demo principal para entorno local.',
            ]
        );

        Company::query()->updateOrCreate(
            ['name' => 'ElectroFix Developer Lab'],
            [
                'owner_name' => 'ElectroFix Platform Team',
                'owner_email' => 'dev-owner@electrofix.ai',
                'owner_phone' => '+52 55 2000 2000',
                'tax_id' => 'RFCDEV1234X9',
                'billing_email' => 'dev-billing@electrofix.ai',
                'billing_phone' => '+52 55 2000 2001',
                'address_line' => 'Innovation Park 42',
                'city' => 'Monterrey',
                'state' => 'NL',
                'country' => 'MX',
                'postal_code' => '64000',
                'currency' => 'MXN',
                'vat_percentage' => 16,
                'notes' => 'Empresa fija para pruebas de developer.',
            ]
        );
    }
}
