<?php

/*
| Migration: create_research_categories_table  (Wave 3 / D-034)
| Reference taxonomy (hierarchical via parent_id). NOT tier-gated content.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('parent_id', 'idx_research_cats_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_categories');
    }
};
