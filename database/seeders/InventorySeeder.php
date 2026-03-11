<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('name', 'ElectroFix Cliente Demo')->firstOrFail();
        $developerLab = Company::query()->where('name', 'ElectroFix Developer Lab')->firstOrFail();

        $items = [
            ['name' => 'Capacitor 25uF', 'internal_code' => 'REF-CAP-25', 'quantity' => 18, 'low_stock_threshold' => 8, 'is_sale_enabled' => false, 'sale_price' => null],
            ['name' => 'Tarjeta Control Universal', 'internal_code' => 'REF-TCU-110', 'quantity' => 4, 'low_stock_threshold' => 5, 'is_sale_enabled' => true, 'sale_price' => 1450.00],
            ['name' => 'Bomba de Drenaje', 'internal_code' => 'REF-BDR-22', 'quantity' => 10, 'low_stock_threshold' => 4, 'is_sale_enabled' => true, 'sale_price' => 780.00],
            ['name' => 'Kit de Sellos', 'internal_code' => 'REF-KSE-09', 'quantity' => 3, 'low_stock_threshold' => 4, 'is_sale_enabled' => true, 'sale_price' => 320.00],
        ];

        foreach ($items as $item) {
            InventoryItem::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'internal_code' => $item['internal_code'],
                ],
                [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'low_stock_threshold' => $item['low_stock_threshold'],
                    'is_sale_enabled' => $item['is_sale_enabled'],
                    'sale_price' => $item['sale_price'],
                ]
            );
        }

        InventoryItem::query()->updateOrCreate(
            [
                'company_id' => $developerLab->id,
                'internal_code' => 'DEV-LAB-001',
            ],
            [
                'name' => 'Módulo de prueba laboratorio',
                'quantity' => 12,
                'low_stock_threshold' => 2,
                'is_sale_enabled' => false,
                'sale_price' => null,
            ]
        );
    }
}
