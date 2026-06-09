<?php

namespace App\Http\Resources\Startup;

use App\Models\Startup\Startup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public startup projection (C-1 / M-1). Exposes ONLY public identity fields. NEVER includes
 * ownership/cap-table, milestones, mentor notes, team ownership, or founder PII beyond display.
 * Investment-sensitive data is Investment Network (5d) data-room only.
 */
class StartupPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Startup $s */
        $s = $this->resource;

        return [
            'id' => $s->id,
            'name' => $s->name,
            'slug' => $s->slug,
            'description' => $s->description,
            'industry' => $s->industry,
            'lifecycle_stage' => $s->lifecycle_stage, // journey is public-safe
            'stage' => $s->stage,                       // product maturity
            'country_code' => $s->country_code,
            'team_size' => $s->team_size,
            'logo_path' => $s->logo_path,
            'website' => $s->website,
            'is_verified' => (bool) $s->is_verified,   // trust signal only
            // EXCLUDED (C-1/M-1): ownership_percent, milestones, mentor notes, founder PII
        ];
    }
}
