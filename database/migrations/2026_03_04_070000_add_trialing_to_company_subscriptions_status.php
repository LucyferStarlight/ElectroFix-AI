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

        if ($this->usesMysql()) {
            DB::statement(
                "ALTER TABLE company_subscriptions
                 MODIFY status ENUM('active','trial','trialing','past_due','canceled','suspended')
                 NOT NULL DEFAULT 'trialing'"
            );
        }

        DB::table('company_subscriptions')
            ->where('status', 'trial')
            ->update(['status' => 'trialing']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_subscriptions')) {
            return;
        }

        DB::table('company_subscriptions')
            ->where('status', 'trialing')
            ->update(['status' => 'trial']);

        if ($this->usesMysql()) {
            DB::statement(
                "ALTER TABLE company_subscriptions
                 MODIFY status ENUM('active','trial','past_due','canceled','suspended')
                 NOT NULL DEFAULT 'trial'"
            );
        }
    }

    private function usesMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
