<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Knowledge taxonomy (knowledge_categories). Reference data; not tier-gated. */
class KnowledgeCategory extends Model
{
    protected $table = 'knowledge_categories';

    protected $fillable = ['tenant_id', 'name', 'slug', 'icon', 'parent_id', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, 'category_id');
    }
}
