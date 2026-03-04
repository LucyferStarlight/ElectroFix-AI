<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('ai_requests_used')->default(0);
            $table->unsignedInteger('ai_tokens_used')->default(0);
            $table->date('current_cycle_start');
            $table->date('current_cycle_end');
            $table->unsignedInteger('overage_requests')->default(0);
            $table->unsignedInteger('overage_tokens')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage');
    }
};
