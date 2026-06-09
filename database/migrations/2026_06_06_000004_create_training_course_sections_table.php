<?php

/*
| Migration: create_training_course_sections_table  (Wave 4a)
| Child of training_courses (cascade). Structural grouping for lessons.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_course_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('course_id', 'idx_training_sections_course');
            $table->foreign('course_id', 'fk_training_sections_course')
                ->references('id')->on('training_courses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_course_sections');
    }
};
