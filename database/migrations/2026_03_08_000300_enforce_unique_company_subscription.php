<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_subscriptions')) {
            return;
        }

        if (! $this->indexExists('company_subscriptions_company_id_unique')) {
            Schema::table('company_subscriptions', function (Blueprint $table): void {
                $table->unique('company_id', 'company_subscriptions_company_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_subscriptions')) {
            return;
        }

        if ($this->indexExists('company_subscriptions_company_id_unique')) {
            Schema::table('company_subscriptions', function (Blueprint $table): void {
                $table->dropUnique('company_subscriptions_company_id_unique');
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        $result = DB::selectOne(
            "SELECT COUNT(1) AS aggregate
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'company_subscriptions'
               AND INDEX_NAME = ?",
            [$indexName]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
