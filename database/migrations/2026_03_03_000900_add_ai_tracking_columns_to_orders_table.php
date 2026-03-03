<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('ai_diagnosed_at')->nullable()->after('ai_technical_advice');
            $table->unsignedInteger('ai_tokens_used')->nullable()->after('ai_diagnosed_at');
            $table->string('ai_provider', 80)->nullable()->after('ai_tokens_used');
            $table->string('ai_model', 120)->nullable()->after('ai_provider');
            $table->boolean('ai_requires_parts_replacement')->nullable()->after('ai_model');
            $table->decimal('ai_cost_repair_labor', 12, 2)->nullable()->after('ai_requires_parts_replacement');
            $table->decimal('ai_cost_replacement_parts', 12, 2)->nullable()->after('ai_cost_repair_labor');
            $table->decimal('ai_cost_replacement_total', 12, 2)->nullable()->after('ai_cost_replacement_parts');

            $table->index(['company_id', 'ai_diagnosed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_company_id_ai_diagnosed_at_index');
            $table->dropColumn([
                'ai_diagnosed_at',
                'ai_tokens_used',
                'ai_provider',
                'ai_model',
                'ai_requires_parts_replacement',
                'ai_cost_repair_labor',
                'ai_cost_replacement_parts',
                'ai_cost_replacement_total',
            ]);
        });
    }
};

