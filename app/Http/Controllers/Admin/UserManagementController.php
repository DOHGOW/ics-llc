<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Services\Auth\PasswordRules;
use App\Services\Auth\RoleAssignmentService;
use App\Services\Auth\UserLifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Admin user management (Task 7). Every action is authorised by UserPolicy; the
 * lifecycle transitions run through UserLifecycleService (guards + events + audit).
 * No user may act on their own account for suspend/deactivate/delete (UserPolicy).
 */
class UserManagementController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UserLifecycleService $lifecycle,
        private readonly RoleAssignmentService $roles,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json(
            User::query()->select(['id', 'name', 'email', 'status', 'created_at'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:core_users,email'],
            'password' => ['required', 'confirmed', PasswordRules::default()],
            'role' => ['required', 'string'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);

        // Role assignment is escalation-guarded (Super Admin requires four-eyes).
        $this->roles->assign($request->user(), $user, $data['role'], (string) $request->ip());

        return response()->json(['id' => $user->id, 'status' => $user->status], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'status', 'locale', 'created_at']),
            'roles' => $user->getRoleNames(),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'locale' => ['sometimes', 'string', 'max:10'],
        ]);

        $user->update($data);

        return response()->json(['message' => __('User updated.')]);
    }

    public function approve(Request $request, User $user): JsonResponse
    {
        $this->authorize('approve', $user);
        $this->lifecycle->approve($request->user(), $user, (string) $request->ip());

        return response()->json(['message' => __('Account approved.')]);
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        $this->authorize('suspend', $user);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->lifecycle->suspend($request->user(), $user, $data['reason'], (string) $request->ip());

        return response()->json(['message' => __('Account suspended.')]);
    }

    public function reactivate(Request $request, User $user): JsonResponse
    {
        $this->authorize('reactivate', $user);
        $this->lifecycle->reactivate($request->user(), $user, (string) $request->ip());

        return response()->json(['message' => __('Account reactivated.')]);
    }

    public function deactivate(Request $request, User $user): JsonResponse
    {
        $this->authorize('deactivate', $user);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->lifecycle->deactivate($request->user(), $user, $data['reason'], (string) $request->ip());

        return response()->json(['message' => __('Account deactivated.')]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $this->lifecycle->delete($request->user(), $user, (string) $request->ip());

        return response()->json(['message' => __('Account deleted and anonymised.')]);
    }
}
