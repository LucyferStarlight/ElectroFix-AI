<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $clientCompany = Company::query()->where('name', 'ElectroFix Cliente Demo')->firstOrFail();
        $developerLab = Company::query()->where('name', 'ElectroFix Developer Lab')->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => 'admin@electrofix.ai'],
            [
                'name' => 'Admin ElectroFix',
                'company_id' => $clientCompany->id,
                'role' => 'admin',
                'is_active' => true,
                'can_access_billing' => true,
                'can_access_inventory' => true,
                'password' => 'password123',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'worker@electrofix.ai'],
            [
                'name' => 'Worker ElectroFix',
                'company_id' => $clientCompany->id,
                'role' => 'worker',
                'is_active' => true,
                'can_access_billing' => false,
                'can_access_inventory' => true,
                'password' => 'password123',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'developer@electrofix.ai'],
            [
                'name' => 'Developer ElectroFix',
                'company_id' => $developerLab->id,
                'role' => 'developer',
                'is_active' => true,
                'can_access_billing' => true,
                'can_access_inventory' => true,
                'password' => 'password123',
            ]
        );
    }
}
