<?php

namespace App\Services\Community;

use App\Events\Community\ConsultantProfileCreated;
use App\Events\Community\ProfileStatusChanged;
use App\Events\Community\ProfileVerified;
use App\Models\Community\CommunityProfile;
use App\Models\Core\User;
use App\Models\Partner\PartnerProfile;
use App\Models\Research\ResearchAuthor;
use App\Models\Training\Instructor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Community profile lifecycle (Wave 4b / D-035).
 *
 * - CTI: base + exactly ONE extension matching profile_type, created transactionally (W4b-4).
 * - Link integrity (W4b-2): a cross-module link id (partner_id/instructor_id/author_id) is
 *   accepted ONLY if the linked record belongs to the same user; otherwise rejected. Link
 *   pointers are stored internal-only and are NEVER serialised (W4b-1).
 * - Consultant creation fires the ONE-WAY CRM lead capture (W4b-3).
 */
class CommunityProfileService
{
    public function createProfile(User $user, array $base, array $extension): CommunityProfile
    {
        if (CommunityProfile::where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['profile' => __('You already have a community profile.')]);
        }

        $type = $base['profile_type'];
        $this->assertLinksOwned($user, $type, $extension);

        return DB::transaction(function () use ($user, $base, $extension, $type): CommunityProfile {
            $profile = CommunityProfile::create($base + ['user_id' => $user->id]);

            $extensionClass = CommunityProfile::EXTENSIONS[$type];
            $extensionClass::create($extension + ['profile_id' => $profile->id]);

            if ($type === 'consultant') {
                event(new ConsultantProfileCreated($profile)); // W4b-3 one-way → CRM
            }

            return $profile;
        });
    }

    /** W4b-2: reject a link the user does not own (anti-impersonation). */
    private function assertLinksOwned(User $user, string $type, array $extension): void
    {
        $checks = [
            'partner' => ['partner_id', PartnerProfile::class],
            'trainer' => ['instructor_id', Instructor::class],
            'researcher' => ['author_id', ResearchAuthor::class],
        ];

        if (! isset($checks[$type])) {
            return; // founder/startup/consultant have no ownership-verifiable link in Phase 1
        }

        [$field, $model] = $checks[$type];
        $id = $extension[$field] ?? null;
        if ($id === null) {
            return;
        }

        $owned = $model::where('id', $id)->where('user_id', $user->id)->exists();
        if (! $owned) {
            throw ValidationException::withMessages([
                $field => __('You may only link to a :type record you own (or request ICS verification).', ['type' => $type]),
            ]);
        }
    }

    public function verify(CommunityProfile $profile, User $actor): CommunityProfile
    {
        $profile->forceFill(['is_verified' => true, 'verified_at' => now(), 'verified_by' => $actor->id])->save();
        event(new ProfileVerified($profile, $actor->id, $actor->getRoleNames()->first()));

        return $profile;
    }

    public function changeStatus(CommunityProfile $profile, string $toStatus, User $actor): CommunityProfile
    {
        $from = $profile->status;
        if ($from === $toStatus) {
            return $profile;
        }

        $profile->forceFill(['status' => $toStatus])->save();
        event(new ProfileStatusChanged($profile, $from, $toStatus, $actor->id, $actor->getRoleNames()->first()));

        return $profile;
    }
}
