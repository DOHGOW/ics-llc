<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Application (marketplace_applications). PRIVATE — applicant + listing poster + ICS only
 * (D-060 #5). Unique per (listing, applicant). Attachments streamed/gated (W4-7/W2-5).
 */
class MarketplaceApplication extends Model
{
    protected $table = 'marketplace_applications';

    public const STATUSES = ['submitted', 'under_review', 'shortlisted', 'accepted', 'rejected'];

    protected $fillable = [
        'tenant_id', 'listing_id', 'applicant_id', 'cover_letter', 'attachments', 'status',
        'submitted_at', 'reviewed_at', 'reviewed_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['attachments' => 'array', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }
}
