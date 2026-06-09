<?php

/*
| Migration: create_content_media_table  (Wave 1c / D-024 / D-028)
| Media library asset (not lifecycle content). `alt_text` is REQUIRED-validated for
| images at the controller (WCAG 1.1.1 / W1c-2).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->enum('type', ['image', 'document', 'video', 'other']);
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_kb');
            $table->string('alt_text')->nullable();   // required for images (validation)
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type', 'idx_content_media_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_media');
    }
};
