<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasAssignmentVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CRM Activity (crm_activities) — polymorphic engagement timeline against a
 * Lead/Opportunity/Account. NOTES are an activity `type='note'` (W1d-2 — there is no
 * separate crm_notes table). Assignment-scoped (D-053).
 */
class Activity extends Model
{
    use HasAssignmentVisibility;
    use SoftDeletes;

    protected $table = 'crm_activities';

    public const TYPES = ['call', 'email', 'meeting', 'note', 'task', 'demo'];

    protected $fillable = [
        'tenant_id', 'subject_type', 'subject_id', 'type', 'title', 'description',
        'due_at', 'completed_at', 'created_by', 'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function crmReadAllPermission(): string
    {
        return 'crm.activities.read.all';
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
