<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Abuse report (marketplace_listing_reports, D-060). Creation = analytics; resolution = audited. */
class ListingReport extends Model
{
    protected $table = 'marketplace_listing_reports';

    public const REASONS = ['spam', 'scam', 'inappropriate', 'duplicate', 'other'];

    public const STATUSES = ['open', 'reviewed', 'dismissed', 'actioned'];

    protected $fillable = [
        'tenant_id', 'listing_id', 'reporter_id', 'reason', 'details', 'status', 'reviewed_by',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }
}
