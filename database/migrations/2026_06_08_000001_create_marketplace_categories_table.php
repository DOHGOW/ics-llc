<?php

/*
| Migration: create_marketplace_categories_table  (Wave 4c / D-011)
| Reference taxonomy by listing type.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->enum('listing_type', ['grant', 'tender', 'job', 'internship', 'scholarship', 'fellowship', 'accelerator']);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_categories');
    }
};
