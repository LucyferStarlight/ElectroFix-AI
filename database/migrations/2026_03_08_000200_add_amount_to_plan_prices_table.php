<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plan_prices') || Schema::hasColumn('plan_prices', 'amount')) {
            return;
        }

        Schema::table('plan_prices', function (Blueprint $table): void {
            $table->decimal('amount', 10, 2)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('plan_prices') || ! Schema::hasColumn('plan_prices', 'amount')) {
            return;
        }

        Schema::table('plan_prices', function (Blueprint $table): void {
            $table->dropColumn('amount');
        });
    }
};
