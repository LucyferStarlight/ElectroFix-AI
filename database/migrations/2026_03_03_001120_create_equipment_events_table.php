<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipments')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'equipment_id', 'created_at']);
            $table->index(['company_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_events');
    }
};

