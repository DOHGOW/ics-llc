<?php

/*
|--------------------------------------------------------------------------
| ICS Platform Control Surface  (D-037)
|--------------------------------------------------------------------------
| All ICS-specific runtime switches. Read ONLY via config('ics.*').
| Deferred-to-VPS behaviours are gated by these flags so the code always
| ships and the .env value decides. No driver names live here — those are
| resolved by Laravel's own config (queue/cache/session/filesystem/mail).
|
| Shared hosting profile: every flag below resolves to false.
| VPS profile: flipped true via .env only — no code change (D-037).
*/

return [

    // Runtime feature flags — gate deferred behaviours (D-037 guarantee #3)
    'flags' => [
        'warehouse_etl_enabled' => env('ICS_WAREHOUSE_ETL_ENABLED', false),
        'heavy_jobs' => env('ICS_HEAVY_JOBS', false),
        'ai_high_volume' => env('ICS_AI_HIGH_VOLUME', false),
        'community_scaling' => env('ICS_COMMUNITY_SCALING', false),
    ],

    // AI guardrails — consumed when the AI sprints land (D-026, COST-01)
    'ai' => [
        'daily_request_cap' => (int) env('ICS_AI_DAILY_REQUEST_CAP', 1000),
        'user_hourly_cap' => (int) env('ICS_AI_USER_HOURLY_CAP', 20),
        'guest_session_cap' => (int) env('ICS_AI_GUEST_SESSION_CAP', 5),
    ],

    // Edge / security posture (D-039)
    'edge' => [
        'cloudflare_enabled' => env('CLOUDFLARE_ENABLED', false),
    ],

    // Security alerting (R-7) — comma-separated recipient emails for high-sensitivity
    // lifecycle alerts (role grants/revokes, suspensions, deactivations, escalations).
    'security' => [
        'alert_recipients' => array_filter(array_map(
            'trim',
            explode(',', (string) env('ICS_SECURITY_ALERT_RECIPIENTS', ''))
        )),
    ],

    // Content search driver (D-038): 'fulltext' (Phase 1 MySQL) → 'scout' (Phase 2
    // Meilisearch). Config-only swap (D-037).
    'search' => [
        'driver' => env('ICS_SEARCH_DRIVER', 'fulltext'),
    ],

    // CMS media handling (D-024 / Wave 1c). Disk is the Laravel filesystem disk
    // ('public' on shared hosting → 's3'/object storage Phase 3, config-only D-037).
    'media' => [
        'disk' => env('ICS_MEDIA_DISK', 'public'),
        'path' => env('ICS_MEDIA_PATH', 'media'),
        'max_kb' => (int) env('ICS_MEDIA_MAX_KB', 10240),
    ],

    // Opportunity Marketplace trust controls (D-060 / Wave 4c).
    'marketplace' => [
        // Open reports on a published listing that auto-hide it (→ pending_review).
        'report_autohide_threshold' => (int) env('ICS_MKT_REPORT_AUTOHIDE', 3),
    ],

    // Multi-tenancy / Franchise (D-004/D-019/D-037/D-076/D-077). Config-only activation:
    // disabled → single-tenant (TenantScope is a no-op; all rows belong to the default root
    // tenant); enabled → multi-tenant TenantScope filters by the resolved tenant (fail-closed).
    'tenancy' => [
        'enabled' => (bool) env('ICS_TENANCY_ENABLED', false),
        'default_tenant_id' => (int) env('ICS_TENANCY_DEFAULT_TENANT_ID', 1),
        // 'user' (by authenticated user's tenant_id) or 'domain' (by core_tenants.domain).
        'resolver' => env('ICS_TENANCY_RESOLVER', 'user'),
    ],

    // Billing / subscriptions (D-031/D-084). Gateway driver is config-only (D-037) — secrets via
    // .env only (never in tenant settings JSON). Sandbox mode for non-production (D-083).
    'billing' => [
        'gateway' => env('ICS_BILLING_GATEWAY', 'paystack'),
        'currency' => env('ICS_BILLING_CURRENCY', 'NGN'),
        'sandbox' => (bool) env('ICS_BILLING_SANDBOX', true),
        'paystack' => [
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        ],
    ],

    // Membership (D-080..D-083 / D-087). Membership is a CONSUMER of Billing — an active
    // billing_subscription to a module='membership' plan ELEVATES the user's effective content tier
    // (Knowledge/Research ONLY — C-2). Entitlement is LIVE-status derived (no cached grant — C-3).
    // max_grant_tier CAPS how high a membership may elevate content tiers: membership never reaches
    // internal(4)/super(5) content, and (in the lateral Knowledge scheme) never the org tiers (C-2).
    'membership' => [
        'max_grant_tier' => (int) env('ICS_MEMBERSHIP_MAX_GRANT_TIER', 3),
    ],

];
