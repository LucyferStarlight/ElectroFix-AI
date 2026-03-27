<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('version')->default(1)->after('document_type');
            $table->string('status', 20)->default('approved')->after('version');
            $table->boolean('is_active')->default(false)->after('status');

            $table->index(['order_id', 'document_type', 'version']);
            $table->index(['order_id', 'document_type', 'is_active']);
        });

        DB::table('billing_documents')
            ->where('document_type', 'quote')
            ->update([
                'status' => 'draft',
                'is_active' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->dropIndex(['order_id', 'document_type', 'version']);
            $table->dropIndex(['order_id', 'document_type', 'is_active']);
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn(['version', 'status', 'is_active']);
        });
    }
};
