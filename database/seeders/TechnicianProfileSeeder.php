<?php

namespace Database\Seeders;

use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\TechnicianStatus;
use Illuminate\Database\Seeder;

class TechnicianProfileSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->whereNotNull('company_id')
            ->whereIn('role', ['worker', 'admin', 'developer'])
            ->get();

        foreach ($users as $user) {
            TechnicianProfile::query()->updateOrCreate(
                ['company_id' => $user->company_id, 'user_id' => $user->id],
                [
                    'employee_code' => 'USR-'.$user->id,
                    'display_name' => $user->name,
                    'specialties' => [],
                    'status' => $user->is_active ? TechnicianStatus::AVAILABLE : TechnicianStatus::INACTIVE,
                    'max_concurrent_orders' => 5,
                    'hourly_cost' => 0,
                    'is_assignable' => $user->is_active,
                ]
            );
        }
    }
}

