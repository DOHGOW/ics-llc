<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Trainer CTI extension. `instructor_id` link pointer never exposed (W4b-1); ownership-checked (W4b-2). */
class TrainerProfile extends Model
{
    protected $table = 'community_trainer_profiles';

    protected $fillable = ['profile_id', 'instructor_id', 'specializations', 'certifications', 'delivery_modes', 'years_experience', 'courses_count'];

    protected function casts(): array
    {
        return ['specializations' => 'array', 'certifications' => 'array', 'delivery_modes' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CommunityProfile::class, 'profile_id');
    }

    public function publicFields(): array
    {
        return $this->only(['specializations', 'certifications', 'delivery_modes', 'years_experience', 'courses_count']);
    }
}
