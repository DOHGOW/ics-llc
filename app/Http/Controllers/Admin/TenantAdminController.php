<?php

namespace App\Http\Controllers\Admin;

use App\Authorization\Roles;
use App\Http\Controllers\Controller;
use App\Models\Core\Tenant;
use App\Models\Core\User;
use App\Services\Tenant\TenantService;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HQ tenant / franchise administration (D-079). Provisioning + lifecycle are Platform/Super-Admin
 * (HQ super-tenant) actions; every mutation is audited HIGH under TENANT_MANAGEMENT. Cross-tenant
 * listing uses the EXPLICIT super-tenant context (requirement 5).
 */
class TenantAdminController extends Controller
{
    public function __construct(
        private readonly TenantService $tenants,
        private readonly TenantContext $context,
    ) {}

    private function authorizeHq(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole([Roles::SUPER_ADMIN, Roles::PLATFORM_ADMIN]), 403);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeHq($request);

        // core_tenants is not tenant-scoped; listing is inherently cross-tenant (HQ).
        return response()->json(Tenant::query()->select(['id', 'name', 'slug', 'status', 'country_code'])->paginate(25));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeHq($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'unique:core_tenants,slug'],
            'domain' => ['nullable', 'string', 'max:255'],
            'parent_tenant_id' => ['nullable', 'integer', 'exists:core_tenants,id'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'residency_region' => ['nullable', 'string', 'max:50'],
        ]);

        $tenant = $this->tenants->create($data, $request->user());

        return response()->json(['id' => $tenant->id], 201);
    }

    public function suspend(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeHq($request);
        $this->tenants->suspend($tenant, $request->user());

        return response()->json(['message' => __('Tenant suspended.')]);
    }

    public function activate(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeHq($request);
        $this->tenants->activate($tenant, $request->user());

        return response()->json(['message' => __('Tenant activated.')]);
    }

    public function transferOwnership(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeHq($request);
        $data = $request->validate(['owner_user_id' => ['required', 'integer', 'exists:core_users,id']]);
        $this->tenants->transferOwnership($tenant, (int) $data['owner_user_id'], $request->user());

        return response()->json(['message' => __('Tenant ownership transferred.')]);
    }

    public function elevateAdmin(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeHq($request);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:core_users,id']]);
        $this->tenants->elevateAdmin($tenant, User::findOrFail($data['user_id']), $request->user());

        return response()->json(['message' => __('Franchise Admin elevated.')]);
    }

    public function changeResidency(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeHq($request);
        $data = $request->validate([
            'country_code' => ['nullable', 'string', 'size:2'],
            'residency_region' => ['nullable', 'string', 'max:50'],
        ]);
        $this->tenants->changeResidency($tenant, $data['country_code'] ?? null, $data['residency_region'] ?? null, $request->user());

        return response()->json(['message' => __('Tenant residency updated.')]);
    }
}
