<?php

/*
| Migration: create_training_lessons_table  (Wave 4a / D-057)
| ENROLLMENT-gated content. `is_preview=1` lessons are the ONLY publicly accessible lessons;
| all others require an active enrollment (TrainingAccessService).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('title');
            $table->enum('type', ['video', 'pdf', 'text', 'quiz', 'assignment']);
            $table->longText('content')->nullable();         // gated body (enrollment)
            $table->string('video_embed_url', 500)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_preview')->default(false);   // D-057 public preview
            $table->timestamps();

            $table->index('course_id', 'idx_training_lessons_course');
            $table->index('section_id', 'idx_training_lessons_section');
            $table->foreign('course_id', 'fk_training_lessons_course')
                ->references('id')->on('training_courses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_lessons');
    }
};
