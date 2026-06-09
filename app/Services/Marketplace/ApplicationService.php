<?php

namespace App\Services\Marketplace;

use App\Events\Marketplace\ApplicationStatusChanged;
use App\Models\Core\User;
use App\Models\Marketplace\MarketplaceApplication;
use App\Models\Marketplace\MarketplaceListing;
use Illuminate\Validation\ValidationException;

/**
 * Marketplace applications (Wave 4c / D-060). Duplicate prevention is enforced by the DB
 * unique (listing, applicant); this service surfaces a clean 422 on conflict. Status changes
 * (by poster/ICS) fire ApplicationStatusChanged (audited).
 */
class ApplicationService
{
    public function apply(MarketplaceListing $listing, User $applicant, array $data): MarketplaceApplication
    {
        if (MarketplaceApplication::where('listing_id', $listing->id)->where('applicant_id', $applicant->id)->exists()) {
            throw ValidationException::withMessages(['listing' => __('You have already applied to this opportunity.')]);
        }

        $application = MarketplaceApplication::create([
            'tenant_id' => $listing->tenant_id,
            'listing_id' => $listing->id,
            'applicant_id' => $applicant->id,
            'cover_letter' => $data['cover_letter'] ?? null,
            'attachments' => $data['attachments'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $listing->increment('application_count'); // analytics counter (not audited)

        return $application;
    }

    public function changeStatus(MarketplaceApplication $application, string $toStatus, User $actor): MarketplaceApplication
    {
        $from = $application->status;
        if ($from === $toStatus) {
            return $application;
        }

        $application->forceFill([
            'status' => $toStatus,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
        ])->save();

        event(new ApplicationStatusChanged($application, $from, $toStatus, $actor->id, $actor->getRoleNames()->first()));

        return $application;
    }
}
