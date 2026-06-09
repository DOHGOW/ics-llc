<?php

namespace Tests\Feature\Authorization;

use App\Authorization\EscalationReasonCode;
use App\Authorization\Roles;
use App\Models\Core\User;
use App\Services\Auth\RoleAssignmentService;
use App\Services\Auth\UserLifecycleService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Escalation guard + four-eyes + last-Super-Admin (D-044/D-045/R-3). */
class EscalationGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, RoleSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_super_admin_cannot_be_directly_assigned(): void
    {
        $svc = app(RoleAssignmentService::class);
        $this->expectException(\DomainException::class);
        $svc->assign($this->withRole(Roles::SUPER_ADMIN), $this->user(), Roles::SUPER_ADMIN, '127.0.0.1');
    }

    public function test_cannot_grant_at_or_above_own_level(): void
    {
        $svc = app(RoleAssignmentService::class);
        $this->expectException(\DomainException::class);
        $svc->assign($this->withRole(Roles::PLATFORM_ADMIN), $this->user(), Roles::PLATFORM_ADMIN, '127.0.0.1');
    }

    public function test_four_eyes_requires_a_different_super_admin(): void
    {
        $svc = app(RoleAssignmentService::class);
        $a = $this->withRole(Roles::SUPER_ADMIN);
        $b = $this->withRole(Roles::SUPER_ADMIN);
        $target = $this->user();

        $request = $svc->requestSuperAdmin($a, $target, EscalationReasonCode::STAFFING_CHANGE, '127.0.0.1');

        try {
            $svc->approveSuperAdmin($a, $request, '127.0.0.1'); // same person
            $this->fail('Self-approval should be rejected.');
        } catch (\DomainException $e) {
            // expected
        }

        $svc->approveSuperAdmin($b, $request->fresh(), '127.0.0.1');
        $this->assertTrue($target->fresh()->hasRole(Roles::SUPER_ADMIN));
    }

    public function test_last_super_admin_cannot_be_deactivated(): void
    {
        $life = app(UserLifecycleService::class);
        $only = $this->withRole(Roles::SUPER_ADMIN);
        $admin = $this->withRole(Roles::PLATFORM_ADMIN);

        $this->expectException(\DomainException::class);
        $life->deactivate($admin, $only, 'offboarding', '127.0.0.1');
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
