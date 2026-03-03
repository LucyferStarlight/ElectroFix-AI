<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_number', 40);
            $table->enum('document_type', ['quote', 'invoice']);
            $table->enum('customer_mode', ['registered', 'walk_in'])->default('registered');
            $table->string('walk_in_name', 180)->nullable();
            $table->enum('source', ['repair', 'sale', 'mixed'])->default('mixed');
            $table->enum('tax_mode', ['included', 'excluded']);
            $table->decimal('vat_percentage', 5, 2);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'document_number']);
            $table->index(['company_id', 'document_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_documents');
    }
};
