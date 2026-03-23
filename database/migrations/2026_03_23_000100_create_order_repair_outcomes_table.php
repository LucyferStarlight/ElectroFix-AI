<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_repair_outcomes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('billing_document_id')->nullable()->constrained('billing_documents')->nullOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('repair_outcome', ['repaired', 'partial', 'not_repaired']);
            $table->text('outcome_notes')->nullable();
            $table->text('work_performed')->nullable();
            $table->decimal('actual_amount_charged', 10, 2)->nullable();
            $table->decimal('aris_estimated_cost', 10, 2)->nullable();
            $table->boolean('had_ai_diagnosis')->default(false);
            $table->boolean('feeds_aris_training')->default(false);
            $table->string('plan_at_close', 60);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('feeds_aris_training');
            $table->index('repair_outcome');
            $table->index('had_ai_diagnosis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_repair_outcomes');
    }
};
