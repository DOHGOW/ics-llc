<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasAssignmentVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CRM Opportunity (crm_opportunities). Spawned from a qualified lead (`lead_id`).
 * Pipeline: qualification → proposal → negotiation → closed_won/closed_lost.
 * Assignment-scoped (D-053). Stage transitions go through CrmService (audited, D-054).
 */
class Opportunity extends Model
{
    use HasAssignmentVisibility;
    use SoftDeletes;

    protected $table = 'crm_opportunities';

    public const STAGES = ['qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];

    protected $fillable = [
        'tenant_id', 'account_id', 'lead_id', 'title', 'value', 'currency', 'stage',
        'close_date', 'probability', 'description', 'assigned_to', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'close_date' => 'date',
        ];
    }

    public function crmReadAllPermission(): string
    {
        return 'crm.opportunities.read.all';
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
