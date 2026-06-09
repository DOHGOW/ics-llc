<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Project milestone (client_project_milestones). CHILD of client_projects — NOT org-owned
 * (no BelongsToAccount). Parent-isolated (W2-1): only ever reached through its
 * AccountScope-protected project; never queried independently by an org user.
 */
class ProjectMilestone extends Model
{
    protected $table = 'client_project_milestones';

    public const STATUSES = ['pending', 'in_progress', 'completed', 'missed'];

    protected $fillable = [
        'project_id', 'title', 'description', 'due_date', 'completed_at', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'project_id');
    }
}
