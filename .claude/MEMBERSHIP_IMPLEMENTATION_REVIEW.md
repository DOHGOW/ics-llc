# MEMBERSHIP IMPLEMENTATION REVIEW — Wave Membership
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05
Status: Implementation complete — Awaiting Approval
Author: Lead Architect
Decisions: D-031, D-034, D-036, D-038, D-046, D-051, D-076, D-080, D-081, D-082, D-083, D-087; C-1..C-4
Baseline: MEMBERSHIP_SYSTEM_ARCHITECTURE_REVIEW.md (approved); Billing substrate (ACCEPTED 2026-06-05)

---

## EXECUTIVE SUMMARY

Membership is implemented as a **consumer of the Billing substrate** — NO new module, NO new access-
control family, NO new core schema. A membership = an active `billing_subscription` to a
`module='membership'` `billing_plan` carrying `knowledge_tier_grant` / `research_tier_grant`. An active
subscription **elevates the user's effective content tier** through the **one pre-designed hook**:
`MembershipTierResolver`, now consulted by `ContentAccessService` as `max(roleTier, membershipTier)`.

The extension is **ELEVATE-ONLY** (C-1: `max()` can never reduce the role baseline; default argument 0
preserves all pre-Membership behaviour), **content-tiers-ONLY** (C-2: Knowledge/Research only; the
resolver output is consumed *solely* inside content-tier evaluation — never portal/CRM/admin), and
**LIVE-status** (C-3: every read derives from `BillingSubscription::isEntitling()`; no cached grant, so
revocation is structurally immediate). Membership rides the **same TenantScoped billing models** (C-4).

**Verdict: IMPLEMENTATION SOUND.** Constraint honoured: membership may elevate ONLY content tiers and
NEVER CRM/Portal/Marketplace-moderation/Startup-governance/account/tenant/admin authority.

---

## DELIVERABLES (CODE)

| Layer | Artifact | Scope item |
|---|---|---|
| Hook activation | `Services/Content/ContentAccessService` now injects `MembershipTierResolver`; computes `membershipTierFor()` (Knowledge/Research only, clamped) and passes it to the strategy | 1, 2 |
| Strategy (elevate-only) | `Strategies/AccessStrategyContract::canAccess(..., int $membershipTier = 0)`; `HierarchicalAccessStrategy` = `max(roleTier, membershipTier)`; `LateralAccessStrategy` = membership grants member dimension (tier 2) ONLY, never org tiers 3/4 | 2 |
| Plan management | `Http/Controllers/Membership/Admin/MembershipAdminController` (storePlan/updatePlan — module forced `membership`, tier grants validated ≤ `max_grant_tier`) | 3 |
| Entitlement projection | `Services/Membership/MembershipService` (activeMembershipsFor / isMember / entitlementFor / grantManual / revokeManual) | 4 |
| Audit | `AuditCategory::MEMBERSHIP_MANAGEMENT`; `Events/Membership/MembershipEntitlementChanged` + `AuditEventSubscriber::handleMembershipEntitlementChanged` (manual grant/removal HIGH); plan-policy changes HIGH-logged inline | 5 |
| Tenant-aware admin | `MembershipAdminController` gated to Super/Platform/**Franchise** Admin; TenantScope isolates a franchise admin to their own tenant (C-4) | 6 |
| Analytics | `Services/Membership/MembershipAnalyticsService` (active/trialing/MRR/tier-distribution/churn — per-tenant, financial aggregates only, no PII) | 7 |
| Member self-service | `Http/Controllers/Membership/MembershipController` (status projection + plan catalogue) | — |
| Routes / config | `routes/membership.php` (registered in bootstrap/app.php); `config/ics.php` → `membership.max_grant_tier` (default 3) | — |
| Tests | `tests/Feature/Membership/MembershipEntitlementTest` (validations 1–8) | 8 |

**No migration.** Membership is a typed use of the existing Billing schema (the `*_tier_grant`
columns are the pre-modeled hook). **ContentAccessService public API unchanged** (`canAccess(user,
content)` — 2 args); only an internal, defaulted strategy argument was added → zero caller impact.

---

## MANDATORY VALIDATION (1–8)

| # | Requirement | Result | Evidence |
|---|---|---|---|
| 1 | Immediate entitlement activation | ✅ | active membership → `isMember`/`entitlementFor` grant tier at once; test_1 |
| 2 | Immediate entitlement revocation | ✅ | `revokeManual` → live status drops tier instantly (no cached grant, C-3); test_2 |
| 3 | Knowledge tier elevation | ✅ | knowledge grant surfaced + applied for member-tier knowledge; test_3 |
| 4 | Research tier elevation | ✅ | non-member DENIED tier-3 research; member GRANTED via grant=3 (genuine elevation); test_4 |
| 5 | NO portal privilege escalation | ✅ | maxed knowledge grant cannot unlock CLIENT(3)/PARTNER(4) knowledge; no org role conferred; test_5 |
| 6 | NO CRM privilege escalation | ✅ | no CRM/admin role conferred; CMS module yields 0 elevation; test_6 |
| 7 | TenantScope compatibility | ✅ | billing models in the TenantScope family + tenant-stamped writes (C-4); test_7 |
| 8 | Billing integration integrity | ✅ | only {trial, active} entitle; past_due/cancelled/expired do NOT; test_8 |

---

## GUARDRAIL COMPLIANCE (C-1..C-4 / D-082)

| Guardrail | Status | How |
|---|---|---|
| C-1 elevate-only, non-destructive, regression-proof | ✅ | `max(roleTier, membershipTier)`; default arg `0` = pre-Membership behaviour byte-for-byte; public API unchanged |
| C-2 content tiers ONLY (Knowledge/Research) | ✅ | `membershipTierFor` returns 0 for CMS/guest; lateral org tiers (CLIENT/PARTNER) remain role-only; resolver consumed ONLY in content eval |
| C-3 live status, immediate revocation, no cache | ✅ | every read = `isEntitling()` on live subscription; no grant store/cache |
| C-4 tenant-aware (billing models in TenantScope) | ✅ | `BillingPlan`/`BillingSubscription` use `BelongsToTenant`; franchise-admin isolation |
| D-082 NEVER CRM/Portal/Marketplace-mod/Startup-gov/account/tenant/admin | ✅ | structural — membership flows ONLY through content-tier evaluation; grants confer no role |

---

## SELF-FLAGGED DESIGN DECISIONS

1. **M-DN-1 — where elevation is genuinely demonstrable.** Within the existing tier schemes the real
   premium-content path is **Research (Hierarchical, stacked)**: `max(roleTier, grant)` lifts a member
   up the stack (test_4 proves a non-member is denied tier-3 research and a member is granted). In
   **Knowledge (Lateral)** tiers 3/4 are ORG-ROLE checks (CLIENT/PARTNER) that require the real role;
   membership therefore confers the **member dimension (tier 2) ONLY** and **structurally cannot** grant
   org tiers (C-2 by construction, test_5). This is faithful to the architecture's §4 note and to D-082.
2. **Clamp `max_grant_tier` (default 3).** Membership grants are capped so a plan can never elevate a
   member to **internal(4)/super(5)** content — membership confers content visibility only, never near
   staff/admin content. Config-only (D-037).
3. **No new schema / no new model.** A separate `memberships` table/model was considered and rejected
   (architecture §2) — `MembershipService` is the projection layer over `billing_subscriptions`,
   avoiding a redundant join-scoped model.
4. **Manual entitlement overlay event.** `MembershipEntitlementChanged` carries manual admin grant/
   removal into `MEMBERSHIP_MANAGEMENT` (HIGH) so comp/migration actions are not lost in the billing
   stream; payment-driven lifecycle stays audited under `BILLING_MANAGEMENT`.
5. **Subscribe reuses Billing.** Membership adds NO parallel payment path — paid membership subscribe/
   cancel are the existing `/api/v1/billing/*` endpoints (C-3 pre-payment non-entitlement preserved).

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| No new access-control family; reuses content-tiering (ContentAccessService) | ✅ |
| No new core schema; module='membership' plans + subscriptions | ✅ |
| ContentAccessService extension elevate-only / regression-safe / public API unchanged | ✅ |
| Membership maps to content tiers ONLY (never org/CRM/portal/admin) | ✅ |
| Entitlement = live status; immediate revocation; no cached grants | ✅ |
| Billing models in TenantScope; tenant-aware administration | ✅ |
| MEMBERSHIP_MANAGEMENT audit; manual grant/removal + policy = HIGH | ✅ |
| Validations 1–8 authored as GREEN-CI gate | ⚠ carried (run under bootstrap — D-049) |

---

## REVIEW VERDICT

**IMPLEMENTATION SOUND.** Membership is a clean, controlled consumer of the accepted Billing substrate:
one elevate-only, live-status, content-tiers-only extension at the pre-designed hook; no new mechanism,
no new schema, tenant-aware, fully audited. Validations 1–8 are the mandatory GREEN-CI release gate
(run under the project bootstrap per D-049 before production membership).

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Finance / Billing | | | | |

**Status:** Awaiting Approval.
