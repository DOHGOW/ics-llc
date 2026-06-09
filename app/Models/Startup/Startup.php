<?php

namespace App\Models\Startup;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Startup (startup_profiles). FOUNDER-OWNED (founder_id) — NOT account-owned (H-3): no
 * AccountScope/BelongsToAccount. Access is the participation family (StartupAccessService,
 * D-061). `lifecycle_stage` is the authoritative journey (D-063). Cap-table/ownership is gated
 * (C-1) and excluded from public projections.
 */
class Startup extends Model
{
    use SoftDeletes;

    protected $table = 'startup_profiles';

    public const LIFECYCLE = ['idea', 'registered', 'validation', 'incubation', 'acceleration', 'investment_ready', 'alumni'];

    protected $fillable = [
        'tenant_id', 'founder_id', 'name', 'slug', 'description', 'industry', 'lifecycle_stage',
        'stage', 'status', 'founding_year', 'team_size', 'website', 'logo_path', 'country_code',
        'is_verified', 'verified_at', 'verified_by',
    ];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'verified_at' => 'datetime'];
    }

    public function founder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'startup_id');
    }

    public function activeFounders(): HasMany
    {
        return $this->teamMembers()->where('is_founder', true)->where('status', 'active');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class, 'startup_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class, 'startup_id');
    }
}
