<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant / franchise (core_tenants) — D-004/D-019/D-079. The root of the tenant > account > user
 * hierarchy (D-050 #4). Phase 1 = single root/default tenant; multi-tenant activation is config-
 * only (D-037). It IS the tenant, so it is NOT itself TenantScope-ed. D-079 adds parent_tenant_id
 * (regional hierarchy), country, residency, owner.
 */
class Tenant extends Model
{
    use SoftDeletes;

    protected $table = 'core_tenants';

    public const STATUSES = ['active', 'suspended', 'trial'];

    protected $fillable = [
        'name', 'slug', 'domain', 'status', 'settings',
        'parent_tenant_id', 'country_code', 'residency_region', 'owner_user_id', // D-079
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_tenant_id');
    }
}
