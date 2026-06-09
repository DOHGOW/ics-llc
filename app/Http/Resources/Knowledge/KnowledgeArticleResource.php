<?php

namespace App\Http\Resources\Knowledge;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * W3-3 teaser projection. Public fields (title/excerpt/SEO) are ALWAYS returned; `body` is
 * included ONLY when the caller is entitled (ContentAccessService::canAccess). The
 * entitlement is decided in the controller and passed in — this resource never queries
 * access itself and never weakens ContentAccessService.
 */
class KnowledgeArticleResource extends JsonResource
{
    public bool $entitled = false;

    public static function for($article, bool $entitled): self
    {
        $resource = new self($article);
        $resource->entitled = $entitled;

        return $resource;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'excerpt' => $this->excerpt,          // always public (teaser/SEO)
            'access_tier' => $this->access_tier,
            'read_time_min' => $this->read_time_min,
            'featured_image' => $this->featured_image,
            'seo' => [
                'title' => $this->seo_title ?: $this->title,
                'description' => $this->seo_description ?: $this->excerpt,
            ],
            'published_at' => optional($this->published_at)->toIso8601String(),
            // Gated — only for entitled callers (W3-3):
            'body' => $this->entitled ? $this->body : null,
            'video_embed_url' => $this->entitled ? $this->video_embed_url : null,
            'entitled' => $this->entitled,
        ];
    }
}
