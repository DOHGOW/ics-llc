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
 * CMS Page (content_pages). Public, tier-1 content. Reuses the content engine (D-038)
 * + authorship (D-052). NOT org-owned.
 */
class Page extends Model implements ContentAccessible
{
    use HasAuthorship;
    use HasContentLifecycle;
    use HasFullTextSearch;
    use SoftDeletes;

    protected $table = 'content_pages';

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'body', 'template',
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
        return AccessStrategy::LATERAL;
    }

    public function accessTier(): int
    {
        return 1;
    }

    public function contentModule(): string
    {
        return 'cms';
    }
}
