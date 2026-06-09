<?php

/*
| Migration: create_training_assessment_submissions_table  (Wave 4a)
| A learner attempt. Scored server-side (auto for objective, instructor for subjective).
| attempt_number bounded by assessment.max_attempts. graded_by NULL = auto-graded.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_assessment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->json('answers');
            $table->decimal('score', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('graded_at')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable(); // NULL = auto
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->index('enrollment_id', 'idx_training_submissions_enrollment');
            $table->index('assessment_id', 'idx_training_submissions_assessment');
            $table->foreign('enrollment_id', 'fk_training_submissions_enrollment')
                ->references('id')->on('training_enrollments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assessment_submissions');
    }
};
