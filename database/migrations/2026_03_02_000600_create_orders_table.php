<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipments')->cascadeOnDelete();
            $table->string('technician');
            $table->text('symptoms')->nullable();
            $table->enum('status', ['received', 'diagnostic', 'repairing', 'quote', 'ready', 'delivered', 'not_repaired'])->default('received');
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->json('ai_potential_causes')->nullable();
            $table->string('ai_estimated_time')->nullable();
            $table->json('ai_suggested_parts')->nullable();
            $table->text('ai_technical_advice')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
