<?php

/*
| Migration: create_research_publication_authors_table  (Wave 3 / D-034)
| M2M pivot linking publications ↔ authors with `author_order` (citation ordering).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_publication_authors', function (Blueprint $table) {
            $table->unsignedBigInteger('publication_id');
            $table->unsignedBigInteger('author_id');
            $table->unsignedTinyInteger('author_order')->default(1);

            $table->primary(['publication_id', 'author_id']);
            $table->index('author_id', 'idx_rpa_author');

            $table->foreign('publication_id', 'fk_rpa_publication')
                ->references('id')->on('research_publications')->cascadeOnDelete();
            $table->foreign('author_id', 'fk_rpa_author')
                ->references('id')->on('research_authors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_publication_authors');
    }
};
