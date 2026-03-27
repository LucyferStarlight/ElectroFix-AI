<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 20);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('mxn');
            $table->string('source', 30)->default('manual');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_checkout_session_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_refund_id')->nullable();
            $table->string('status', 30)->default('succeeded');
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'direction']);
            $table->index(['stripe_payment_intent_id']);
            $table->index(['stripe_checkout_session_id']);
            $table->index(['stripe_charge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
