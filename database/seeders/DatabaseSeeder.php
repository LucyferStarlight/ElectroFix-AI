<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            SubscriptionSeeder::class,
            OperationalDataSeeder::class,
            InventorySeeder::class,
            BillingSeeder::class,
        ]);
    }
}
