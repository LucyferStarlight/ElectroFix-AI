<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_assignment_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_technician_profile_id')->nullable()->constrained('technician_profiles')->nullOnDelete();
            $table->foreignId('to_technician_profile_id')->nullable()->constrained('technician_profiles')->nullOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_assignment_logs');
    }
};

