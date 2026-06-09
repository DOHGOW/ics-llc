<?php

namespace App\Services\Content;

use App\Models\Content\ContentEngagementEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Records content engagement (view/download/citation) into the unified table
 * (D-038/D-051). The integration seam modules call from their read/download paths;
 * cached counters on the content row are updated separately by the module.
 */
class EngagementRecorder
{
    public function record(Model $content, string $eventType, ?Request $request = null): ContentEngagementEvent
    {
        $request ??= request();
        $user = $request?->user();

        return ContentEngagementEvent::create([
            'tenant_id' => $content->tenant_id ?? null,
            'content_type' => $content::class,
            'content_id' => $content->getKey(),
            'event_type' => $eventType,
            'user_id' => $user?->id,
            'session_id' => $request?->hasSession() ? $request->session()->getId() : null,
            'ip_address' => $request?->ip(),
            'referrer_url' => $request?->headers->get('referer'),
            'created_at' => now(),
        ]);
    }
}
