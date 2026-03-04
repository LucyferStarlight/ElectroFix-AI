<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('technician_profile_id')
                ->nullable()
                ->after('technician')
                ->constrained('technician_profiles')
                ->nullOnDelete();

            $table->index(['company_id', 'technician_profile_id', 'status']);
        });

        $this->backfillTechnicianProfiles();
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_company_id_technician_profile_id_status_index');
            $table->dropConstrainedForeignId('technician_profile_id');
        });
    }

    private function backfillTechnicianProfiles(): void
    {
        $companies = DB::table('orders')
            ->select('company_id')
            ->distinct()
            ->whereNotNull('company_id')
            ->pluck('company_id');

        foreach ($companies as $companyId) {
            $names = DB::table('orders')
                ->where('company_id', $companyId)
                ->whereNull('technician_profile_id')
                ->whereNotNull('technician')
                ->select('technician')
                ->distinct()
                ->pluck('technician');

            foreach ($names as $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }

                $user = DB::table('users')
                    ->where('company_id', $companyId)
                    ->where('name', $name)
                    ->whereIn('role', ['worker', 'admin', 'developer'])
                    ->first();

                $codeSuffix = strtoupper(substr(md5($companyId.'|'.$name), 0, 6));
                $employeeCode = 'LEG-'.$codeSuffix;

                $profile = DB::table('technician_profiles')
                    ->where('company_id', $companyId)
                    ->where('display_name', $name)
                    ->first();

                if (! $profile) {
                    $profileId = DB::table('technician_profiles')->insertGetId([
                        'company_id' => $companyId,
                        'user_id' => $user?->id,
                        'employee_code' => $employeeCode,
                        'display_name' => $name,
                        'specialties' => null,
                        'status' => $user ? 'available' : 'inactive',
                        'max_concurrent_orders' => 5,
                        'hourly_cost' => 0,
                        'is_assignable' => (bool) $user,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $profileId = $profile->id;
                }

                DB::table('orders')
                    ->where('company_id', $companyId)
                    ->where('technician', $name)
                    ->whereNull('technician_profile_id')
                    ->update(['technician_profile_id' => $profileId]);
            }
        }
    }
};

