<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_intents', function (Blueprint $table): void {
            $table->id();
            $table->string('access_token', 100)->unique();
            $table->json('payload_snapshot');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->string('confirmation_token', 100)->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_intents');
    }
};
