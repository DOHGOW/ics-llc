<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

/** Immutable pre-publication review decision (marketplace_listing_reviews). */
class ListingReview extends Model
{
    protected $table = 'marketplace_listing_reviews';

    public $timestamps = false;

    protected $fillable = ['listing_id', 'reviewed_by', 'decision', 'notes', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
