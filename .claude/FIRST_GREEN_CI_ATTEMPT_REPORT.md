# FIRST GREEN CI ATTEMPT REPORT — Phase 5
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05 → executed
Status: EXECUTED — actual results only. PASS / FAIL / NOT EXECUTED per stage.
Author: Lead Architect
Runtime: PHP 8.2.12 (XAMPP), Composer 2.9.5, sqlite (phpunit) — **not** the 8.3 + MySQL 8 CI target

## RESULT: ⚠ NOT YET GREEN — but the suite now RUNS: 47 passed / 5 failed

The pipeline reached PHPUnit for the first time ever. After fixing real bugs surfaced by execution, **47
tests pass and 5 fail**, and the **5 failures share ONE architectural root cause** (scope isolation under
console context). Pint and Larastan fail on style/static-analysis (auto-fixable / baseline). Engine-parity
was NOT EXECUTED (no MySQL 8 locally).

## CI STAGE RESULTS (ci.yml order)

| # | Stage | Result | Evidence |
|---|---|---|---|
| 1 | `composer validate --strict` | ✅ PASS | "./composer.json is valid" |
| 2 | `composer audit` | ⚠ 1 advisory | CVE-2026-48019 (report-only in ci.yml `|| true`); REQUIRES DECISION |
| 3 | driver gate (`check-hardcoded-drivers.sh`) | ✅ PASS | "no hardcoded infrastructure drivers (D-037)" |
| 4 | Pint (`pint --test`) | ❌ FAIL | ~36 files need formatting (cosmetic; auto-fix via `pint`) |
| 5 | Larastan (`phpstan analyse`) | ❌ FAIL | **113 errors**, almost all `property.notFound` on dynamic Eloquent attributes (no runtime impact; needs `@property` annotations or a baseline) |
| 6 | PHPUnit (`php artisan test`) | ⚠ 47 pass / 5 fail | see roster below |
| 7 | MySQL 8 engine-parity | ⏸ NOT EXECUTED | local engine is **MariaDB 10.4.32**, not MySQL 8 |
| — | Gitleaks | ⏸ NOT EXECUTED | runs in GitHub Actions only |

## PHPUNIT ROSTER (actual)

### PASS (7 suites fully green)
- ✅ `LocalizationTest`, `AuditImmutabilityTest`, `EscalationGuardTest`, `SecurityHeadersTest`,
  `UserLifecycleTest`, `RbacConformanceTest` (after count fix), **`MembershipEntitlementTest` (1–8 ALL PASS)**.
- ✅ **Billing A, B, C, E, F, G PASS** (webhook idempotency, signature, immediate revocation, invoice
  uniqueness, duplicate-payment, membership hook). Billing's own logic verifies GREEN.
- ✅ `AccountIsolationTest`: 3/5 (cross-account direct-access denied, internal bypass, create-stamping).

### FAIL (5 — ALL one root cause: scope isolation under console)
| Test | Cause |
|---|---|
| `BillingSubstrateTest > d tenant isolation` | TenantScope console-bypass |
| `CrossTenantIsolationTest > tenant a cannot see tenant b` | TenantScope console-bypass |
| `CrossTenantIsolationTest > reads fail closed when no tenant resolved` | TenantScope console-bypass |
| `AccountIsolationTest > enumeration is isolated to own account` | AccountScope isolation under console/no-auth context |
| `AccountIsolationTest > null account user sees no other org rows` | AccountScope isolation under console/no-auth context |

**Root cause (ARCHITECTURAL — flagged, NOT fixed):** `TenantScope::apply()` returns early when
`app()->runningInConsole()` is true. **PHPUnit always runs in console**, so TenantScope never filters →
isolation tests fail. The same console/non-auth context defeats the AccountScope enumeration tests.
> **Wider implication (security):** the `runningInConsole()` bypass means tenant isolation does **not**
> apply in ANY console context — including **queue workers and scheduled commands** (e.g. the hourly
> `billing:reconcile` job). That is a potential cross-tenant exposure in async/scheduled processing and
> needs an architectural decision (this execution did NOT modify TenantScope — your stop rule).

## DEFECTS SURFACED & FIXED (bug/test fixes — not architecture/features)

| Defect | Fix | Tests recovered |
|---|---|---|
| `SubscriptionService::fire()` → "Call to first() on null" when actor is null (`optional($actor)->getRoleNames()->first()`) | `$actor?->getRoleNames()->first()` | Billing **a, c, g** |
| `RbacConformanceTest` asserted 13 roles (stale; 14 since FRANCHISE_ADMIN/D-079) | assert `count(Roles::ALL)` | RBAC roles test |
| `MembershipEntitlementTest::test_7` created a user with `tenant_id=7` (FK to non-existent `core_tenants` row) | user `tenant_id` left null; sub-stamp is what's under test | Membership **7** |

Progression: first run **42 pass / 10 fail** → after fixes **47 pass / 5 fail**. The 5 remaining are the
single architectural isolation issue.

## NOT-GREEN GATES — remediation (no architecture)

- **Pint (FAIL):** run `./vendor/bin/pint` (auto-format ~36 files). Cosmetic.
- **Larastan (FAIL, 113):** add `@property` annotations to models OR `phpstan analyse --generate-baseline`
  to accept current findings (then burn down). No runtime impact.
- **engine-parity (NOT EXECUTED):** run on the MySQL 8 CI job (authoritative for isolation/FULLTEXT/JSON).

## ENGINE CAVEAT

All PHPUnit results are from **sqlite on PHP 8.2.12**. The TenantScope failures are independent of engine
(console-bypass), but FULLTEXT/JSON correctness and the *positive* isolation behaviour must be confirmed on
**MySQL 8 / PHP 8.3** in CI before any GREEN claim is final.

## STATUS

First GREEN CI: **NOT achieved.** Substantive progress: app runs, 47/52 tests pass, Membership 8/8 +
Billing 6/7 green. Remaining to GREEN: (1) ARCHITECTURAL decision on the TenantScope/AccountScope
console-bypass (5 tests), (2) Pint auto-format, (3) Larastan baseline/annotations, (4) run on MySQL 8 + PHP
8.3, (5) the CVE-2026-48019 decision.
