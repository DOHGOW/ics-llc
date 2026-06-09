<?php

/*
| Migration: extend startup_program_enrollments for governed intake  (Wave 5B / M-1 / D-067)
| The 5A enrollment table becomes the GENERIC, governed participation record (D-065 — one
| architecture, no duplicate). Adds cohort_id + the full intake flow status (applied →
| under_review → accepted → active → graduated → withdrawn / removed) + decision/reason fields.
| D-067: unique (startup_id, cohort_id) — a startup cannot enter the same cohort twice.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('startup_program_enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('cohort_id')->nullable()->after('program_id');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->text('removal_reason')->nullable();

            $table->index('cohort_id', 'idx_startup_prog_enroll_cohort');
            $table->unique(['startup_id', 'cohort_id'], 'uk_startup_cohort'); // D-067
        });

        // Widen the status enum to the governed intake flow (M-1). MySQL-specific MODIFY;
        // on SQLite (test DB) the column is stored as text, so the new values just work.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE startup_program_enrollments MODIFY status '
                ."ENUM('applied','under_review','accepted','active','graduated','withdrawn','removed') "
                ."NOT NULL DEFAULT 'applied'"
            );
        }
    }

    public function down(): void
    {
        Schema::table('startup_program_enrollments', function (Blueprint $table) {
            $table->dropUnique('uk_startup_cohort');
            $table->dropIndex('idx_startup_prog_enroll_cohort');
            $table->dropColumn(['cohort_id', 'applied_at', 'decided_at', 'decided_by', 'withdrawal_reason', 'removal_reason']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE startup_program_enrollments MODIFY status '
                ."ENUM('active','graduated','withdrawn') NOT NULL DEFAULT 'active'"
            );
        }
    }
};
