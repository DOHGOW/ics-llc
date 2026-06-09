<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Startup CTI extension. `startup_id` link pointer never exposed (W4b-1). */
class StartupProfile extends Model
{
    protected $table = 'community_startup_profiles';

    protected $fillable = ['profile_id', 'startup_id', 'founding_year', 'team_size', 'stage', 'industry', 'business_model', 'seeking'];

    protected function casts(): array
    {
        return ['seeking' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CommunityProfile::class, 'profile_id');
    }

    public function publicFields(): array
    {
        return $this->only(['founding_year', 'team_size', 'stage', 'industry', 'business_model', 'seeking']);
    }
}
