<?php

/*
| Migration: create_marketplace_applications_table  (Wave 4c / D-060)
| PRIVATE to applicant + listing poster + ICS (D-060 #5). Duplicate prevention via the unique
| (listing_id, applicant_id) constraint (D-060). Attachments streamed/gated (W4-7/W2-5).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('applicant_id');
            $table->text('cover_letter')->nullable();
            $table->json('attachments')->nullable();   // file paths — gated/streamed
            $table->enum('status', ['submitted', 'under_review', 'shortlisted', 'accepted', 'rejected'])->default('submitted');
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['listing_id', 'applicant_id'], 'uk_mkt_applications'); // D-060 duplicate prevention
            $table->index('applicant_id', 'idx_mkt_applications_applicant');

            $table->foreign('listing_id', 'fk_mkt_applications_listing')
                ->references('id')->on('marketplace_listings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_applications');
    }
};
