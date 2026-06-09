<?php

/*
| Migration: create_research_authors_table  (Wave 3 / D-034)
| Author identity — distinct from platform users. `user_id` is NULL for EXTERNAL authors
| (W3-8); `orcid_id` enables external dedup/interoperability.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_authors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // NULL = external author
            $table->string('name');
            $table->string('title', 150)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path', 500)->nullable();
            $table->string('email')->nullable();
            $table->string('organisation')->nullable();
            $table->string('orcid_id', 50)->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_research_authors_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_authors');
    }
};
