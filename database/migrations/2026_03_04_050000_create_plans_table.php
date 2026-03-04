<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 40)->unique();
            $table->boolean('is_public')->default(true);
            $table->boolean('ai_enabled')->default(false);
            $table->unsignedInteger('max_ai_requests')->default(0);
            $table->unsignedInteger('max_ai_tokens')->default(0);
            $table->boolean('overage_enabled')->default(false);
            $table->decimal('overage_price_per_request', 12, 4)->nullable();
            $table->decimal('overage_price_per_1000_tokens', 12, 4)->nullable();
            $table->string('stripe_overage_requests_price_id')->nullable();
            $table->string('stripe_overage_tokens_price_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
