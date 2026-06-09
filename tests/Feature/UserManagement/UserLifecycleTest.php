<?php

namespace Tests\Feature\UserManagement;

use App\Authorization\Roles;
use App\Models\Core\User;
use App\Services\Auth\RegistrationService;
use App\Services\Auth\RoleAssignmentService;
use App\Services\Auth\UserLifecycleService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lifecycle controls (D-047: R-2/R-4/R-5 + self-action + approval). */
class UserLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, RoleSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_approval_moves_pending_to_active(): void
    {
        $life = app(UserLifecycleService::class);
        $admin = $this->withRole(Roles::PLATFORM_ADMIN);
        $pending = $this->user('pending');

        $life->approve($admin, $pending, '127.0.0.1');

        $this->assertSame('active', $pending->fresh()->status);
    }

    public function test_user_cannot_suspend_self(): void
    {
        $life = app(UserLifecycleService::class);
        $admin = $this->withRole(Roles::PLATFORM_ADMIN);

        $this->expectException(\DomainException::class);
        $life->suspend($admin, $admin, 'reason', '127.0.0.1');
    }

    public function test_reactivation_strips_super_admin(): void
    {
        $life = app(UserLifecycleService::class);
        $admin = $this->withRole(Roles::SUPER_ADMIN);          // active super admin
        $target = $this->withRole(Roles::SUPER_ADMIN);
        $target->update(['status' => 'suspended']);            // not counted as active

        $life->reactivate($admin, $target, '127.0.0.1');

        $this->assertFalse($target->fresh()->hasRole(Roles::SUPER_ADMIN)); // R-4
        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_self_registration_rejects_non_whitelisted_role(): void
    {
        $svc = app(RegistrationService::class);
        $this->expectException(\DomainException::class);
        $svc->register(
            ['name' => 'X', 'email' => 'x@y.test', 'password' => 'Password!2345'],
            Roles::PLATFORM_ADMIN,
            '127.0.0.1'
        );
    }

    public function test_role_change_revokes_tokens(): void // R-2
    {
        $svc = app(RoleAssignmentService::class);
        $actor = $this->withRole(Roles::SUPER_ADMIN);
        $target = $this->user();
        $target->createToken('api');
        $this->assertSame(1, $target->tokens()->count());

        $svc->assign($actor, $target, Roles::STUDENT, '127.0.0.1');

        $this->assertSame(0, $target->fresh()->tokens()->count());
    }

    private function user(string $status = 'active'): User
    {
        return User::create([
            'name' => 'T', 'email' => uniqid('u', true).'@x.test',
            'password' => bcrypt('Password!2345'), 'status' => $status,
        ]);
    }

    private function withRole(string $role): User
    {
        $u = $this->user();
        $u->assignRole($role);

        return $u->fresh();
    }
}
