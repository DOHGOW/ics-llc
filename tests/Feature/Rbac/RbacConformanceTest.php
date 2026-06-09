<?php

namespace Tests\Feature\Rbac;

use App\Authorization\Roles;
use App\Models\Core\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** RBAC conformance (RBAC_CONFORMANCE_TEST_SPEC: PM-*, UR-*, DD-*). */
class RbacConformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, RoleSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_all_roles_are_seeded(): void
    {
        // 14 roles since D-079 added Franchise Admin (FT-1); Roles::ALL is the source of truth.
        $this->assertSame(count(Roles::ALL), Role::count());
        foreach (Roles::ALL as $role) {
            $this->assertTrue(Role::where('name', $role)->exists(), "Missing role: {$role}");
        }
    }

    public function test_permission_catalogue_is_seeded(): void
    {
        $this->assertSame(count(PermissionSeeder::catalogue()), Permission::count());
    }

    public function test_gov_rep_has_no_tier4_knowledge(): void
    {
        $user = $this->userWithRole(Roles::GOV_REP);
        $this->assertFalse($user->can('knowledge.tier4.read')); // D-044/EP-2
        $this->assertTrue($user->can('knowledge.tier2.read'));
    }

    public function test_platform_admin_excludes_super_only(): void
    {
        $user = $this->userWithRole(Roles::PLATFORM_ADMIN);
        $this->assertFalse($user->can('platform.config.update'));
        $this->assertFalse($user->can('platform.tenants.manage'));
        $this->assertTrue($user->can('platform.users.create'));
    }

    public function test_super_admin_passes_any_ability(): void
    {
        $user = $this->userWithRole(Roles::SUPER_ADMIN);
        $this->assertTrue($user->can('platform.config.update'));
        $this->assertTrue($user->can('any.unknown.ability')); // Gate::before
    }

    public function test_roleless_user_is_default_denied(): void
    {
        $this->assertFalse($this->user()->can('platform.users.read.all'));
    }

    private function user(string $status = 'active'): User
    {
        return User::create([
            'name' => 'T', 'email' => uniqid('u', true).'@x.test',
            'password' => bcrypt('Password!2345'), 'status' => $status,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = $this->user();
        $user->assignRole($role);

        return $user->fresh();
    }
}
