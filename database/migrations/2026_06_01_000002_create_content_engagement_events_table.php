<?php

/*
|--------------------------------------------------------------------------
| Migration: create_content_engagement_events_table   (Wave 1b / D-038 / D-051)
|--------------------------------------------------------------------------
| Purpose:       Single content analytics table (views/downloads/citations) for
|                CMS/Knowledge/Research. SUPERSEDES knowledge_views,
|                knowledge_downloads, research_downloads (D-051).
| Decision IDs:  D-038, D-051, D-025/D-032 (analytics source).
| Security:      Append-only; may carry IP/country for geo analytics (PII-light).
| Dependencies:  Polymorphic (content_type/content_id) — no FK.
| Extension pts: feeds Analytics Layer + Data Warehouse; partition by created_at at
|                scale (VPS/cloud).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_engagement_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('content_type', 100);
            $table->unsignedBigInteger('content_id');
            $table->enum('event_type', ['view', 'download', 'citation']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('referrer_url', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['content_type', 'content_id'], 'idx_cee_content');
            $table->index('event_type', 'idx_cee_event');
            $table->index('created_at', 'idx_cee_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_engagement_events');
    }
};
