<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 60);
            $table->string('display_name', 180);
            $table->json('specialties')->nullable();
            $table->enum('status', ['available', 'assigned', 'inactive'])->default('available');
            $table->unsignedInteger('max_concurrent_orders')->default(5);
            $table->decimal('hourly_cost', 12, 2)->default(0);
            $table->boolean('is_assignable')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'employee_code']);
            $table->index(['company_id', 'status', 'is_assignable']);
            $table->index(['company_id', 'display_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_profiles');
    }
};

