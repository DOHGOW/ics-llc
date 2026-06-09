<?php

/*
| Migration: startup programs + enrollments  (Wave 5A; Incubator/Accelerator extend these in 5b/5c)
| `type` (general/incubator/accelerator) is the program track authority — startups derive their
| program participation from enrollments (D-063: program_type removed from startup_profiles).
| Program participation is the membership key for incubation/acceleration lifecycle stages.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->enum('type', ['general', 'incubator', 'accelerator']);
            $table->string('cohort_name', 100)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('max_startups')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
            $table->timestamps();
        });

        Schema::create('startup_program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('startup_id');
            $table->unsignedBigInteger('program_id');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('graduated_at')->nullable();
            $table->enum('status', ['active', 'graduated', 'withdrawn'])->default('active');
            $table->timestamps();

            $table->unique(['startup_id', 'program_id'], 'uk_program_enrollment');
            $table->index('startup_id', 'idx_startup_prog_enroll_startup');
            $table->foreign('startup_id', 'fk_startup_prog_enroll_startup')
                ->references('id')->on('startup_profiles')->cascadeOnDelete();
            $table->foreign('program_id', 'fk_startup_prog_enroll_program')
                ->references('id')->on('startup_programs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_program_enrollments');
        Schema::dropIfExists('startup_programs');
    }
};
