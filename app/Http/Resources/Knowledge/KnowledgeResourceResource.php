<?php

namespace App\Http\Resources\Knowledge;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * W3-3 teaser projection for downloadable resources. `description` (teaser) + metadata are
 * public; the download is signalled as available ONLY for entitled callers — the file itself
 * is served exclusively through the gated download endpoint (never a public file_path).
 */
class KnowledgeResourceResource extends JsonResource
{
    public bool $entitled = false;

    public static function for($resource, bool $entitled): self
    {
        $r = new self($resource);
        $r->entitled = $entitled;

        return $r;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,  // always public (teaser/SEO)
            'access_tier' => $this->access_tier,
            'file_size_kb' => $this->file_size_kb,
            'seo' => [
                'title' => $this->seo_title ?: $this->title,
                'description' => $this->seo_description ?: $this->description,
            ],
            'published_at' => optional($this->published_at)->toIso8601String(),
            // Gated — never expose file_path; only signal downloadability (W3-3/W2-5):
            'downloadable' => $this->entitled && $this->file_path !== null,
            'entitled' => $this->entitled,
        ];
    }
}
