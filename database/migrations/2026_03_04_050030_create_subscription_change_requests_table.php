<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_plan_id')->constrained('plans')->restrictOnDelete();
            $table->enum('requested_billing_period', ['monthly', 'semiannual', 'annual']);
            $table->timestamp('effective_at');
            $table->enum('status', ['pending', 'applied', 'canceled'])->default('pending');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_change_requests');
    }
};
