<?php

namespace App\Models\Concerns;

use App\Authorization\Scopes\TenantScope;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marks a model as TENANT-SCOPED (D-076). ADDITIVE — sits alongside any existing scope/trait
 * (e.g. BelongsToAccount) without modifying it; TenantScope composes above AccountScope.
 *
 * Applies TenantScope and stamps tenant_id on create from the current TenantContext, falling back
 * to the configured default (root) tenant in single-tenant mode (D-077). Models may either `use`
 * this trait OR be registered centrally in TenancyServiceProvider (the registry is the single
 * auditable list of tenant-scoped models).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if ($model->tenant_id === null) {
                $model->tenant_id = app(TenantContext::class)->id()
                    ?? (int) config('ics.tenancy.default_tenant_id', 1);
            }
        });
    }

    /** Explicit, permission-gated, audited cross-tenant query (HQ/reporting). */
    public static function acrossTenants(): Builder
    {
        return static::query()->withoutGlobalScope(TenantScope::class);
    }
}
