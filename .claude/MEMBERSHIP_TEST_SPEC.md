# MEMBERSHIP TEST SPEC — Wave Membership
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05
Status: Authored — mandatory GREEN-CI release gate (run under bootstrap, D-049)
Scope: D-087 mandatory validations 1–8
Suite: `tests/Feature/Membership/MembershipEntitlementTest`

---

## TEST ENVIRONMENT

- PHPUnit + `RefreshDatabase` (MySQL under the project bootstrap).
- Content is exercised via a **non-DB `ContentAccessible` double** (anonymous class) so the access
  DECISION is tested in isolation from content-model schema (module/strategy/tier/published only).
- Membership = `billing_subscription` (status) + `billing_plan` (module='membership', `*_tier_grant`).
- `ContentAccessService`, `MembershipService`, `MembershipTierResolver` resolved from the container
  (so the elevate-only wiring is exercised end-to-end).

---

## VALIDATION MATRIX (1–8)

| # | Test | Asserts | Pass condition |
|---|---|---|---|
| 1 | `test_1_immediate_activation` | active membership entitles at once | `isMember` true; `entitlementFor.research_tier == grant` |
| 2 | `test_2_immediate_revocation` | revoke drops entitlement instantly (C-3) | after `revokeManual`: `isMember` false; tier null |
| 3 | `test_3_knowledge_tier_elevation` | knowledge grant surfaced + applied (member dim.) | `entitlementFor.knowledge_tier == grant`; member-tier knowledge accessible |
| 4 | `test_4_research_tier_elevation` | genuine research elevation | non-member DENIED tier-3 research; member (grant 3) GRANTED |
| 5 | `test_5_no_portal_escalation` | C-2 boundary — no org/portal access | knowledge grant=4 cannot unlock CLIENT(3)/PARTNER(4); no org role |
| 6 | `test_6_no_crm_escalation` | C-2 boundary — no CRM/admin | no CRM/admin role conferred; CMS yields 0 elevation |
| 7 | `test_7_tenant_scope_compatibility` | C-4 — tenant family + stamping | `TenantScope` in billing-model global scopes; write stamped current tenant |
| 8 | `test_8_billing_integration_integrity` | only live statuses entitle | {trial,active} entitle; {past_due,cancelled,expired} do NOT |

---

## REGRESSION COVERAGE (C-1)

The membership extension MUST NOT change any pre-Membership content decision:

- **Default-argument guarantee.** `AccessStrategyContract::canAccess(..., int $membershipTier = 0)`;
  with `0` the strategies behave byte-for-byte as before (`max(roleTier, 0) == roleTier`; lateral
  membership branch is skipped). Any caller that does not pass a membership tier is unaffected.
- **Public API unchanged.** `ContentAccessService::canAccess(user, content)` signature is identical;
  the existing Knowledge/Research/CMS controller tests exercise it unchanged.
- **CMS exclusion.** `membershipTierFor` returns 0 for `module='cms'` and for guests → CMS and public
  access paths are provably untouched (test_6 asserts the 0).
- **Recommended:** run the existing `tests/Feature/Content/*`, `Knowledge/*`, `Research/*` suites in the
  same CI run to confirm no tier regression (the elevate-only `max()` cannot reduce access).

---

## EXIT CRITERIA

All of 1–8 GREEN **plus** the existing content suites GREEN, under the project bootstrap, before
production membership is enabled (D-049 / R-012 / R-013). Until then the suite stands as the authored
release gate (parity with the Billing A–G gate).
