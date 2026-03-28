<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_diagnostics', function (Blueprint $table): void {
            $table->index(
                ['company_id', 'equipment_type', 'failure_type', 'created_at'],
                'order_diag_company_type_failure_created_idx'
            );
            $table->index(
                ['company_id', 'order_id', 'version'],
                'order_diag_company_order_version_idx'
            );
            $table->index(
                ['company_id', 'failure_type', 'created_at'],
                'order_diag_company_failure_created_idx'
            );
        });

        Schema::table('order_repair_outcomes', function (Blueprint $table): void {
            $table->index(
                ['company_id', 'order_id'],
                'order_repair_outcomes_company_order_idx'
            );
            $table->index(
                ['company_id', 'actual_amount_charged'],
                'order_repair_outcomes_company_amount_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('order_repair_outcomes', function (Blueprint $table): void {
            $table->dropIndex('order_repair_outcomes_company_order_idx');
            $table->dropIndex('order_repair_outcomes_company_amount_idx');
        });

        Schema::table('order_diagnostics', function (Blueprint $table): void {
            $table->dropIndex('order_diag_company_type_failure_created_idx');
            $table->dropIndex('order_diag_company_order_version_idx');
            $table->dropIndex('order_diag_company_failure_created_idx');
        });
    }
};
