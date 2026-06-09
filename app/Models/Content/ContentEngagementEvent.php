<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only content engagement event (D-038/D-051). Write via EngagementRecorder;
 * read for analytics. Mutation is forbidden.
 */
class ContentEngagementEvent extends Model
{
    protected $table = 'content_engagement_events';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'content_type', 'content_id', 'event_type',
        'user_id', 'session_id', 'ip_address', 'country_code', 'referrer_url', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('Engagement events are append-only.');
    }

    public function delete(): bool
    {
        throw new \LogicException('Engagement events are append-only.');
    }
}
