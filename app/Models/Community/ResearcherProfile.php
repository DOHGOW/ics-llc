<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Researcher CTI extension. `author_id` link never exposed; no restricted research leak (W4b-1). */
class ResearcherProfile extends Model
{
    protected $table = 'community_researcher_profiles';

    protected $fillable = ['profile_id', 'author_id', 'institution', 'research_areas', 'academic_degree', 'orcid_id', 'publications_count'];

    protected function casts(): array
    {
        return ['research_areas' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CommunityProfile::class, 'profile_id');
    }

    public function publicFields(): array
    {
        return $this->only(['institution', 'research_areas', 'academic_degree', 'orcid_id', 'publications_count']);
    }
}
