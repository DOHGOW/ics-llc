<?php

namespace App\Authorization;

/**
 * Enumerated reason codes for a Super Admin role-escalation request (D-044/D-045).
 * Single-purpose; extensible without becoming a workflow engine.
 */
final class EscalationReasonCode
{
    public const NEW_LEADERSHIP = 'new_leadership';

    public const STAFFING_CHANGE = 'staffing_change';

    public const EMERGENCY_ACCESS = 'emergency_access';

    public const ROLE_CORRECTION = 'role_correction';

    /** @var array<int,string> */
    public const ALL = [
        self::NEW_LEADERSHIP,
        self::STAFFING_CHANGE,
        self::EMERGENCY_ACCESS,
        self::ROLE_CORRECTION,
    ];

    public static function isValid(string $code): bool
    {
        return in_array($code, self::ALL, true);
    }
}
