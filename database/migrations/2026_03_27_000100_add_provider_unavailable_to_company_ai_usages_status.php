<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE company_ai_usages
             MODIFY status ENUM('success','blocked_plan','blocked_quota','blocked_tokens','provider_unavailable','error')
             NOT NULL"
        );
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE company_ai_usages
             MODIFY status ENUM('success','blocked_plan','blocked_quota','blocked_tokens','error')
             NOT NULL"
        );
    }
};
