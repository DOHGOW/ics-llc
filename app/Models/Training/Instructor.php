<?php

namespace App\Models\Training;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Instructor profile (training_instructors). Staff-approved (audited, TRAINING_MANAGEMENT). */
class Instructor extends Model
{
    protected $table = 'training_instructors';

    public const STATUSES = ['pending', 'active', 'inactive'];

    protected $fillable = [
        'tenant_id', 'user_id', 'bio', 'specializations', 'status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['specializations' => 'array', 'approved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
