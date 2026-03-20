<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('status', 30)->default('active')->after('currency');
            $table->unsignedInteger('pending_attempts')->default(0)->after('status');
            $table->timestamp('pending_expires_at')->nullable()->after('pending_attempts');
            $table->timestamp('pending_last_failed_at')->nullable()->after('pending_expires_at');
            $table->string('pending_plan', 50)->nullable()->after('pending_last_failed_at');
            $table->string('pending_billing_period', 20)->nullable()->default('monthly')->after('pending_plan');
            $table->string('stripe_checkout_session_id', 120)->nullable()->after('pending_billing_period');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'status',
                'pending_attempts',
                'pending_expires_at',
                'pending_last_failed_at',
                'pending_plan',
                'pending_billing_period',
                'stripe_checkout_session_id',
            ]);
        });
    }
};
