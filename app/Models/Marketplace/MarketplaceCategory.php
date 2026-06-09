<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

/** Marketplace category (reference data). */
class MarketplaceCategory extends Model
{
    protected $table = 'marketplace_categories';

    protected $fillable = ['name', 'slug', 'listing_type', 'sort_order'];
}
