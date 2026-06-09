<?php

namespace App\Models\Knowledge;

use App\Content\AccessStrategy;
use App\Content\ContentAccessible;
use App\Models\Concerns\HasContentLifecycle;
use App\Models\Concerns\HasFullTextSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Knowledge article (knowledge_articles). Reuses the Unified Content Engine (D-038):
 * lifecycle + FULLTEXT + ContentAccessible with the LATERAL strategy (D-036). The row's
 * `access_tier` is the gate (strategy-relative, W3-1). `excerpt` is the public teaser;
 * `body` is tier-gated (W3-3, enforced in the resource).
 */
class KnowledgeArticle extends Model implements ContentAccessible
{
    use HasContentLifecycle;
    use HasFullTextSearch;
    use SoftDeletes;

    protected $table = 'knowledge_articles';

    protected $fillable = [
        'tenant_id', 'category_id', 'type', 'title', 'slug', 'excerpt', 'body',
        'featured_image', 'video_embed_url', 'access_tier', 'status', 'read_time_min',
        'seo_title', 'seo_description', 'metadata', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'published_at' => 'datetime'];
    }

    public function toSearchableColumns(): array
    {
        return ['title', 'excerpt', 'body'];
    }

    public function accessStrategy(): string
    {
        return AccessStrategy::LATERAL; // D-036
    }

    public function accessTier(): int
    {
        return (int) $this->access_tier;
    }

    public function contentModule(): string
    {
        return 'knowledge';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category_id');
    }
}
