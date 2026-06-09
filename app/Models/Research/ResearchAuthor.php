<?php

namespace App\Models\Research;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Research author (research_authors). Identity distinct from platform users — `user_id` is
 * NULL for external authors (W3-8); `orcid_id` for external dedup.
 */
class ResearchAuthor extends Model
{
    protected $table = 'research_authors';

    protected $fillable = [
        'tenant_id', 'user_id', 'name', 'title', 'bio', 'avatar_path', 'email', 'organisation', 'orcid_id',
    ];

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(ResearchPublication::class, 'research_publication_authors', 'author_id', 'publication_id')
            ->withPivot('author_order');
    }
}
