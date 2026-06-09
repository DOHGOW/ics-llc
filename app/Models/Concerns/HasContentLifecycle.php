<?php

namespace App\Models\Concerns;

use App\Events\Content\ContentArchived;
use App\Events\Content\ContentPublished;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Shared content lifecycle (D-038): draft → under_review → published → archived,
 * auto-slug, SEO, published scope. Reused by CMS/Knowledge/Research — implemented ONCE.
 *
 * Human-approval publish (P-1): callers gate publish() with the module permission
 * (e.g. knowledge.articles.publish); the trait does not auto-publish.
 */
trait HasContentLifecycle
{
    public static function bootHasContentLifecycle(): void
    {
        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = 'draft';
            }
            if (empty($model->slug) && ! empty($model->title)) {
                $model->slug = static::uniqueContentSlug((string) $model->title);
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function submitForReview(): void
    {
        $this->forceFill(['status' => 'under_review'])->save();
    }

    public function publish(): void
    {
        $this->forceFill([
            'status' => 'published',
            'published_at' => $this->published_at ?? now(),
        ])->save();

        event(new ContentPublished($this));
    }

    public function archive(): void
    {
        $this->forceFill(['status' => 'archived'])->save();

        event(new ContentArchived($this));
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at <= now();
    }

    protected static function uniqueContentSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'item';
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
