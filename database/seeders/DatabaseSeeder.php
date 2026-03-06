<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            PlanCatalogSeeder::class,
            PlanPricesSeeder::class,
            UserSeeder::class,
            TechnicianProfileSeeder::class,
            SubscriptionSeeder::class,
            AiUsageSeeder::class,
            OperationalDataSeeder::class,
            InventorySeeder::class,
            BillingSeeder::class,
        ]);
    }
}
