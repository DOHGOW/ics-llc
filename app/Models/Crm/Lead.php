<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasAssignmentVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CRM Lead (crm_leads). Lead pipeline: new → contacted → qualified → proposal →
 * negotiation → closed_won/closed_lost. Assignment-scoped (D-053). AI columns deferred
 * (D-029). Stage transitions go through CrmService (audited, D-054).
 */
class Lead extends Model
{
    use HasAssignmentVisibility;
    use SoftDeletes;

    protected $table = 'crm_leads';

    public const STAGES = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];

    protected $fillable = [
        'tenant_id', 'contact_id', 'account_id', 'source', 'source_detail', 'title',
        'value', 'currency', 'stage', 'probability', 'expected_close_date',
        'assigned_to', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'expected_close_date' => 'date',
            'ai_qualification_at' => 'datetime',
        ];
    }

    public function crmReadAllPermission(): string
    {
        return 'crm.leads.read.all';
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
