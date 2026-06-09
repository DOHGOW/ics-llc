<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partner CTI extension (community_partner_profiles). `partner_id` links to the Partner
 * Portal but is NEVER exposed and NEVER joined — no referrals/commissions/agreements/
 * account_id leak (W4b-1/W2-3). Ownership-checked at link time (W4b-2). Named to avoid
 * collision with App\Models\Partner\PartnerProfile.
 */
class PartnerCommunityProfile extends Model
{
    protected $table = 'community_partner_profiles';

    protected $fillable = ['profile_id', 'partner_id', 'organisation_name', 'partnership_types', 'service_areas', 'coverage_regions'];

    protected function casts(): array
    {
        return ['partnership_types' => 'array', 'service_areas' => 'array', 'coverage_regions' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CommunityProfile::class, 'profile_id');
    }

    /** W4b-1: only the public partner-facing fields stored here — never the Partner Portal internals. */
    public function publicFields(): array
    {
        return $this->only(['organisation_name', 'partnership_types', 'service_areas', 'coverage_regions']);
    }
}
