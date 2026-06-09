# WAVE 1A IMPLEMENTATION REVIEW — ORGANISATION OWNERSHIP FRAMEWORK
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Review Complete — Awaiting Approval
Reviewer: Chief Enterprise Architect
Decision References: D-004, D-021, D-037, D-044, D-050

---

## EXECUTIVE SUMMARY

Wave 1a delivers the organisation ownership framework — the sole Phase 1 cross-
organisation isolation control (TenantScope deferred). It comprises the
`core_users.account_id` column (D-050), the `AccountScope` global scope (Layer 1),
the `BelongsToAccount` trait, the `OrgOwnedPolicy` base (Layer 2), and a working
isolation test harness proving enumeration / direct-access / NULL / staff-bypass
behaviour. No Content Engine, CMS, or CRM was implemented. Verdict: **PASS — proceed
to Wave 1b (Content Engine) after approval.**

---

## 1. FILES CREATED / CHANGED

| File | Purpose |
|---|---|
| migration `…add_account_id_to_core_users_table` | account_id (nullable) + index; FK deferred to Wave 1d (D-050) |
| app/Authorization/Roles.php (edit) | `ICS_INTERNAL` role set (AccountScope bypass) |
| app/Authorization/Scopes/AccountScope.php | Layer-1 global query scope |
| app/Models/Concerns/BelongsToAccount.php | marks org-owned models; scope + create-stamping |
| app/Policies/OrgOwnedPolicy.php | Layer-2 base policy (`accessible()`) |
| tests/Support/IsoFixture.php + IsoFixturePolicy.php | harness fixtures |
| tests/Concerns/AssertsOrgIsolation.php | reusable isolation helpers for module tests |
| tests/Feature/Isolation/AccountIsolationTest.php | enumeration/direct/null/staff tests + create-stamping |

---

## 2. ISOLATION ARCHITECTURE REVIEW

Two independent layers, both required (defence in depth):
- **Layer 1 — AccountScope:** org users' queries auto-filter to their `account_id`;
  prevents enumeration/listing of other orgs' rows.
- **Layer 2 — OrgOwnedPolicy:** per-record `accessible()` (staff bypass OR same
  account); prevents direct-id access to another org's record.
- **Create-stamping:** org users' new rows are stamped with their own `account_id`
  — they cannot forge a row into another organisation.
- **Boundary (W1-3):** this applies ONLY to org-owned models (BelongsToAccount).
  Content remains tier-scoped (ContentAccessService) — not touched here.

## 3. AccountScope REVIEW

- Resolution is explicit and safe: console (no scope), no-auth (no scope), ICS staff
  (bypass), else filter by `account_id`.
- Cross-guard user resolution: `auth()->user() ?? request()->user()` — correct for
  session (web) AND Sanctum (api), and for `actingAs()` in tests.
- Escape hatch: `Model::acrossAccounts()` for permission-gated, audited cross-org
  admin/reporting paths — explicit, not implicit.
- ICS_INTERNAL bypass is permission-gated downstream (staff still need the relevant
  permission; Super Admin via Gate::before).

## 4. POLICY REVIEW

- `OrgOwnedPolicy::accessible()` = internal-staff OR same-account; module policies
  combine it with a permission per ability (default-deny preserved).
- Demonstrated by `IsoFixturePolicy` (uses a real permission + accessible()).
- Composes with the existing `BasePolicy` helpers and `Gate::before` (Super Admin).

## 5. SECURITY REVIEW

| Control | Status |
|---|---|
| Enumeration isolation (Layer 1) | ✅ tested |
| Direct-access isolation (Layer 2) | ✅ tested |
| Create-stamping (no cross-org forge) | ✅ tested |
| Staff bypass (permission-gated) | ✅ tested |
| NULL-account user sees no other org rows | ✅ tested |
| Console/system contexts not over-filtered | ✅ (runningInConsole guard) |
| Escape hatch explicit + auditable | ✅ acrossAccounts() |

**W1-1 enforcement:** every future org-owned model MUST use BelongsToAccount +
OrgOwnedPolicy + an isolation test (using AssertsOrgIsolation). Recommend a review
checklist + a larastan/CI rule flagging any table with `account_id` whose model lacks
the trait/policy.

## 6. TENANTSCOPE COMPATIBILITY REVIEW (Phase 3)

- Hierarchy preserved: **tenant > account > user**. `account_id` nests under the
  existing `tenant_id`.
- In Phase 3, TenantScope adds `WHERE tenant_id = ?`; AccountScope continues to add
  `WHERE account_id = ?` — they **compose** with no rework. AccountScope is modelled
  exactly like the future TenantScope (same trait/scope pattern), so the second scope
  is additive.
- account_id has no FK yet (added Wave 1d); nullable + indexed; backward compatible.

## 7. TESTING REVIEW

- The harness creates a real org-owned fixture (via BelongsToAccount) and proves all
  four required cases without depending on a business module.
- `AssertsOrgIsolation` is reusable: Wave 2 portal tests reuse it to satisfy W1-1.
- Tests run on the engine-parity CI job (RefreshDatabase). Note: the fixture table is
  created in setUp; production org-owned tables arrive with their modules.

---

## FINDINGS

| ID | Finding | Severity |
|---|---|---|
| W1a-1 | account_id FK to crm_accounts deferred to Wave 1d (by design, D-050) | INFO |
| W1a-2 | Enforce W1-1 via CI/larastan rule (table with account_id ⇒ trait+policy+test) | MEDIUM (process) |
| W1a-3 | `acrossAccounts()` bypass must be permission-gated + audited at each call site | MEDIUM (usage) |

---

## CONFIRMATIONS

| Requirement | Result |
|---|---|
| AccountScope ≠ ContentAccessService (separate) | ✅ |
| BelongsToAccount + OrgOwnedPolicy + isolation tests provided | ✅ |
| Cross-org denial tested: enumeration, direct, NULL, staff bypass | ✅ |
| Content Engine / CMS / CRM NOT implemented | ✅ |

---

## REVIEW VERDICT

**PASS.** The organisation ownership framework is implemented, layered, and proven by
the isolation harness; TenantScope-compatible; content boundary preserved. Cleared to
proceed to **Wave 1b (Unified Content Engine)** after approval.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security Officer | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do not begin Content Engine / CMS / CRM until approved.**
