<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\RoleEscalationApproval;
use App\Models\Core\User;
use App\Services\Auth\RoleAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Role assignment + four-eyes Super Admin escalation (Task 7 / D-044 / D-045).
 * Guarded by the `platform.roles.manage` permission and the RoleAssignmentService
 * (level guard, four-eyes, last-Super-Admin protection, token revocation, audit).
 * DomainExceptions from the service surface as 422.
 */
class RoleManagementController extends Controller
{
    public function __construct(private readonly RoleAssignmentService $roles) {}

    public function assign(Request $request, User $user): JsonResponse
    {
        $this->guard($request);
        $data = $request->validate(['role' => ['required', 'string']]);

        $this->roles->assign($request->user(), $user, $data['role'], (string) $request->ip());

        return response()->json(['message' => __('Role assigned.')]);
    }

    public function revoke(Request $request, User $user): JsonResponse
    {
        $this->guard($request);
        $data = $request->validate(['role' => ['required', 'string']]);

        $this->roles->revokeRole($request->user(), $user, $data['role'], (string) $request->ip());

        return response()->json(['message' => __('Role revoked.')]);
    }

    public function escalationRequest(Request $request, User $user): JsonResponse
    {
        $this->guard($request);
        $data = $request->validate(['reason_code' => ['required', 'string']]);

        $req = $this->roles->requestSuperAdmin($request->user(), $user, $data['reason_code'], (string) $request->ip());

        return response()->json(['message' => __('Escalation requested.'), 'request_id' => $req->id], 201);
    }

    public function escalationApprove(Request $request, RoleEscalationApproval $approval): JsonResponse
    {
        $this->guard($request);
        $this->roles->approveSuperAdmin($request->user(), $approval, (string) $request->ip());

        return response()->json(['message' => __('Escalation approved; Super Admin granted.')]);
    }

    public function escalationReject(Request $request, RoleEscalationApproval $approval): JsonResponse
    {
        $this->guard($request);
        $this->roles->reject($request->user(), $approval, (string) $request->ip());

        return response()->json(['message' => __('Escalation rejected.')]);
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user()->can('platform.roles.manage'), 403);
    }
}
