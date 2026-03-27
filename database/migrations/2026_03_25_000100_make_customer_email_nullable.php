<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->usesSqlite()) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if ($this->usesSqlite()) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
            $table->string('phone')->nullable()->change();
        });
    }

    private function usesSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }
};
