<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Founder CTI extension. `startup_id` is a link pointer (W4b-2) — never exposed (W4b-1). */
class FounderProfile extends Model
{
    protected $table = 'community_founder_profiles';

    protected $fillable = ['profile_id', 'startup_id', 'stage', 'industries', 'seeking', 'years_experience'];

    protected function casts(): array
    {
        return ['industries' => 'array', 'seeking' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CommunityProfile::class, 'profile_id');
    }

    /** W4b-1: public-only fields (no startup_id link pointer). */
    public function publicFields(): array
    {
        return $this->only(['stage', 'industries', 'seeking', 'years_experience']);
    }
}
