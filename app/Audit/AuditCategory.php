<?php

namespace App\Audit;

/**
 * Audit categories (D-046). The HIGH_SENSITIVITY set is always recorded as
 * sensitivity = high; in addition, ANY action by a Super Admin is high-sensitivity.
 */
final class AuditCategory
{
    public const GENERAL = 'general';

    public const AUTHENTICATION = 'authentication';

    public const USER_MANAGEMENT = 'user_management';

    public const ROLE_ASSIGNMENT = 'role_assignment';

    public const PERMISSION_CHANGE = 'permission_change';

    public const ESCALATION_REQUEST = 'escalation_request';

    public const ESCALATION_APPROVAL = 'escalation_approval';

    public const SECURITY_CONFIG = 'security_config';

    public const DATA_PRIVACY = 'data_privacy';

    public const CONTENT_MANAGEMENT = 'content_management'; // W1c-4 (CMS publish/archive)

    public const CRM_MANAGEMENT = 'crm_management';         // D-054 (CRM lifecycle/assignment/stage)

    public const PORTAL_MANAGEMENT = 'portal_management';   // D-056 (Client/Partner portal lifecycle)

    public const TRAINING_MANAGEMENT = 'training_management';     // D-058 (enrollment/cert/grading)

    public const COMMUNITY_MANAGEMENT = 'community_management';   // D-058 (profile verify/suspend)

    public const MARKETPLACE_MANAGEMENT = 'marketplace_management'; // D-058 (listing review/application)

    public const STARTUP_MANAGEMENT = 'startup_management';         // D-062 (ownership/verify/lifecycle)

    public const PROGRAM_MANAGEMENT = 'program_management';         // D-066 (incubator/accelerator programs)

    public const TENANT_MANAGEMENT = 'tenant_management';           // FT (tenant lifecycle/franchise governance)

    public const BILLING_MANAGEMENT = 'billing_management';         // D-085 (subscription/refund/override)

    public const MEMBERSHIP_MANAGEMENT = 'membership_management';   // D-081 (membership plan/manual entitlement)

    /** Categories always treated as high-sensitivity (Task 6 requirement). */
    public const HIGH_SENSITIVITY = [
        self::USER_MANAGEMENT,
        self::ROLE_ASSIGNMENT,
        self::PERMISSION_CHANGE,
        self::ESCALATION_REQUEST,
        self::ESCALATION_APPROVAL,
        self::SECURITY_CONFIG,
    ];
}
