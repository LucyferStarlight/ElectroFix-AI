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
            $table->index('status', 'orders_status_idx');
            $table->index(['equipment_id', 'status', 'created_at'], 'orders_equipment_status_created_idx');
        });

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE orders ADD FULLTEXT INDEX orders_symptoms_fulltext_idx (symptoms)');
            DB::statement('ALTER TABLE order_diagnostics ADD FULLTEXT INDEX order_diag_symptoms_fulltext_idx (normalized_symptoms, symptoms_snapshot)');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->index('symptoms', 'orders_symptoms_idx');
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE order_diagnostics DROP INDEX order_diag_symptoms_fulltext_idx');
            DB::statement('ALTER TABLE orders DROP INDEX orders_symptoms_fulltext_idx');
        } else {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex('orders_symptoms_idx');
            });
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_status_idx');
            $table->dropIndex('orders_equipment_status_created_idx');
        });
    }
};
