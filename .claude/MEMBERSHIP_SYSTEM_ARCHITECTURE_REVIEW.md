# MEMBERSHIP SYSTEM — ARCHITECTURE REVIEW
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-04
Status: Architecture review — NO code/migrations/models/services. Design only.
Author: Lead Architect
Validates against: D-019, D-025, D-029, D-031, D-034, D-036, D-037, D-038, D-046, D-051, D-076
Inputs: ECOSYSTEM_ROADMAP_REVIEW, ACCESS_CONTROL_CONSOLIDATION_REVIEW, DATABASE_BLUEPRINT (Billing module),
TENANTSCOPE_IMPLEMENTATION_REVIEW

> **Membership is NOT a new module — it is a CONSUMER of the existing Billing substrate.** The
> blueprint already models it: `billing_plans` (with `module='membership'`, `knowledge_tier_grant`,
> `research_tier_grant`) + `billing_subscriptions`. The ContentAccessService tier-elevation hook the
> roadmap promised is **pre-modeled in the schema** (the `*_tier_grant` columns). Membership reuses
> Billing (D-031) and the content-tiering family (ContentAccessService) — **no new access mechanism.**

---

## EXECUTIVE SUMMARY

A "membership" = an active `billing_subscription` to a `billing_plan` where `module='membership'`.
The plan carries `knowledge_tier_grant` / `research_tier_grant` (already in the schema). An active
subscription **elevates the user's effective content tier** via a thin `MembershipTierResolver` that
`ContentAccessService` consults — an **elevate-only, live-status** extension at the pre-designed hook.
Membership is owner-scoped (the user's subscription), tenant-scoped (per-franchise plans, now that
TenantScope is active), and billing-driven (status flows from Paystack webhooks). **Verdict: SOUND
WITH CONDITIONS.** The one dependency to surface: the **Billing subscription substrate must exist**
(plans/subscriptions/webhook-driven status) — payment *execution* can be sandboxed/deferred, but the
subscription *lifecycle* is required for entitlement.

---

## 1. SCOPE & PURPOSE

Paid membership tiers (e.g., Free / Member / Pro / Premium) granting elevated cross-module benefits —
primarily **Knowledge/Research content tier access** — plus member pricing/benefits. It is the
realisation of the D-031 Subscription Module's `module='membership'` plans.

## 2. ENTITIES (reuse Billing — minimal/no new schema)

| Entity | Source | Role |
|---|---|---|
| billing_plans (module='membership') | EXISTING blueprint | plan definition + tier grants (knowledge_tier_grant/research_tier_grant) + price/period/gateway_plan_id |
| billing_subscriptions | EXISTING blueprint | user↔plan, status (trial/active/past_due/cancelled/expired), period, gateway_subscription_id |
| billing_invoices / payments / webhooks | EXISTING blueprint | recurring billing + status transitions (Paystack) |
| (optional) membership benefits view | thin | non-content perks (badges, member pricing) — config/JSON on the plan (`features`) |

**No new membership tables required** — Membership is a typed use of the Billing schema. (Possibly a
thin `memberships` convenience view/model over billing_subscriptions where module='membership'.)

## 3. ACCESS-CONTROL MODEL — Mandatory Mechanism Test

**Reuse an existing access-control family? YES — the CONTENT-TIERING family (ContentAccessService).**
No new mechanism. Membership does NOT introduce a parallel gate; it ELEVATES the user's tier that
ContentAccessService already evaluates.

- A `MembershipTierResolver` computes, for a user, the tier grants from their ACTIVE membership
  subscriptions (live status): `knowledgeTier`, `researchTier`.
- `ContentAccessService` (via the Hierarchical/Lateral strategies) computes the effective user tier as
  **`max(role-derived tier, membership-derived tier)`** per module. Membership only ELEVATES — never
  reduces — and is the **pre-designed hook** (the `*_tier_grant` columns + the strategy comment).

**This is the ONE controlled, additive extension of ContentAccessService** (a proven mechanism). It is
permitted because the hook was designed in (schema + comment), but it is a DECISION POINT with hard
guardrails (§Findings C-1): elevate-only, live-status, zero change to existing role/tier behaviour,
full regression tests.

## 4. MEMBERSHIP ↔ CONTENT-TIER MAPPING (D-034 / D-036)

| Grant | Elevates | Strategy |
|---|---|---|
| knowledge_tier_grant | Knowledge Center tier | LATERAL (D-036) — note tiers 3/4 are CLIENT/PARTNER (org roles); membership should elevate the MEMBER dimension (tier 2) and any membership-designated premium tiers, NOT lateral client/partner tiers |
| research_tier_grant | Research Center tier | HIERARCHICAL (D-034) — membership elevates up the stacked tiers (member → higher) per plan |

**Boundary (C-2):** membership elevates the **member/premium content** dimension; it must NOT grant
**lateral org tiers** (client/partner, which are org-role-based) nor CRM/portal access. The grant maps
to the content tiers only.

## 5. BILLING INTEGRATION (D-031) — the dependency

- Subscription lifecycle (trial→active→past_due→cancelled/expired) is **webhook-driven** (billing_webhooks,
  Paystack). Entitlement = LIVE status: tier elevation applies for `active`/`trial` ONLY.
- **Immediate revocation (C-3):** on cancel/expire/refund/past_due, the membership tier drops
  IMMEDIATELY (no stale entitlement) — because the resolver reads live subscription status (no cached
  grant, or cache invalidated on status change).
- **Dependency:** the Billing subscription substrate (plans/subscriptions/webhook status) MUST exist.
  Payment EXECUTION (live Paystack) may be sandboxed/deferred, but the subscription LIFECYCLE is required.
  → Membership wave builds (or is preceded by) the minimal Billing subscription layer.

## 6. TENANT COMPATIBILITY (D-076 — now active)

- billing_plans + billing_subscriptions carry `tenant_id` (blueprint) → **per-tenant membership plans**
  (a franchise offers its own memberships). When Billing/Membership is implemented, **add the billing
  models to the TenancyServiceProvider registry** so they are TenantScoped (C-4).
- A user's membership is within their tenant; the resolver scopes to the tenant automatically.

## 7. AUDIT ARCHITECTURE (D-046)

- **Propose `AuditCategory::MEMBERSHIP_MANAGEMENT`** (or a shared BILLING category): subscription
  created/activated/upgraded/downgraded/cancelled/expired; **refund-driven revocation = HIGH**
  (entitlement removal). Payment events audited via the Billing layer.
- Tier-elevation changes that affect access are auditable governance events.

## 8. ANALYTICS ARCHITECTURE (D-025 / W4-9)

- Own aggregator: active members, churn rate, MRR/ARR (with Billing amounts), tier distribution,
  trial→paid conversion — **per-tenant** (and HQ roll-up via super-tenant).
- No card/PII in dashboards; financial aggregates only.

## 9. AI READINESS (D-029 — seams)

- Churn prediction, upsell/cross-sell recommendation — seams; deferred. Features: usage + tier +
  tenure (non-sensitive).

## 10. FUTURE TenantScope / FRANCHISE

- Per-tenant plans + pricing + currency (franchise-local). TenantScope wraps membership; additive.
  Multi-country = per-tenant currency/pricing + (residency) — additive.

---

## MANDATORY VALIDATION

| Item | Result |
|---|---|
| Reuses an existing access family (content-tiering) | ✅ — no new mechanism |
| Reuses Billing (D-031) — no new core schema | ✅ — module='membership' plans + subscriptions |
| ContentAccessService extension = elevate-only at the PRE-DESIGNED hook | ✅ (controlled; decision C-1) |
| TenantScope compatible (per-tenant plans) | ✅ (register billing models, C-4) |
| Does NOT modify other access families (AccountScope/HasAssignmentVisibility/etc.) | ✅ |

---

## FINDINGS

| ID | Severity | Finding | Disposition |
|---|---|---|---|
| **C-1** | **CRITICAL** | ContentAccessService elevate-only extension (MembershipTierResolver) touches a proven mechanism | controlled: elevate-only, live-status, no behaviour change, full regression tests (D-080) |
| **C-2** | HIGH | Membership maps to CONTENT tiers only — never lateral org (client/partner) or CRM/portal access | enforce mapping boundary (D-082) |
| **C-3** | HIGH | Immediate revocation on cancel/expire/refund/past_due — no stale entitlement | entitlement = live subscription status; cache invalidated on status change (D-081) |
| **C-4** | HIGH | billing_plans/subscriptions must join the TenantScope registry when built | add to TenancyServiceProvider (D-082) |
| **D-1** | HIGH (dependency) | Billing subscription substrate (plans/subscriptions/webhook status) must exist for entitlement | Membership wave builds minimal Billing subscription layer; payment execution may be sandboxed |
| **M-1** | MEDIUM | MEMBERSHIP_MANAGEMENT audit; refund-revocation HIGH | D-081 |
| **M-2** | MEDIUM | Membership is owner + tenant scoped; resolver reads only the user's active subs | impl |
| **L-1** | LOW | Analytics (MRR/churn) per-tenant; AI churn/upsell deferred | seams |
| **L-2** | LOW | Non-content benefits via plan `features` JSON (badges, member pricing) | config |

### Risks
- **Cross-module contamination:** membership granting more than content tiers (e.g., portal/CRM) —
  prevented by the C-2 mapping boundary.
- **Stale entitlement:** a cancelled member retaining access — prevented by live-status entitlement (C-3).
- **Mechanism regression:** the ContentAccessService extension breaking existing tier behaviour —
  prevented by elevate-only + regression tests (C-1).
- **Billing dependency:** building Membership without the subscription substrate — sequencing (D-1).

---

## FINAL VERDICT

**SOUND WITH CONDITIONS.** Membership is a clean consumer of the existing Billing substrate and the
pre-designed ContentAccessService tier-elevation hook — no new access mechanism, no new core schema,
TenantScope-compatible. Conditions: (C-1) the ContentAccessService extension is elevate-only/live-
status/regression-proof; (C-2) it maps to content tiers ONLY; (C-3) immediate revocation; (C-4)
billing models join the TenantScope registry; (D-1) the Billing subscription substrate exists first
(payment execution may be sandboxed).

Proposed decisions to ratify on approval (NOT now):
- **D-080** — Membership = Billing module='membership' + the ContentAccessService elevate-only
  tier-grant hook (MembershipTierResolver); no new access family.
- **D-081** — MEMBERSHIP_MANAGEMENT audit; entitlement = LIVE subscription status; immediate revocation
  on cancel/expire/refund/past_due (refund-revocation HIGH).
- **D-082** — membership tier mapping (knowledge_tier_grant→Lateral D-036, research_tier_grant→
  Hierarchical D-034; content tiers ONLY); per-tenant plans; billing models join the TenantScope registry.
- **D-083** — Billing subscription substrate dependency: build the minimal plans/subscriptions/webhook
  layer with (or before) Membership; payment execution may be sandboxed/deferred.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Finance / Billing | | | | |

**Status:** Awaiting Approval. **Do NOT implement Membership until approved and conditions
C-1..C-4 / D-1 (D-080..D-083) are decided.**
