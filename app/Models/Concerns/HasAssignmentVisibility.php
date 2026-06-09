<?php

namespace App\Models\Concerns;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * CRM assignment-based visibility (D-053 / W1d-1, W1d-4).
 *
 * This is the THIRD, orthogonal isolation control — it is NOT AccountScope and NOT
 * ContentAccessService, and it NEVER filters on `account_id` (in CRM that column is a
 * subject pointer, not an ownership key). It filters on assignment:
 *
 *   - user holds `<readAllPermission>`  → sees the whole pipeline (no filter)
 *   - otherwise                          → sees only rows assigned to / created by them
 *
 * Implementing models declare `crmReadAllPermission()`. Controllers call
 * `Model::visibleTo($user)` on every list/read path. Super Admin bypasses via
 * Gate::before at the policy layer; this scope is an explicit query-level guard.
 *
 * NOTE: deliberately NOT a global scope — CRM has legitimate cross-assignment admin
 * and analytics paths; visibility is applied explicitly where user-facing.
 */
trait HasAssignmentVisibility
{
    abstract public function crmReadAllPermission(): string;

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0'); // no auth → nothing
        }

        if ($user->can($this->crmReadAllPermission())) {
            return $query; // full-pipeline visibility
        }

        $table = $this->getTable();

        return $query->where(function (Builder $sub) use ($table, $user) {
            $sub->where($table.'.assigned_to', $user->id)
                ->orWhere($table.'.created_by', $user->id);
        });
    }

    /** True when this user may see this specific record (mirror of the scope). */
    public function visibleToUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->can($this->crmReadAllPermission())) {
            return true;
        }

        return (int) ($this->assigned_to ?? 0) === (int) $user->id
            || (int) ($this->created_by ?? 0) === (int) $user->id;
    }
}
