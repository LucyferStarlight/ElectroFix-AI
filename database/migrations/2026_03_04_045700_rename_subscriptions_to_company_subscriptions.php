<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions') && ! Schema::hasTable('company_subscriptions')) {
            // MySQL keeps FK names globally unique. Drop old FK name before rename
            // to avoid collisions when Cashier creates the new `subscriptions` table.
            try {
                DB::statement('ALTER TABLE subscriptions DROP FOREIGN KEY subscriptions_company_id_foreign');
            } catch (\Throwable $e) {
                // Ignore when the constraint name differs by platform.
            }

            Schema::rename('subscriptions', 'company_subscriptions');

            try {
                DB::statement('ALTER TABLE company_subscriptions ADD CONSTRAINT company_subscriptions_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE');
            } catch (\Throwable $e) {
                // Ignore if FK already exists.
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_subscriptions') && ! Schema::hasTable('subscriptions')) {
            try {
                DB::statement('ALTER TABLE company_subscriptions DROP FOREIGN KEY company_subscriptions_company_id_foreign');
            } catch (\Throwable $e) {
                // Ignore.
            }

            Schema::rename('company_subscriptions', 'subscriptions');

            try {
                DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE');
            } catch (\Throwable $e) {
                // Ignore.
            }
        }
    }
};
