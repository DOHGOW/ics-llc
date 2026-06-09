<?php

namespace App\Models\Research;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Research taxonomy (research_categories). Reference data; not tier-gated. */
class ResearchCategory extends Model
{
    protected $table = 'research_categories';

    protected $fillable = ['tenant_id', 'name', 'slug', 'description', 'parent_id', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(ResearchPublication::class, 'category_id');
    }
}
