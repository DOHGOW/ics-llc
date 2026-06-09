<?php

/*
| Migration: create_marketplace_listing_reports_table  (Wave 4c / D-060 — NEW table)
| Abuse reporting. Any authenticated user may report a published listing (one OPEN report per
| reporter+listing). Reaching a configurable threshold auto-hides the listing (→ pending_review).
| Report RESOLUTION is audited (MARKETPLACE_MANAGEMENT); report CREATION is analytics.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listing_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('reporter_id');
            $table->enum('reason', ['spam', 'scam', 'inappropriate', 'duplicate', 'other']);
            $table->text('details')->nullable();
            $table->enum('status', ['open', 'reviewed', 'dismissed', 'actioned'])->default('open');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->unique(['listing_id', 'reporter_id'], 'uk_mkt_reports'); // one report per user/listing
            $table->index('status', 'idx_mkt_reports_status');

            $table->foreign('listing_id', 'fk_mkt_reports_listing')
                ->references('id')->on('marketplace_listings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listing_reports');
    }
};
