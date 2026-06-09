<?php

namespace App\Models\Concerns;

use App\Authorization\Scopes\AccountScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marks a model as organisation-owned (Wave 1a / D-050).
 *
 * - Applies AccountScope globally (org users see only their account's rows).
 * - On create, stamps account_id from the acting org user (so org users cannot
 *   create rows for another organisation). ICS staff specify account_id explicitly.
 *
 * MANDATORY for every org-owned model (W1-1): combine with an OrgOwnedPolicy and an
 * isolation test. Content models (CMS/Knowledge/Research) DO NOT use this — they are
 * tier-scoped via ContentAccessService (W1-3).
 */
trait BelongsToAccount
{
    public static function bootBelongsToAccount(): void
    {
        static::addGlobalScope(new AccountScope);

        static::creating(function ($model) {
            if ($model->account_id === null) {
                $user = auth()->user() ?? optional(request())->user();

                if ($user !== null && ($user->account_id ?? null) !== null) {
                    $model->account_id = $user->account_id;
                }
            }
        });
    }

    /** Permission-gated, audited cross-organisation access (admin/reporting). */
    public static function acrossAccounts(): Builder
    {
        return static::query()->withoutGlobalScope(AccountScope::class);
    }
}
