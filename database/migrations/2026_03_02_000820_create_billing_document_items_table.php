<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_document_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->enum('item_kind', ['service', 'product']);
            $table->string('description', 255);
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_subtotal', 12, 2);
            $table->decimal('line_vat', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index(['billing_document_id', 'item_kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_document_items');
    }
};
