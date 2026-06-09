<?php

namespace App\Models\Core;

use App\Notifications\Core\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Core User (core_users).
 *
 * Decisions: D-021 (auth/RBAC subject), D-004 (tenant_id), D-006 (PII),
 * D-039 (password/MFA), D-041 (password reset), D-042/D-043 (encrypted MFA
 * secret + hashed recovery codes).
 *
 * Security:
 *  - `password`           -> hashed cast (bcrypt, cost set by hashing config).
 *  - `mfa_secret`         -> encrypted cast (AF-1/F-5) — NEVER stored plaintext.
 *  - `mfa_recovery_codes` -> array cast of HASHED, single-use codes (AF-3/D-043).
 *  - Secret/credential fields are $hidden and excluded from serialization/export.
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'core_users';

    /** Bind the factory explicitly (model lives in App\Models\Core, factory in Database\Factories). */
    protected static string $factory = UserFactory::class;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'locale',
        'timezone',
        'status',
    ];

    /**
     * Never serialized / never leaves the application (also excluded from the
     * GDPR data export — D-006).
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'mfa_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'mfa_secret' => 'encrypted',   // AF-1 / F-5
            'mfa_enabled' => 'boolean',
            'mfa_recovery_codes' => 'array',       // AF-3 (entries are bcrypt hashes)
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * AF-2: password reset uses an immediate (non-queued) notification routed
     * through the failover mailer.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
