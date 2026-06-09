<?php

/*
| Migration: create_program_coordinators_table  (Wave 5B / M-2)
| Program coordinators manage cohorts. This is a PROGRAM concern — deliberately NOT CRM
| assignment / HasAssignmentVisibility (M-2). Coordinators manage cohorts; CRM staff manage CRM.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_coordinators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cohort_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['cohort_id', 'user_id'], 'uk_program_coordinator');
            $table->foreign('cohort_id', 'fk_program_coordinators_cohort')
                ->references('id')->on('program_cohorts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_coordinators');
    }
};
