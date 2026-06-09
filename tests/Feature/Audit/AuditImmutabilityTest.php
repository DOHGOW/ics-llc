<?php

namespace Tests\Feature\Audit;

use App\Audit\AuditCategory;
use App\Authorization\Roles;
use App\Models\Core\AuditLog;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Audit append-only + high-sensitivity (D-046 / SECURITY+RBAC specs AU-*). */
class AuditImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_cannot_be_updated(): void
    {
        $log = $this->log();
        $this->expectException(\LogicException::class);
        $log->update(['action' => 'TAMPER']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = $this->log();
        $this->expectException(\LogicException::class);
        $log->delete();
    }

    public function test_super_admin_action_is_high_sensitivity(): void
    {
        $entry = app(AuditService::class)->log('TEST', 'test', AuditCategory::GENERAL, 1, Roles::SUPER_ADMIN);
        $this->assertSame('high', $entry->sensitivity);
    }

    public function test_sensitive_category_is_high_sensitivity(): void
    {
        $entry = app(AuditService::class)->log('TEST', 'test', AuditCategory::ROLE_ASSIGNMENT, 1, 'Student / Trainee');
        $this->assertSame('high', $entry->sensitivity);
    }

    public function test_normal_action_is_normal_sensitivity(): void
    {
        $entry = app(AuditService::class)->log('TEST', 'test', AuditCategory::AUTHENTICATION, 1, 'Student / Trainee');
        $this->assertSame('normal', $entry->sensitivity);
    }

    private function log(): AuditLog
    {
        return AuditLog::create([
            'action' => 'TEST', 'module' => 'test',
            'category' => 'general', 'sensitivity' => 'normal',
            'created_at' => now(),
        ]);
    }
}
