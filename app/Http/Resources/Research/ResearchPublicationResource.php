<?php

namespace App\Http\Resources\Research;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * W3-3 teaser projection. `abstract` (+ DOI, authors, SEO) is ALWAYS public for
 * discoverability/citation; `body` and the file are gated behind entitlement
 * (ContentAccessService), decided in the controller and passed in.
 */
class ResearchPublicationResource extends JsonResource
{
    public bool $entitled = false;

    public static function for($publication, bool $entitled): self
    {
        $resource = new self($publication);
        $resource->entitled = $entitled;

        return $resource;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content_group' => $this->content_group,
            'abstract' => $this->abstract,        // always public (teaser/SEO/citation)
            'doi' => $this->doi,
            'access_tier' => $this->access_tier,
            'publish_date' => optional($this->publish_date)->toDateString(),
            'authors' => $this->whenLoaded('authors', fn () => $this->authors->map(fn ($a) => [
                'name' => $a->name,
                'organisation' => $a->organisation,
                'orcid_id' => $a->orcid_id,
                'order' => $a->pivot->author_order,
            ])),
            'seo' => [
                'title' => $this->seo_title ?: $this->title,
                'description' => $this->seo_description ?: $this->abstract,
            ],
            // Gated — only for entitled callers (W3-3):
            'body' => $this->entitled ? $this->body : null,
            'downloadable' => $this->entitled && $this->file_path !== null,
            'entitled' => $this->entitled,
        ];
    }
}
