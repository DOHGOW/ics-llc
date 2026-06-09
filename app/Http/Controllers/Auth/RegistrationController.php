<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordRules;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public self-registration (Task 7 / R-5). The requested role MUST be on the
 * self-registration whitelist (no staff/admin/org-admin self-grant). Approval-
 * required roles are created 'pending' and cannot authenticate until approved.
 */
class RegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $registration) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:core_users,email'],
            'password' => ['required', 'confirmed', PasswordRules::default()],
            'role' => ['required', 'string', Rule::in(RegistrationService::whitelist())],
            'locale' => ['sometimes', 'string', 'max:10'],
        ]);

        $user = $this->registration->register($data, $data['role'], (string) $request->ip());

        return response()->json([
            'message' => $user->status === 'pending'
                ? __('Registration received. Your account is awaiting approval.')
                : __('Registration successful. Please verify your email.'),
            'status' => $user->status,
        ], 201);
    }
}
