<?php

/*
| Migration: create_training_lesson_progress_table  (Wave 4a)
| Per-enrollment, per-lesson progress. Child of enrollment (cascade). Drives the cached
| enrollment.progress_percent and course completion.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('lesson_id');
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('time_spent_sec')->default(0);
            $table->timestamps();

            $table->unique(['enrollment_id', 'lesson_id'], 'uk_lesson_progress');
            $table->foreign('enrollment_id', 'fk_lesson_progress_enrollment')
                ->references('id')->on('training_enrollments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_lesson_progress');
    }
};
