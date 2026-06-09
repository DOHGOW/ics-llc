<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasAssignmentVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CRM Account (crm_accounts) — the organisation entity itself; ICS master data.
 * Internal-only, assignment-scoped (D-053). NOT org-owned (no AccountScope/BelongsToAccount).
 */
class Account extends Model
{
    use HasAssignmentVisibility;
    use SoftDeletes;

    protected $table = 'crm_accounts';

    protected $fillable = [
        'tenant_id', 'name', 'type', 'industry', 'website', 'country_code',
        'phone', 'address', 'status', 'assigned_to', 'created_by',
    ];

    public function crmReadAllPermission(): string
    {
        return 'crm.accounts.read.all';
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'account_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'account_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'account_id');
    }
}
