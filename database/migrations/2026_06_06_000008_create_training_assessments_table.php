<?php

/*
| Migration: create_training_assessments_table  (Wave 4a)
| Quiz/assignment/exam attached to a course (or a lesson). pass_score + max_attempts govern.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id')->nullable(); // NULL = course-level
            $table->string('title');
            $table->enum('type', ['quiz', 'assignment', 'exam'])->default('quiz');
            $table->unsignedTinyInteger('pass_score')->default(70);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->unsignedSmallInteger('time_limit_min')->nullable();
            $table->timestamps();

            $table->index('course_id', 'idx_training_assess_course');
            $table->foreign('course_id', 'fk_training_assess_course')
                ->references('id')->on('training_courses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assessments');
    }
};
