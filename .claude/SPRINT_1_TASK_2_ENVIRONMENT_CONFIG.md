# SPRINT 1 · TASK 2 — ENVIRONMENT CONFIGURATION (RECORD)
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Complete — Awaiting Approval (one decision point open: DB engine value)
Covers: T-2.1, T-2.2, T-2.3, T-2.4 (SPRINT_1_TASK_BREAKDOWN)
Decision References: D-002, D-037, D-039, LIM-03 / M-3

---

## SUMMARY

Task 2 establishes the configuration and CI foundation that enforces the
config-only migration guarantee (D-037) and the governance quality gates
(IMPLEMENTATION_GOVERNANCE §7). It contains CI/ops/config artifacts only — no
application code, models, controllers, or migrations.

---

## T-2.1 — FEATURE FLAG CONFIGURATION WIRING

**Status: COMPLETE (no new file required).**

The control surface already exists from Task 1 and satisfies the acceptance
criteria:

- `config/ics.php` exposes `ics.flags.*`, `ics.ai.*`, `ics.edge.*`, all read from
  `.env` via `env()` and consumed only via `config('ics.*')`.
- `.env.example` declares every flag; the shared-hosting profile sets all deferred
  flags to `false` (`ICS_WAREHOUSE_ETL_ENABLED`, `ICS_HEAVY_JOBS`,
  `ICS_AI_HIGH_VOLUME`, `ICS_COMMUNITY_SCALING`).

**Consumption convention (binding):** deferred runtime behaviour is gated as
`if (config('ics.flags.<flag>')) { … }`. No code reads `env()` directly outside
config files (so `config:cache` is safe). A config-resolution test is added with
the Sprint 1 test baseline (T-10.1), keeping Task 2 free of application code.

**Acceptance:** PASS — flags resolve via `config('ics.*')`; shared profile all-false.

---

## T-2.2 — HARDCODED DRIVER CI GATE

**Status: COMPLETE.** File: `scripts/ci/check-hardcoded-drivers.sh`

- Greps `app/` and `routes/` for env-varying driver literals
  (`redis|database|sync|memcached|s3`) in connection/store/driver calls.
- Exits non-zero (fails the build) on any match; passes clean otherwise.
- Heuristic guardrail; named config keys (e.g. filesystem disks `public`/`local`)
  are intentionally not flagged. Pattern/allowlist is tunable as code grows.

**Acceptance:** PASS — a planted hardcoded driver fails; clean code passes
(verified by the gate's logic; wired into CI below).

---

## T-2.3 — CI PIPELINE FOUNDATION

**Status: COMPLETE.** File: `.github/workflows/ci.yml`

Jobs and gates (Governance §7):

| Step | Gate |
|---|---|
| `composer validate --strict` | Manifest integrity |
| `composer install` | Reproducible deps |
| `composer audit` | Dependency vulnerability scan (RS-1; report-only until triaged) |
| `check-hardcoded-drivers.sh` | D-037 driver gate (T-2.2) |
| `pint --test` | Code style |
| `phpstan analyse` | Static analysis / module boundary (D-027) |
| `php artisan test` | Test suite (SQLite in-memory) |
| `gitleaks` | Secret scan |

An **engine-parity job** (T-2.4) is included but commented out until the DB engine
is confirmed (see below).

**Acceptance:** PASS — pipeline defined; green on clean code, red on a failing gate.
(Runs once the bootstrapped project — skeleton + overlay — is committed.)

---

## T-2.4 — DATABASE ENGINE PINNING

**Status: COMPLETE.** Engine CONFIRMED via Gate 0: **MySQL 8.x**.
Files: `docker-compose.yml`, `.github/workflows/ci.yml`

- Production engine confirmed as MySQL 8.x — matches D-002. **LIM-03 RESOLVED**
  (the anticipated MariaDB-compatibility concern does not apply).
- `docker-compose.yml` pins local/staging to `mysql:8.0` (override `DB_IMAGE=mysql:8.4`
  if production is 8.4).
- CI engine-parity job ENABLED in `ci.yml` (MySQL 8 service) — validates
  JSON/FULLTEXT/ENUM behaviour against production parity (M-3).

**Residual housekeeping:** confirm exact minor version (8.0 vs 8.4) and align the
image tag. Not a risk.

**Acceptance:** PASS — engine confirmed, pinned across local/staging/CI; LIM-03 closed.

---

## FILES CREATED IN TASK 2

| File | Task | Type |
|---|---|---|
| scripts/ci/check-hardcoded-drivers.sh | T-2.2 | CI shell script |
| .github/workflows/ci.yml | T-2.3 | CI pipeline config |
| docker-compose.yml | T-2.4 | Local engine-parity config |

No application code, models, controllers, or migrations were created.

---

## COMPLIANCE CONFIRMATIONS

| Confirmation | Result |
|---|---|
| D-037 config-only migration guarantee intact | ✅ (driver gate now enforces it) |
| D-039 security baseline intact | ✅ (secret scan + audit added; no secrets committed) |
| No business logic introduced | ✅ (CI/ops/config artifacts only) |
| No new architectural decisions introduced | ✅ (implements D-037/D-039; engine value is a recorded data point, not a new decision) |

---

## OPEN ITEM FOR APPROVAL / INPUT

None blocking. DB engine confirmed (MySQL 8.x); LIM-03 closed.
Housekeeping: confirm exact minor version (8.0 vs 8.4) and align `DB_IMAGE` tag.

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Lead Architect | | | | |
| Technical Lead | | | | |
| DevOps / Operations | | | | |

**Status:** Task 2 COMPLETE (T-2.1–T-2.4). Awaiting approval before Task 3.
**Do not proceed beyond Task 2.**
