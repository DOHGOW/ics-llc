<?php

namespace App\Models\Concerns;

/**
 * Publication-traceability authorship (D-052). Stamps created_by on create and
 * updated_by on create/update from the acting user. published_by is set by the
 * publish flow (CmsService).
 */
trait HasAuthorship
{
    public static function bootHasAuthorship(): void
    {
        static::creating(function ($model) {
            $userId = auth()->id() ?? optional(optional(request())->user())->id;
            if ($userId !== null) {
                if (empty($model->created_by)) {
                    $model->created_by = $userId;
                }
                $model->updated_by = $userId;
            }
        });

        static::updating(function ($model) {
            $userId = auth()->id() ?? optional(optional(request())->user())->id;
            if ($userId !== null) {
                $model->updated_by = $userId;
            }
        });
    }
}
