<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->boolean('ai_diagnosis_pending')->default(false)->after('ai_diagnosed_at');
            $table->string('ai_diagnosis_error')->nullable()->after('ai_diagnosis_pending');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'ai_diagnosis_pending',
                'ai_diagnosis_error',
            ]);
        });
    }
};
