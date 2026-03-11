<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_ai_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->char('year_month', 7);
            $table->string('plan_snapshot', 40);
            $table->unsignedInteger('prompt_chars')->default(0);
            $table->unsignedInteger('response_chars')->default(0);
            $table->unsignedInteger('prompt_tokens_estimated')->default(0);
            $table->unsignedInteger('response_tokens_estimated')->default(0);
            $table->unsignedInteger('total_tokens_estimated')->default(0);
            $table->enum('status', ['success', 'blocked_plan', 'blocked_quota', 'blocked_tokens', 'error']);
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'year_month']);
            $table->index(['company_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_ai_usages');
    }
};

