# MEMBERSHIP ENTITLEMENT VALIDATION — Wave Membership
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05
Status: Validation model — the entitlement correctness argument (D-080..D-082, C-1..C-3)
Author: Lead Architect

> The single correctness claim: **membership entitlement is a pure, live derivation of subscription
> state, applied ELEVATE-ONLY to CONTENT tiers ONLY.** There is no stored grant, no cache, and no path
> from membership to any non-content privilege. Everything below proves that claim structurally.

---

## 1. THE ENTITLEMENT FUNCTION

```
membershipTier(user, content) =
    if user is null OR content.module ∉ {knowledge, research}      → 0          (D-082)
    else  min( resolver.grantsFor(user)[content.module],  max_grant_tier )      (clamp, C-2)

resolver.grantsFor(user) =
    over the user's billing_subscriptions where status ∈ {trial, active}
        AND isEntitling()            (period not lapsed — LIVE, C-3)
        AND plan.module == 'membership'
    → max(plan.knowledge_tier_grant), max(plan.research_tier_grant)

effectiveAccess:
    Hierarchical (Research):  max(roleTier, membershipTier) >= content.tier
    Lateral (Knowledge):      membership satisfies tier 2 ONLY; tiers 3/4/5 stay role-only
```

**Derived, not stored.** No `entitlements` table, no cached tier. The grant exists *only* as a function
of live subscription rows — so a status change *is* the entitlement change.

---

## 2. IMMEDIACY (C-3) — activation & revocation

| Transition | Subscription status | `isEntitling()` | membershipTier | Effect |
|---|---|---|---|---|
| Subscribe (free/trial) | trial | true | grant | entitled immediately (val. 1) |
| Charge success | active | true | grant | entitled |
| Cancel (user/admin) | cancelled | false | 0 | **revoked immediately** (val. 2) |
| Expire / period lapse | expired (or period past) | false | 0 | **revoked immediately** |
| Charge failure | past_due | false | 0 | **revoked immediately** |
| Refund / chargeback | cancelled | false | 0 | **revoked immediately** |

There is no window in which a non-entitling subscription confers a tier — the resolver filters on live
status on every read. **No cache to invalidate** ⇒ revocation cannot lag.

---

## 3. ELEVATE-ONLY (C-1)

- Hierarchical: `max(roleTier, membershipTier)` — membership can only **raise** the effective tier;
  it can never lower the role baseline. With `membershipTier = 0` (the default), behaviour is identical
  to pre-Membership.
- Lateral: membership adds the member dimension (tier 2) only; the org-role branches are untouched.
- The public `ContentAccessService::canAccess` signature is unchanged; every existing caller compiles
  and behaves identically when no membership is present.

**Regression property:** a member's access ⊇ the same user's access without membership (monotonic).

---

## 4. CONTENT-TIERS-ONLY (C-2 / D-082) — the non-escalation proof

The resolver output is consumed at **exactly one site**: `ContentAccessService::membershipTierFor`,
inside content-tier evaluation. It is referenced nowhere else in the codebase.

| Forbidden target | Why membership cannot reach it |
|---|---|
| CRM access | CRM uses `HasAssignmentVisibility` (D-053) — never consults the resolver |
| Client / Partner Portal | Portals use `OrgOwnedPolicy`/`AccountScope` (D-055) — never consults the resolver |
| Marketplace moderation | listing-status model (D-060) — never consults the resolver |
| Startup governance | StartupAccessService / ProgramParticipationService — never consults the resolver |
| Account / Tenant ownership | AccountScope / TenantScope — never consults the resolver |
| Admin authority | role/permission engine — membership confers NO role |
| Lateral org content (CLIENT/PARTNER) | tiers 3/4 require the real org role; membership grants tier 2 only |
| Internal / Super content | `max_grant_tier` clamp (default 3) — membership never reaches tier 4/5 |

Validations 5 and 6 assert the negative directly: a maxed membership grants **no** CLIENT/PARTNER
knowledge and confers **no** CRM/Portal/Admin role; CMS yields **0** elevation.

---

## 5. LIVE BILLING INTEGRATION (D-083) — integrity

Entitlement is gated on `BillingSubscription::isEntitling()` (status ∈ {trial, active} ∧ period not
lapsed). Validation 8 enumerates every status: `past_due`, `cancelled`, `expired` → **not** a member;
`trial`, `active` → member. Paid membership purchase reuses the Billing flow, where a paid-no-trial
subscription is **non-entitling (past_due)** until `charge.success` — so membership cannot precede
payment (the Billing fail-safe carries into Membership).

---

## 6. TENANT-AWARENESS (C-4)

`BillingPlan` / `BillingSubscription` use `BelongsToTenant` → `TenantScope` global scope + tenant
create-stamp. Membership reuses Billing's tenancy wholesale (no new mechanism); per-tenant plans and
per-tenant analytics follow. Franchise admins are isolated to their own tenant by the scope.

---

## 7. RESIDUAL / CARRIED

- Validations 1–8 are **authored**; they run GREEN under the project bootstrap (D-049 / R-012 / R-013)
  before production membership — same carried posture as the Billing A–G gate.
- Genuine premium elevation is demonstrable in **Research** (stacked tiers); **Knowledge** confers the
  member dimension only (org tiers reserved) — design note M-DN-1, by construction faithful to D-082.

**Conclusion:** entitlement is correct by construction — live-derived, elevate-only, content-only,
immediately revocable, tenant-aware. No stored grant exists to leak, lag, or escalate.
