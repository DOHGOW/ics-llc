<?php

/*
| Migration: create_program_events_table  (Wave 5C / D-068 / M-1)
| The GENERIC, LIGHTWEIGHT Program Events layer — ONE table for all accelerator event types
| (demo_day / pitch_event / showcase / readiness_review / graduation_showcase). NO workflow
| states / orchestration / process-engine behavior (governance direction) — only a minimal
| `finalized_at` lock. Scoped to a cohort; reusable ecosystem infrastructure.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('cohort_id');
            $table->enum('type', ['demo_day', 'pitch_event', 'showcase', 'readiness_review', 'graduation_showcase']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('finalized_at')->nullable();   // lock — not a workflow state machine
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('cohort_id', 'idx_program_events_cohort');
            $table->index('type', 'idx_program_events_type');
            $table->foreign('cohort_id', 'fk_program_events_cohort')
                ->references('id')->on('program_cohorts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_events');
    }
};
