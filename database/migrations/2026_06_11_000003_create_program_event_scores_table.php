<?php

/*
| Migration: create_program_event_scores_table  (Wave 5C / M-4 / H-3)
| One score per judge × startup × criterion (integrity, M-4). Used by demo_day/pitch_event
| (judging) AND readiness_review (checkpoint ratings) — ONE scoring mechanism (M-1). Scores are
| OPERATIONAL-MATURITY data only (H-3) — NEVER valuation/equity/financial. Ranking is derived.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_event_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('judge_id');         // = program_event_judges.user_id
            $table->unsignedBigInteger('startup_id');
            $table->string('criterion', 100);                // e.g. team, traction, readiness checkpoint
            $table->decimal('score', 5, 2);                  // maturity rating (H-3) — not financial
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'judge_id', 'startup_id', 'criterion'], 'uk_program_event_score');
            $table->index(['event_id', 'startup_id'], 'idx_program_event_scores_event_startup');
            $table->foreign('event_id', 'fk_program_event_scores_event')
                ->references('id')->on('program_events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_event_scores');
    }
};
