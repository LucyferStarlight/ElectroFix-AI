<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_diagnostics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 40)->default('ai');
            $table->text('symptoms_snapshot')->nullable();
            $table->json('equipment_snapshot')->nullable();
            $table->text('diagnostic_summary')->nullable();
            $table->json('possible_causes')->nullable();
            $table->json('recommended_actions')->nullable();
            $table->boolean('requires_parts_replacement')->default(false);
            $table->decimal('repair_labor_cost', 12, 2)->default(0);
            $table->decimal('replacement_parts_cost', 12, 2)->default(0);
            $table->decimal('replacement_total_cost', 12, 2)->default(0);
            $table->string('estimated_time')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->string('provider', 80)->nullable();
            $table->string('model', 120)->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'version']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_diagnostics');
    }
};

