<?php

namespace App\Services\Content\Strategies;

use App\Content\ContentAccessible;
use App\Models\Core\User;

interface AccessStrategyContract
{
    /**
     * @param  int  $membershipTier  ELEVATE-ONLY content tier granted by an active membership (D-080/C-1).
     *                               Default 0 = no membership (pre-Membership behaviour preserved, regression-safe).
     */
    public function canAccess(?User $user, ContentAccessible $content, int $membershipTier = 0): bool;
}
