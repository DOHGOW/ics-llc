<?php

namespace App\Http\Controllers\Profile;

use App\Events\Core\AccountDeletionRequested;
use App\Events\Core\DataExportRequested;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * NDPA/GDPR data-subject rights (D-006 / E-CORE-009 / E-CORE-010 / T-4.7).
 *
 * Export: returns the authenticated subject's own data. SECRETS ARE NEVER
 * EXPORTED (password hash, mfa_secret, recovery codes, tokens) — they are in the
 * model's $hidden set and excluded here.
 *
 * Delete (right to erasure): revokes access immediately, pseudonymises PII, and
 * soft-deletes the account. The audit trail (hashes, no FK) and anonymised
 * consent ledger are preserved as proof-of-process.
 */
class DataPrivacyController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'profile' => $user->only([
                'id', 'name', 'email', 'locale', 'timezone', 'status', 'created_at',
            ]),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'consents' => DB::table('core_consent_logs')->where('user_id', $user->id)->get(),
            'notification_preferences' => DB::table('notify_preferences')->where('user_id', $user->id)->get(),
        ];

        event(new DataExportRequested($user, $request->ip()));

        return response()->json($payload)
            ->header('Content-Disposition', 'attachment; filename="ics-my-data.json"');
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        event(new AccountDeletionRequested($user, $request->ip())); // audit before anonymisation

        // 1. Revoke access immediately.
        $user->tokens()->delete();

        // 2. Pseudonymise PII (erasure by anonymisation — keeps audit integrity).
        $user->forceFill([
            'name' => 'Deleted User',
            'email' => 'deleted_'.$user->id.'@anonymised.invalid',
            'last_login_ip' => null,
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
            'status' => 'deactivated',
        ])->save();

        // 3. Soft-delete the account.
        $user->delete();

        return response()->json([
            'message' => __('Your account has been closed and your personal data anonymised.'),
        ]);
    }
}
