<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_payment_simulations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attempt_no')->unique();
            $table->enum('result', ['approved', 'rejected']);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_payment_simulations');
    }
};
