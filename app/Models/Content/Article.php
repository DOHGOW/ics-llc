<?php

namespace App\Models\Content;

use App\Content\AccessStrategy;
use App\Content\ContentAccessible;
use App\Models\Concerns\HasAuthorship;
use App\Models\Concerns\HasContentLifecycle;
use App\Models\Concerns\HasFullTextSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CMS Article (content_articles). Public, tier-1 content (NOT org-owned — no
 * AccountScope). Reuses the Unified Content Engine (D-038) + authorship (D-052).
 */
class Article extends Model implements ContentAccessible
{
    use HasAuthorship;
    use HasContentLifecycle;
    use HasFullTextSearch;
    use SoftDeletes;

    protected $table = 'content_articles';

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'excerpt', 'body', 'featured_image',
        'seo_title', 'seo_description', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function toSearchableColumns(): array
    {
        return ['title', 'body'];
    }

    public function accessStrategy(): string
    {
        return AccessStrategy::LATERAL; // tier 1 → all strategies permit public
    }

    public function accessTier(): int
    {
        return 1; // public
    }

    public function contentModule(): string
    {
        return 'cms';
    }
}
