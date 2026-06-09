<?php

/*
| Migration: create_marketplace_listing_reviews_table  (Wave 4c / D-011)
| Immutable decision log for the mandatory pre-publication review (separate from the audit
| trail). One record per reviewer decision.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listing_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('reviewed_by');
            $table->enum('decision', ['approve', 'reject']);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('listing_id', 'idx_mkt_reviews_listing');
            $table->foreign('listing_id', 'fk_mkt_reviews_listing')
                ->references('id')->on('marketplace_listings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listing_reviews');
    }
};
