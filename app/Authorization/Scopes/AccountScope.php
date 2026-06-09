<?php

namespace App\Authorization\Scopes;

use App\Authorization\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Organisation isolation scope (Wave 1a / D-050). Applied to org-owned models via the
 * BelongsToAccount trait. Auto-filters queries to the current org user's account_id —
 * Layer 1 of the two-layer isolation control (the policy layer is Layer 2).
 *
 * Resolution (D-088 sibling — the blanket runningInConsole() bypass was REMOVED; the no-user guard
 * below already preserves system context, so migrate/seed/queue without an authenticated actor are
 * unaffected, while async work that restores the actor is correctly isolated):
 *   - no authenticated user        → no filter (system/maintenance context: migrate/seed/queue)
 *   - ICS-internal staff/SuperAdmin → no filter (cross-org per permission/policy)
 *   - otherwise                     → WHERE account_id = <user.account_id>
 *
 * Org-owned models never hold NULL account_id rows in practice, so a non-staff user
 * with a NULL account (an individual) matches nothing — and the policy layer denies
 * regardless. Use Model::acrossAccounts() for permission-gated cross-org admin paths.
 */
class AccountScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user() ?? optional(request())->user();

        if ($user === null) {
            return; // system/maintenance context (no actor) — migrate/seed/queue safe
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(Roles::ICS_INTERNAL)) {
            return;
        }

        $builder->where($model->getTable().'.account_id', $user->account_id);
    }
}
