<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Consultant CTI extension. Creation fires a one-way CRM lead capture (W4b-3, D-053). */
class ConsultantProfile extends Model
{
    protected $table = 'community_consultant_profiles';

    protected $fillable = ['profile_id', 'expertise_areas', 'years_experience', 'certifications', 'languages', 'availability', 'engagement_types'];

    protected function casts(): array
    {
        return [
            'expertise_areas' => 'array',
            'certifications' => 'array',
            'languages' => 'array',
            'engagement_types' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CommunityProfile::class, 'profile_id');
    }

    public function publicFields(): array
    {
        return $this->only(['expertise_areas', 'years_experience', 'certifications', 'languages', 'availability', 'engagement_types']);
    }
}
