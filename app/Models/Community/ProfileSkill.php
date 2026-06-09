<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Model;

/** Profile↔skill pivot with cached endorsement_count (community_profile_skills). */
class ProfileSkill extends Model
{
    protected $table = 'community_profile_skills';

    protected $fillable = ['profile_id', 'skill_id', 'endorsement_count'];
}
