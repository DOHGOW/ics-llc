<?php

namespace App\Authorization;

/**
 * Canonical role names + privilege levels (D-021 / D-040 / D-044).
 *
 * 13 seeded Spatie roles. "Guest" (USER_ROLE_MATRIX R-14) is the ABSENCE of
 * authentication — it has no role record. LEVELS power the escalation guard
 * (no actor may grant a role at/above its own level — D-044).
 */
final class Roles
{
    public const SUPER_ADMIN = 'Platform Super Admin';

    public const PLATFORM_ADMIN = 'Platform Admin';

    public const FRANCHISE_ADMIN = 'Franchise Admin'; // D-079 — tenant-scoped admin

    public const ICS_CRM = 'ICS Staff — CRM';

    public const ICS_TRAINING = 'ICS Staff — Training';

    public const ICS_CONTENT = 'ICS Staff — Content';

    public const CLIENT_ADMIN = 'Client Admin';

    public const PARTNER_ADMIN = 'Partner Admin';

    public const GOV_REP = 'Government Agency Representative';

    public const VENDOR = 'Vendor';

    public const STARTUP_FOUNDER = 'Startup Founder';

    public const STARTUP_MEMBER = 'Startup Team Member';

    public const TRAINER = 'Trainer / Instructor';

    public const STUDENT = 'Student / Trainee';

    /** @var array<int,string> All seeded roles (excludes unauthenticated Guest). */
    public const ALL = [
        self::SUPER_ADMIN,
        self::PLATFORM_ADMIN,
        self::FRANCHISE_ADMIN,
        self::ICS_CRM,
        self::ICS_TRAINING,
        self::ICS_CONTENT,
        self::CLIENT_ADMIN,
        self::PARTNER_ADMIN,
        self::GOV_REP,
        self::VENDOR,
        self::STARTUP_FOUNDER,
        self::STARTUP_MEMBER,
        self::TRAINER,
        self::STUDENT,
    ];

    /** Roles whose accounts MUST have MFA enabled (D-039). */
    public const ADMIN_ROLES = [
        self::SUPER_ADMIN,
        self::PLATFORM_ADMIN,
        self::FRANCHISE_ADMIN,
    ];

    /** ICS-internal roles — not organisation-bound; bypass AccountScope (D-050/W1a). */
    public const ICS_INTERNAL = [
        self::SUPER_ADMIN,
        self::PLATFORM_ADMIN,
        self::ICS_CRM,
        self::ICS_TRAINING,
        self::ICS_CONTENT,
    ];

    /** Privilege levels — higher = more privilege (escalation guard, D-044). */
    public const LEVELS = [
        self::SUPER_ADMIN => 100,
        self::PLATFORM_ADMIN => 90,
        self::FRANCHISE_ADMIN => 80,
        self::ICS_CRM => 70,
        self::ICS_TRAINING => 70,
        self::ICS_CONTENT => 70,
        self::CLIENT_ADMIN => 50,
        self::PARTNER_ADMIN => 50,
        self::GOV_REP => 50,
        self::VENDOR => 40,
        self::STARTUP_FOUNDER => 30,
        self::TRAINER => 30,
        self::STARTUP_MEMBER => 20,
        self::STUDENT => 10,
    ];

    public static function level(string $role): int
    {
        return self::LEVELS[$role] ?? 0;
    }
}
