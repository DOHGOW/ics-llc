# MYSQL ENGINE PARITY REPORT — Phase 5
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05 → executed
Status: EXECUTED on a real MySQL-family server — with an engine-version caveat.
Author: Lead Architect

## RESULT: ✅ 57 PASSED / 0 FAILED against a real server — ⚠ engine was **MariaDB 10.4.32, NOT MySQL 8**

The full suite + all 80 migrations were executed against a **running MariaDB 10.4.32** server (XAMPP),
with foreign-key enforcement ON. **All 57 tests pass; all 80 migrations apply.** This is genuine
real-server parity signal (far stronger than sqlite). **Caveat:** the production/CI target is **MySQL
8.0**; MariaDB 10.4 is MySQL-family but not identical — the **MySQL 8 CI `engine-parity` job remains the
authoritative gate** (`mysql:8.0` is provisioned there; not available in this environment).

## VERIFICATION MATRIX (actual, MariaDB 10.4)

| Required check | Result | Evidence |
|---|---|---|
| Migration parity | ✅ | all 80 migrations apply on MariaDB (`migrate --force` exit 0) |
| FK integrity | ✅ (and enforced) | MariaDB enforces FKs — it SURFACED a test-fixture FK gap (now fixed) that sqlite hid |
| TenantScope behaviour | ✅ | `CrossTenantIsolationTest` 4/4 + `TenantScopeAsyncTest` 6/6 on MariaDB |
| Billing workflows | ✅ | `BillingSubstrateTest` A–G 7/7 on MariaDB |
| Membership entitlement | ✅ | `MembershipEntitlementTest` 1–8 on MariaDB |
| Invoice sequences | ✅ | invoice-sequence-uniqueness (Billing E) passes on MariaDB (per-tenant+year, row-locked) |
| FULLTEXT behaviour | ⚠ partial | content tables (with FULLTEXT) migrate + the suite passes on MariaDB; **dedicated FULLTEXT search assertions are not in the current suite** — confirm on MySQL 8 CI (D-038) |

## ENGINE-PARITY VALUE DELIVERED

Running against a real FK-enforcing engine **surfaced a defect sqlite hid**:
- `core_users.account_id` FK (`fk_core_users_account` → `crm_accounts`) is enforced on MariaDB. The
  `AssertsOrgIsolation` test fixture assigned `account_id` without a parent account row → FK violation.
  **Fixed** (fixture now seeds the parent `crm_accounts` row). This is exactly the class of issue the
  engine-parity gate exists to catch.

## CAVEATS / CARRIED TO CI

1. **MySQL 8 vs MariaDB 10.4:** behavioural deltas possible in JSON functions, FULLTEXT relevance, and
   ENUM/SQL-mode strictness. The **MySQL 8 CI job is authoritative** and must be GREEN before production.
2. **No dedicated FULLTEXT search test:** add one (Knowledge/Research search, D-038) and assert on MySQL 8.
3. Run executed on **PHP 8.2.12**; CI runs PHP 8.3 (the canonical runtime).

## STATUS

Engine parity: ✅ **GREEN on a real MySQL-family server (MariaDB 10.4)**; ⏸ **MySQL 8 authoritative run =
CI gate** (not provisionable here). Schema + FK + Billing + Membership + TenantScope all verified on a real
server.
