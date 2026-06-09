<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Model;

/** Peer skill endorsement (community_endorsements). Analytics, not audited (W4b-6). */
class Endorsement extends Model
{
    protected $table = 'community_endorsements';

    protected $fillable = ['profile_id', 'skill_id', 'endorsed_by_id'];
}
