<?php

/*
| Migration: create_knowledge_categories_table  (Wave 3 / D-036)
| Reference taxonomy (hierarchical via parent_id). NOT tier-gated content — public
| catalogue managed by ICS Content staff.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('icon', 100)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('article_count')->default(0); // cached counter
            $table->timestamps();

            $table->index('parent_id', 'idx_knowledge_cats_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_categories');
    }
};
