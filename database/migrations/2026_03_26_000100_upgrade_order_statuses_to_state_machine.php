<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $runner = function (): void {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    ALTER TABLE orders
                    MODIFY status ENUM(
                        'received',
                        'diagnostic',
                        'repairing',
                        'quote',
                        'ready',
                        'delivered',
                        'not_repaired',
                        'created',
                        'diagnosing',
                        'quoted',
                        'approved',
                        'in_repair',
                        'completed',
                        'closed',
                        'canceled'
                    ) NOT NULL DEFAULT 'created'
                ");
            }

            $this->migrateStatuses([
                'received' => 'created',
                'diagnostic' => 'diagnosing',
                'quote' => 'quoted',
                'repairing' => 'in_repair',
                'ready' => 'completed',
                'not_repaired' => 'canceled',
            ]);

            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    ALTER TABLE orders
                    MODIFY status ENUM(
                        'created',
                        'diagnosing',
                        'quoted',
                        'approved',
                        'in_repair',
                        'completed',
                        'delivered',
                        'closed',
                        'canceled'
                    ) NOT NULL DEFAULT 'created'
                ");
            }
        };

        if (DB::getDriverName() === 'mysql') {
            $runner();

            return;
        }

        DB::transaction($runner);
    }

    public function down(): void
    {
        $runner = function (): void {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    ALTER TABLE orders
                    MODIFY status ENUM(
                        'received',
                        'diagnostic',
                        'repairing',
                        'quote',
                        'ready',
                        'delivered',
                        'not_repaired',
                        'created',
                        'diagnosing',
                        'quoted',
                        'approved',
                        'in_repair',
                        'completed',
                        'closed',
                        'canceled'
                    ) NOT NULL DEFAULT 'received'
                ");
            }

            $this->migrateStatuses([
                'created' => 'received',
                'diagnosing' => 'diagnostic',
                'quoted' => 'quote',
                'approved' => 'quote',
                'in_repair' => 'repairing',
                'completed' => 'ready',
                'closed' => 'delivered',
                'canceled' => 'not_repaired',
            ]);

            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    ALTER TABLE orders
                    MODIFY status ENUM(
                        'received',
                        'diagnostic',
                        'repairing',
                        'quote',
                        'ready',
                        'delivered',
                        'not_repaired'
                    ) NOT NULL DEFAULT 'received'
                ");
            }
        };

        if (DB::getDriverName() === 'mysql') {
            $runner();

            return;
        }

        DB::transaction($runner);
    }

    private function migrateStatuses(array $map): void
    {
        foreach ($map as $from => $to) {
            DB::table('orders')
                ->where('status', $from)
                ->update(['status' => $to]);
        }
    }
};
