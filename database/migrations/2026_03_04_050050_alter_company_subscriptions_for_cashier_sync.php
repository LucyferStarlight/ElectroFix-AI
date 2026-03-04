<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_subscriptions', function (Blueprint $table): void {
            $table->foreignId('plan_id')->nullable()->after('company_id')->constrained('plans')->nullOnDelete();
            $table->string('stripe_subscription_id')->nullable()->unique()->after('plan_id');
            $table->enum('billing_period', ['monthly', 'semiannual', 'annual'])->nullable()->after('stripe_subscription_id');
            $table->timestamp('current_period_end')->nullable()->after('billing_period');
            $table->boolean('cancel_at_period_end')->default(false)->after('current_period_end');
        });

        DB::table('company_subscriptions')
            ->whereNull('billing_period')
            ->update([
                'billing_period' => DB::raw("CASE WHEN billing_cycle = 'yearly' THEN 'annual' ELSE 'monthly' END"),
                'current_period_end' => DB::raw('ends_at'),
            ]);

        $plans = DB::table('plans')->pluck('id', 'name');
        foreach ($plans as $name => $id) {
            DB::table('company_subscriptions')
                ->where('plan', $name)
                ->whereNull('plan_id')
                ->update(['plan_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('company_subscriptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn([
                'stripe_subscription_id',
                'billing_period',
                'current_period_end',
                'cancel_at_period_end',
            ]);
        });
    }
};
