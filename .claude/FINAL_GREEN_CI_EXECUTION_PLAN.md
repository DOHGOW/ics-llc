# FINAL GREEN CI EXECUTION PLAN
# ICS Enterprise Ecosystem Platform — current status → first GREEN CI

Version: 1.0
Date: 2026-06-05
Status: Execution plan — ANALYSIS ONLY (no code performed here).
Author: Lead Architect
Baseline (actual): app boots (L11.54.0), 266 routes, 80 migrations, vendor+lock present; **47 pass / 5 fail**;
Pint ✗ (~36), Larastan ✗ (113), engine-parity NOT EXECUTED.
Decisions: TenantScope → **OPTION B**; CVE-2026-48019 → **OPTION A now / OPTION B before prod**.

---

## CURRENT-STATE TEST LEDGER

| Bucket | State |
|---|---|
| Membership 1–8 | ✅ GREEN |
| Billing A/B/C/E/F/G | ✅ GREEN |
| Sprint-1 conformance (RBAC, Audit, Escalation, Security, Lifecycle, Localization) | ✅ GREEN |
| AccountIsolation (3 of 5) | ✅ GREEN |
| **Billing-d + CrossTenant×2** | ❌ TenantScope console-bypass → fixed by TenantScope OPTION B |
| **AccountIsolation×2** | ❌ AccountScope test-context (sibling item) |

---

## EXECUTION SEQUENCE (ordered; each step has a pass gate)

### Step 1 — TenantScope remediation (OPTION B) — *separately authorized implementation*
- Remove the blanket `runningInConsole()` bypass; add: maintenance/super context for migrate/seed;
  context-propagating queue middleware; fail-closed default in async; explicit `acrossTenants()` for
  intentional cross-tenant jobs (reconciliation already complies).
- Add a queue-tenancy isolation test.
- **Gate:** `BillingSubstrateTest>d`, `CrossTenantIsolationTest` (both) PASS; migrations/seeders still run.
- **Effort:** ~0.5–1 day.

### Step 1b — AccountScope isolation tests (sibling)
- Set an authenticated acting-user/tenant context in `AccountIsolationTest` setup (or apply the same
  context-aware pattern to AccountScope, D-050).
- **Gate:** `AccountIsolationTest` enumeration + null-account tests PASS.
- **Effort:** ~0.25 day (test-context) — confirm whether it is purely test setup or an AccountScope behavior.

### Step 2 — CVE-2026-48019 decision (OPTION A now)
- Add `composer.json` `audit.ignore: [GHSA-5vg9-5847-vvmq]` with justification + compensating-control note;
  **re-enable `audit.block-insecure`** (revert the temporary Phase-2 change); add an email-hardening task.
- Record the **OPTION B (Laravel 12.60+) production-gate** fast-follow.
- **Gate:** `composer audit` exits clean (only the documented, approved ignore); `composer validate` clean.
- **Effort:** hours.

### Step 3 — Pint cleanup
- `./vendor/bin/pint` (auto-format the ~36 flagged files; cosmetic — `class_attributes_separation`,
  `ordered_imports`, `braces_position`, etc.).
- **Gate:** `pint --test` exits 0.
- **Effort:** ~minutes (+ review).

### Step 4 — Larastan remediation
- Triage the **113** `property.notFound` findings (dynamic Eloquent attributes). Preferred: add `@property`
  annotations to the affected models (Training/Billing/etc.) and fix any genuine issues; pragmatic interim:
  `phpstan analyse --generate-baseline` to accept current findings, then burn down.
- **Gate:** `phpstan analyse` exits 0 (clean or baselined).
- **Effort:** ~0.5–1 day (annotations) or ~1–2 h (baseline interim).

### Step 5 — PHPUnit rerun (authoritative)
- `php artisan test` on **PHP 8.3** (CI runner).
- **Gate:** **52/52 PASS** (47 current + 3 TenantScope + 2 AccountScope).
- **Effort:** minutes (CI).

### Step 6 — MySQL 8 engine-parity run
- Run the suite against **MySQL 8** (ci.yml `engine-parity` job / local MySQL 8 / docker-compose) — the
  authoritative engine for FULLTEXT (D-038), JSON casts, ENUM, and the **positive** tenant-isolation
  behaviour. (Local MariaDB 10.4 is NOT acceptable for this gate.)
- **Gate:** full suite GREEN on MySQL 8.
- **Effort:** minutes (CI), once the runner is used.

### Step 7 — Final CI verification (GitHub Actions, PHP 8.3 + MySQL 8)
- Push the recovery branch; both `quality` and `engine-parity` jobs + `secrets` (gitleaks) GREEN.
- Pipeline: validate ✅ · install(lock) ✅ · audit ✅(approved ignore) · driver-gate ✅ · Pint ✅ · Larastan ✅
  · PHPUnit ✅ · engine-parity ✅ · gitleaks ✅.
- **Gate:** first GREEN CI achieved → **R-012 / R-013 closed; D-049 #3–4 satisfied.**

---

## DEPENDENCIES / CRITICAL PATH

```
Step 1 (TenantScope B) ─┬─► Step 5 (PHPUnit 52/52) ─► Step 6 (MySQL 8) ─► Step 7 (GREEN CI)
Step 1b (AccountScope) ─┘
Step 2 (CVE A) ──────────► (audit gate within Step 7)
Step 3 (Pint) ───────────► (Pint gate within Step 7)
Step 4 (Larastan) ───────► (Larastan gate within Step 7)
```
Steps 2/3/4 are parallelisable with Step 1. The runtime prerequisite for the authoritative run is **PHP 8.3
+ MySQL 8** — already provided by the GitHub Actions runner (so no local provisioning is required to reach
first GREEN).

## CARRIED PRODUCTION GATES (NOT part of first GREEN CI)

- Hostinger capability spike (B9) — host PHP 8.3/intl/MySQL 8/cron/SSL/webhook/.env isolation.
- CVE OPTION B (Laravel 12.60+ upgrade) — production-gate fast-follow.
- TenantScope production enablement (D-078-A/B + isolation GREEN on MySQL 8).
- R-010 shared-tenancy data decision (VPS vs shared).
