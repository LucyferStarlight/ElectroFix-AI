<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_price_id')->unique();
            $table->enum('billing_period', ['monthly', 'semiannual', 'annual']);
            $table->unsignedTinyInteger('trial_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['plan_id', 'billing_period']);
            $table->index(['billing_period', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};
