<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('email')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('can_access_billing')->default(false)->after('is_active');
            $table->boolean('can_access_inventory')->default(false)->after('can_access_billing');
            $table->softDeletes();
            $table->index(['company_id', 'role', 'is_active']);
        });

        if ($this->usesMysql()) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','worker','developer') NOT NULL DEFAULT 'worker'");
        }
    }

    public function down(): void
    {
        if ($this->usesMysql()) {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'worker'");
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_company_id_role_is_active_index');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['is_active', 'can_access_billing', 'can_access_inventory']);
            $table->dropSoftDeletes();
        });
    }

    private function usesMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
