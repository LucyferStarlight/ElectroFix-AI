<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_subscriptions')) {
            return;
        }

        DB::table('company_subscriptions')
            ->where('status', 'suspended')
            ->update(['status' => 'inactive']);

        if ($this->usesMysql()) {
            DB::statement(
                "ALTER TABLE company_subscriptions
                 MODIFY status ENUM('active','trialing','past_due','canceled','inactive')
                 NOT NULL DEFAULT 'trialing'"
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_subscriptions')) {
            return;
        }

        DB::table('company_subscriptions')
            ->where('status', 'inactive')
            ->update(['status' => 'suspended']);

        if ($this->usesMysql()) {
            DB::statement(
                "ALTER TABLE company_subscriptions
                 MODIFY status ENUM('active','trialing','past_due','canceled','suspended')
                 NOT NULL DEFAULT 'trialing'"
            );
        }
    }

    private function usesMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
