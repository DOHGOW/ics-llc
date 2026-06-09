<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces MFA enrolment for administrator accounts (D-039 / Role Matrix).
 * Platform Super Admin and Platform Admin must have MFA enabled; until they do,
 * access to protected routes is refused with guidance to enrol.
 */
class RequireMfaForAdmins
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->hasAnyRole(['Platform Super Admin', 'Platform Admin'])
            && ! $user->mfa_enabled) {
            return response()->json([
                'message' => __('MFA enrolment is required for administrator accounts.'),
                'mfa_enrolment_required' => true,
            ], 403);
        }

        return $next($request);
    }
}
