<?php

namespace App\Models\Research;

use App\Content\AccessStrategy;
use App\Content\ContentAccessible;
use App\Models\Concerns\HasContentLifecycle;
use App\Models\Concerns\HasFullTextSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Research publication (research_publications). Reuses the Unified Content Engine (D-038):
 * lifecycle + FULLTEXT + ContentAccessible with the HIERARCHICAL strategy (D-034,
 * user_tier >= tier). The row's `access_tier` is strategy-relative (W3-1). `abstract` is the
 * public teaser; `body` is tier-gated (W3-3, enforced in the resource).
 */
class ResearchPublication extends Model implements ContentAccessible
{
    use HasContentLifecycle;
    use HasFullTextSearch;
    use SoftDeletes;

    protected $table = 'research_publications';

    protected $fillable = [
        'tenant_id', 'category_id', 'content_group', 'title', 'slug', 'abstract', 'body',
        'file_path', 'file_size_kb', 'doi', 'publish_date', 'access_tier', 'status',
        'seo_title', 'seo_description', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['publish_date' => 'date', 'published_at' => 'datetime'];
    }

    public function toSearchableColumns(): array
    {
        return ['title', 'abstract'];
    }

    public function accessStrategy(): string
    {
        return AccessStrategy::HIERARCHICAL; // D-034
    }

    public function accessTier(): int
    {
        return (int) $this->access_tier;
    }

    public function contentModule(): string
    {
        return 'research';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ResearchCategory::class, 'category_id');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(ResearchAuthor::class, 'research_publication_authors', 'publication_id', 'author_id')
            ->withPivot('author_order')
            ->orderBy('author_order');
    }
}
