<?php

namespace App\Http\Resources\Community;

use App\Models\Community\CommunityProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * W4b-1 public-only projection. Exposes the base public identity fields + the matching
 * extension's whitelisted publicFields() ONLY. It NEVER exposes link pointers
 * (partner_id/instructor_id/author_id/startup_id) and NEVER joins into the linked module —
 * so no CRM/portal/training/research internals can leak. is_verified is the trust signal.
 */
class CommunityProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CommunityProfile $profile */
        $profile = $this->resource;
        $extension = $profile->extension();

        return [
            'id' => $profile->id,
            'profile_type' => $profile->profile_type,
            'display_name' => $profile->display_name,
            'tagline' => $profile->tagline,
            'bio' => $profile->bio,
            'avatar_path' => $profile->avatar_path,
            'website_url' => $profile->website_url,
            'location' => array_filter(['country' => $profile->location_country, 'city' => $profile->location_city]),
            'links' => array_filter(['linkedin' => $profile->linkedin_url, 'twitter' => $profile->twitter_url]),
            'is_verified' => (bool) $profile->is_verified,   // trust signal (W4b-2)
            'view_count' => $profile->view_count,
            'follower_count' => $profile->follower_count,
            // CTI extension — whitelisted public fields ONLY (W4b-1); no link pointers:
            'details' => $extension !== null ? $extension->publicFields() : [],
        ];
    }
}
