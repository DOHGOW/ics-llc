<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Deliverable (client_deliverables). CHILD of client_projects — parent-isolated (W2-1).
 * Files served only via policy-gated/streamed delivery (W2-5). Drafts are hidden from the
 * client until submitted/approved (status filter on client reads).
 */
class Deliverable extends Model
{
    use SoftDeletes;

    protected $table = 'client_deliverables';

    public const STATUSES = ['draft', 'submitted', 'approved', 'rejected'];

    /** Statuses a client may see (drafts hidden, W2-4/W2-5 spirit). */
    public const CLIENT_VISIBLE_STATUSES = ['submitted', 'approved', 'rejected'];

    protected $fillable = [
        'project_id', 'milestone_id', 'title', 'description', 'file_path', 'version',
        'status', 'submitted_at', 'approved_at', 'approved_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'project_id');
    }
}
