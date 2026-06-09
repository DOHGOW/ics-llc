<?php

/*
| Migration: create_program_event_judges_table  (Wave 5C / H-2)
| Judges are EXISTING ecosystem users (mentors/staff/invited) REFERENCED here — NOT a new
| investor/judge registry (H-2). One judge per event (unique).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_event_judges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('user_id');          // existing identity (no registry)
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id'], 'uk_program_event_judge');
            $table->foreign('event_id', 'fk_program_event_judges_event')
                ->references('id')->on('program_events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_event_judges');
    }
};
