<?php

namespace App\Models\Client;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Client project (client_projects). ORG-OWNED — account_id is the ownership key
 * (D-050); isolated by AccountScope (Layer 1) + ClientProjectPolicy (Layer 2).
 * Children (milestones, deliverables) are parent-isolated (W2-1).
 */
class ClientProject extends Model
{
    use BelongsToAccount;
    use SoftDeletes;

    protected $table = 'client_projects';

    public const STATUSES = ['planning', 'active', 'on_hold', 'completed', 'cancelled'];

    protected $fillable = [
        'tenant_id', 'account_id', 'contract_id', 'title', 'description', 'status',
        'start_date', 'target_end_date', 'actual_end_date', 'project_manager_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_end_date' => 'date',
            'actual_end_date' => 'date',
        ];
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class, 'project_id');
    }
}
