<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_repair_outcomes', function (Blueprint $table): void {
            $table->json('ai_diagnosis')->nullable()->after('actual_causes');
            $table->json('real_diagnosis')->nullable()->after('ai_diagnosis');
            $table->text('repair_applied')->nullable()->after('real_diagnosis');
            $table->decimal('confidence_score', 5, 2)->nullable()->after('repair_applied');
            $table->boolean('validated')->default(false)->after('confidence_score');
            $table->index(['company_id', 'validated'], 'order_repair_outcomes_company_validated_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_repair_outcomes', function (Blueprint $table): void {
            $table->dropIndex('order_repair_outcomes_company_validated_idx');
            $table->dropColumn([
                'ai_diagnosis',
                'real_diagnosis',
                'repair_applied',
                'confidence_score',
                'validated',
            ]);
        });
    }
};
