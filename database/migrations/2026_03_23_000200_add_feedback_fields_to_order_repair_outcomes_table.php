<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_repair_outcomes', function (Blueprint $table): void {
            $table->enum('diagnostic_accuracy', ['correct', 'partial', 'incorrect'])
                ->nullable()
                ->after('had_ai_diagnosis');
            $table->text('technician_notes')->nullable()->after('diagnostic_accuracy');
            $table->json('actual_causes')->nullable()->after('technician_notes');
        });
    }

    public function down(): void
    {
        Schema::table('order_repair_outcomes', function (Blueprint $table): void {
            $table->dropColumn([
                'diagnostic_accuracy',
                'technician_notes',
                'actual_causes',
            ]);
        });
    }
};
