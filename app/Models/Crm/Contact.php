<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasAssignmentVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CRM Contact (crm_contacts). `account_id` is a SUBJECT pointer to crm_accounts, not an
 * ownership key (D-053). Assignment-scoped.
 */
class Contact extends Model
{
    use HasAssignmentVisibility;
    use SoftDeletes;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'tenant_id', 'account_id', 'first_name', 'last_name', 'email', 'phone',
        'job_title', 'status', 'assigned_to', 'created_by',
    ];

    public function crmReadAllPermission(): string
    {
        return 'crm.contacts.read.all';
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
