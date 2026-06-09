<?php

namespace App\Models\Marketplace;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Marketplace listing (marketplace_listings). Access = LISTING-STATUS + REVIEW + OWNER
 * (D-057/D-060) — NOT AccountScope, NOT ContentAccessible. `organisation_id` is provenance,
 * not isolation (D-060 #1). Lifecycle via MarketplaceListingService (NOT HasContentLifecycle).
 */
class MarketplaceListing extends Model
{
    use SoftDeletes;

    protected $table = 'marketplace_listings';

    public const STATUSES = ['draft', 'pending_review', 'published', 'expired', 'rejected', 'removed'];

    protected $fillable = [
        'tenant_id', 'posted_by_id', 'organisation_id', 'category_id', 'title', 'description',
        'type', 'deadline', 'value', 'currency', 'requirements', 'location', 'is_remote',
        'status', 'published_at', 'shared_by_profile_id',
    ];

    protected function casts(): array
    {
        return ['deadline' => 'date', 'is_remote' => 'boolean', 'value' => 'decimal:2', 'published_at' => 'datetime'];
    }

    /** Public scope: published AND not past deadline (lazy expiry filter, D-060). */
    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', now()->toDateString()));
    }

    public function applications(): HasMany
    {
        return $this->hasMany(MarketplaceApplication::class, 'listing_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ListingReport::class, 'listing_id');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function ownedBy(User $user): bool
    {
        return (int) $this->posted_by_id === (int) $user->id;
    }
}
