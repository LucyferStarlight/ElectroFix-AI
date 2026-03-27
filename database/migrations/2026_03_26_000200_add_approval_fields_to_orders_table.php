<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->string('approved_by')->nullable()->after('approved_at');
            $table->string('approval_channel')->nullable()->after('approved_by');
            $table->timestamp('rejected_at')->nullable()->after('approval_channel');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'approved_at',
                'approved_by',
                'approval_channel',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
