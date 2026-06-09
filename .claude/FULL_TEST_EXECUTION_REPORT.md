# FULL TEST EXECUTION REPORT — Phase 4
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05 → executed
Status: EXECUTED — actual results.
Author: Lead Architect
Runtime: PHP 8.2.12; sqlite (:memory:) AND MariaDB 10.4 (engine parity, see MYSQL_ENGINE_PARITY_REPORT).

## RESULT: ✅ 57 PASSED / 0 FAILED (121 assertions)

Identical result on **both** engines (sqlite and MariaDB 10.4): 57/57.

## SUITE ROSTER (actual)

| Suite | Result | Notes |
|---|---|---|
| `Unit\Localization\LocalizationTest` | ✅ PASS | i18n (intl present in this run) |
| `Feature\Audit\AuditImmutabilityTest` | ✅ PASS | append-only audit (D-046) |
| `Feature\Authorization\EscalationGuardTest` | ✅ PASS | role-escalation guard (D-044) |
| `Feature\Rbac\RbacConformanceTest` | ✅ PASS | RBAC (role count = count(Roles::ALL)=14, FRANCHISE_ADMIN) |
| `Feature\UserManagement\UserLifecycleTest` | ✅ PASS | lifecycle/status (D-047) |
| `Feature\Security\SecurityHeadersTest` | ✅ PASS | CSP/headers (D-039/D-048) |
| `Feature\Isolation\AccountIsolationTest` | ✅ PASS (5/5) | AccountScope (D-050) — the CRM/Portal org-isolation mechanism |
| `Feature\Tenancy\CrossTenantIsolationTest` | ✅ PASS (4/4) | TenantScope (D-076/D-088) |
| `Feature\Tenancy\TenantScopeAsyncTest` | ✅ PASS (6/6) | D-088 async (queue/fail-closed/super/explicit/trait) |
| `Feature\Billing\BillingSubstrateTest` | ✅ PASS (A–G, 7/7) | webhook/idempotency/signature/revocation/invoice/payment/hook |
| `Feature\Membership\MembershipEntitlementTest` | ✅ PASS (1–8, 8/8) | activation/revocation/elevation/boundaries/tenant/billing |

## REQUESTED COVERAGE MAPPING

| Requested suite | Where covered |
|---|---|
| Sprint 1 conformance | Localization, Audit, EscalationGuard, RBAC, Lifecycle, Security ✅ |
| RBAC | `RbacConformanceTest` ✅ |
| Audit | `AuditImmutabilityTest` ✅ |
| Lifecycle | `UserLifecycleTest` ✅ |
| Localization | `LocalizationTest` ✅ |
| Security | `SecurityHeadersTest` ✅ |
| TenantScope | `CrossTenantIsolationTest` + `TenantScopeAsyncTest` ✅ |
| Billing A–G | `BillingSubstrateTest` ✅ |
| Membership 1–8 | `MembershipEntitlementTest` ✅ |
| **CRM tests** | covered by `AccountIsolationTest` (CRM models use AccountScope, D-050/D-053); **no dedicated CRM test file exists in the current suite** |
| **Portal isolation tests** | covered by `AccountIsolationTest` (Client/Partner Portal models use AccountScope/OrgOwnedPolicy, D-055); **no dedicated Portal test file exists** |

> **Coverage gap (honest):** the suite has 11 test files. There are **no dedicated CRM or Portal feature
> test files** — their isolation is validated through the shared AccountScope harness, but module-specific
> behaviour (CRM stages/assignment D-053; Portal lifecycle D-056) is not yet covered by tests. This is
> pre-existing and is logged as a post-GREEN test-coverage backlog item (not a GREEN-CI blocker).

## DEFECTS FIXED THIS PHASE (engine-surfaced, test-fixture)

- `AssertsOrgIsolation::makeUser` set `core_users.account_id` without a parent `crm_accounts` row →
  FK violation on MariaDB (sqlite was lax). Fixed: the helper now `insertOrIgnore`s the parent account.
  Result: AccountIsolation 5/5 on both engines.

## STATUS

Full suite: ✅ **57/0 GREEN** on sqlite and MariaDB. CRM/Portal dedicated coverage = backlog (non-blocking).
