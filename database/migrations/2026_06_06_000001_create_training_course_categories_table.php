<?php

/*
| Migration: create_training_course_categories_table  (Wave 4a)
| Reference taxonomy. Not access-gated.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('parent_id', 'idx_training_cats_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_course_categories');
    }
};
