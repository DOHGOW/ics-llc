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
 * Knowledge resource (knowledge_resources) — downloadable asset. Same engine reuse as
 * KnowledgeArticle (LATERAL, D-036). `description` is the public teaser; `file_path` is the
 * tier-gated download (W3-3, enforced in the resource + the gated download endpoint).
 */
class KnowledgeResource extends Model implements ContentAccessible
{
    use HasContentLifecycle;
    use HasFullTextSearch;
    use SoftDeletes;

    protected $table = 'knowledge_resources';

    protected $fillable = [
        'tenant_id', 'category_id', 'type', 'title', 'slug', 'description', 'file_path',
        'file_size_kb', 'access_tier', 'status', 'seo_title', 'seo_description',
        'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function toSearchableColumns(): array
    {
        return ['title', 'description'];
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
