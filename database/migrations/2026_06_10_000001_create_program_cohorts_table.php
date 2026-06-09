<?php

/*
| Migration: create_program_cohorts_table  (Wave 5B / D-065)
| GENERIC Program Architecture — cohorts (intake cycles) are first-class and SHARED by
| Incubator and Accelerator (D-065). A cohort belongs to a startup_program (type carries
| incubator/accelerator). Closure/archival audited (D-067).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_cohorts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('program_id');
            $table->string('name');                         // e.g. "2026 Spring Cohort"
            $table->timestamp('intake_opens_at')->nullable();
            $table->timestamp('intake_closes_at')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('max_startups')->nullable();
            $table->enum('status', ['planned', 'intake_open', 'active', 'closed', 'archived'])->default('planned');
            $table->timestamps();

            $table->index('program_id', 'idx_program_cohorts_program');
            $table->index('status', 'idx_program_cohorts_status');
            $table->foreign('program_id', 'fk_program_cohorts_program')
                ->references('id')->on('startup_programs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_cohorts');
    }
};
