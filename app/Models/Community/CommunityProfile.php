<?php

namespace App\Models\Community;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Community profile base (D-035 CTI). VISIBILITY + OWNER scoped (D-057) — NOT
 * ContentAccessible, NOT AccountScope/ContentAccessService/HasAssignmentVisibility/
 * TrainingAccessService. One per user. Cross-module links live on the extensions and are
 * surfaced public-only (W4b-1) via CommunityProfileResource.
 */
class CommunityProfile extends Model
{
    use SoftDeletes;

    protected $table = 'community_profiles';

    public const TYPES = ['founder', 'startup', 'consultant', 'trainer', 'partner', 'researcher'];

    /** Extension table per type (CTI). */
    public const EXTENSIONS = [
        'founder' => FounderProfile::class,
        'startup' => StartupProfile::class,
        'consultant' => ConsultantProfile::class,
        'trainer' => TrainerProfile::class,
        'partner' => PartnerCommunityProfile::class,
        'researcher' => ResearcherProfile::class,
    ];

    protected $fillable = [
        'tenant_id', 'user_id', 'profile_type', 'display_name', 'tagline', 'bio', 'avatar_path',
        'cover_image_path', 'website_url', 'location_country', 'location_city', 'linkedin_url',
        'twitter_url', 'visibility', 'is_verified', 'verified_at', 'verified_by', 'status',
    ];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'verified_at' => 'datetime'];
    }

    /**
     * W4b-5 visibility scope: active + (public OR authenticated viewer). Owner and ICS staff
     * (read.all) bypass and see everything.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user !== null && $user->can('community.profile.read.all')) {
            return $query; // staff
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where(function (Builder $pub) use ($user) {
                $pub->where('status', 'active')
                    ->where(fn (Builder $v) => $user !== null
                        ? $v->whereIn('visibility', ['public', 'authenticated'])
                        : $v->where('visibility', 'public'));
            });
            if ($user !== null) {
                $q->orWhere('user_id', $user->id); // owner sees own (any status)
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function founder(): HasOne
    {
        return $this->hasOne(FounderProfile::class, 'profile_id');
    }

    public function startup(): HasOne
    {
        return $this->hasOne(StartupProfile::class, 'profile_id');
    }

    public function consultant(): HasOne
    {
        return $this->hasOne(ConsultantProfile::class, 'profile_id');
    }

    public function trainer(): HasOne
    {
        return $this->hasOne(TrainerProfile::class, 'profile_id');
    }

    public function partner(): HasOne
    {
        return $this->hasOne(PartnerCommunityProfile::class, 'profile_id');
    }

    public function researcher(): HasOne
    {
        return $this->hasOne(ResearcherProfile::class, 'profile_id');
    }

    /** The single extension matching profile_type (CTI). */
    public function extension(): ?Model
    {
        return $this->{$this->profile_type} ?? null;
    }
}
