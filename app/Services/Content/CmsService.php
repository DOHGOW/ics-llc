<?php

namespace App\Services\Content;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;

/**
 * CMS orchestration. Publish stamps `published_by` (D-052) then triggers the engine
 * lifecycle (which sets status/published_at and fires ContentPublished → audited
 * under content_management, D-046/W1c-4).
 */
class CmsService
{
    public function publish(Model $content, User $actor): void
    {
        $content->forceFill(['published_by' => $actor->id])->save();
        $content->publish(); // HasContentLifecycle: status/published_at + ContentPublished
    }

    public function submitForReview(Model $content): void
    {
        $content->submitForReview();
    }

    public function archive(Model $content): void
    {
        $content->archive(); // fires ContentArchived (audited)
    }
}
