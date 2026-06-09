<?php

/*
| Migration: create_training_assessment_questions_table  (Wave 4a / W4-5)
| SECURITY-SENSITIVE: `correct_answer` is NEVER serialised to learners — it is excluded from
| every learner-facing query/resource and used ONLY server-side for grading.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->text('question_text');
            $table->enum('type', ['mcq', 'true_false', 'short_answer']);
            $table->json('options')->nullable();           // MCQ options (no correctness flag)
            $table->text('correct_answer');                 // W4-5: server-side ONLY
            $table->unsignedTinyInteger('marks')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('assessment_id', 'idx_training_questions_assess');
            $table->foreign('assessment_id', 'fk_training_questions_assess')
                ->references('id')->on('training_assessments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assessment_questions');
    }
};
