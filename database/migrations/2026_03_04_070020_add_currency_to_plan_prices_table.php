<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plan_prices', 'currency')) {
            Schema::table('plan_prices', function (Blueprint $table): void {
                $table->string('currency', 3)->default('mxn')->after('billing_period');
            });
        }

        if ($this->indexExists('plan_prices_plan_id_billing_period_unique')
            && ! $this->indexExists('plan_prices_plan_id_idx')) {
            Schema::table('plan_prices', function (Blueprint $table): void {
                $table->index('plan_id', 'plan_prices_plan_id_idx');
            });
        }

        if ($this->indexExists('plan_prices_plan_id_billing_period_unique')) {
            Schema::table('plan_prices', function (Blueprint $table): void {
                $table->dropUnique('plan_prices_plan_id_billing_period_unique');
            });
        }

        if (! $this->indexExists('plan_prices_plan_period_currency_unique')) {
            Schema::table('plan_prices', function (Blueprint $table): void {
                $table->unique(['plan_id', 'billing_period', 'currency'], 'plan_prices_plan_period_currency_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('plan_prices_plan_period_currency_unique')) {
            Schema::table('plan_prices', function (Blueprint $table): void {
                $table->dropUnique('plan_prices_plan_period_currency_unique');
            });
        }

        if (! $this->indexExists('plan_prices_plan_id_billing_period_unique')) {
            Schema::table('plan_prices', function (Blueprint $table): void {
                $table->unique(['plan_id', 'billing_period'], 'plan_prices_plan_id_billing_period_unique');
            });
        }

        if ($this->indexExists('plan_prices_plan_id_idx')) {
            Schema::table('plan_prices', function (Blueprint $table): void {
                $table->dropIndex('plan_prices_plan_id_idx');
            });
        }

        if (Schema::hasColumn('plan_prices', 'currency')) {
            Schema::table('plan_prices', function (Blueprint $table): void {
                $table->dropColumn('currency');
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        $result = DB::selectOne(
            "SELECT COUNT(1) AS aggregate
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'plan_prices'
               AND INDEX_NAME = ?",
            [$indexName]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
