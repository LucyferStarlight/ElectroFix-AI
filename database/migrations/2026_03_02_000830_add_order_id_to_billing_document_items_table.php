<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_document_items', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->after('inventory_item_id')->constrained('orders')->nullOnDelete();
            $table->index(['order_id', 'item_kind']);
        });
    }

    public function down(): void
    {
        Schema::table('billing_document_items', function (Blueprint $table): void {
            $table->dropIndex('billing_document_items_order_id_item_kind_index');
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
