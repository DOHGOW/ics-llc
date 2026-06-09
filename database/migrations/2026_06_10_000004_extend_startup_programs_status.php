<?php

/*
| Migration: extend startup_programs status  (Wave 5B / H-2 / D-067)
| Program-level governance states: suspension / reinstatement / termination / archival
| (HIGH-audited governance events, D-066). MySQL-guarded enum widening.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE startup_programs MODIFY status '
                ."ENUM('planned','active','suspended','completed','terminated','archived','cancelled') "
                ."NOT NULL DEFAULT 'planned'"
            );
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE startup_programs MODIFY status '
                ."ENUM('planned','active','completed','cancelled') NOT NULL DEFAULT 'planned'"
            );
        }
    }
};
