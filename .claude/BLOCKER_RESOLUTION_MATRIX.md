# BLOCKER RESOLUTION MATRIX
# ICS Enterprise Ecosystem Platform — Environment Remediation & Bootstrap Recovery

Version: 1.0
Date: 2026-06-05
Status: Remediation ANALYSIS only — no code/architecture/module changes performed.
Author: Lead Architect
Source of blockers: PRODUCTION_READINESS_CERTIFICATION.md (NO GO, executed evidence)

> Classification keys: **RESOLVABLE NOW** (local/CI, no external party) · **REQUIRES HOSTINGER** (host
> access) · **REQUIRES DECISION** (owner/architect choice) · **REQUIRES EXTERNAL DEPENDENCY** (upstream/
> third party). Two blockers (B11, B12) are **newly identified** during this analysis and added for
> completeness.

---

## SUMMARY CLASSIFICATION

| Blocker | Short name | Classification |
|---|---|---|
| B1 | PHP 8.2 < 8.3 | RESOLVABLE NOW (+ REQUIRES HOSTINGER to confirm on host) |
| B2 | Laravel dependency/security constraint | RESOLVABLE NOW (+ REQUIRES DECISION on audit policy) |
| B3 | Missing Laravel skeleton | RESOLVABLE NOW |
| B4 | ext-intl missing | RESOLVABLE NOW (+ REQUIRES HOSTINGER to confirm on host) |
| B5 | Node/npm missing | RESOLVABLE NOW (build only — NOT a CI-GREEN blocker) |
| B6 | MariaDB 10.4 vs MySQL 8 | RESOLVABLE NOW in CI; REQUIRES DECISION + possibly REQUIRES HOSTINGER for prod |
| B7 | composer.lock absent | RESOLVABLE NOW |
| B8 | No GREEN CI run | RESOLVABLE NOW (after B1–B3, B7, B11) |
| B9 | Hostinger spike not executed | REQUIRES HOSTINGER |
| B10 | Duplicate openssl load | RESOLVABLE NOW |
| **B11** | **database/factories absent (tests use User::factory())** | RESOLVABLE NOW (NEW) |
| **B12** | **standard config set absent (database.php etc.)** | RESOLVABLE NOW (NEW) |

**Verdict:** 9 of 12 are RESOLVABLE NOW without any external party. Only **B9 truly REQUIRES HOSTINGER**;
**B6 REQUIRES a DECISION** (and possibly host/VPS); **B2 may REQUIRE a DECISION** (audit-ignore policy).

---

## PER-BLOCKER DETAIL

### B1 — PHP 8.2.12 < required ^8.3
- **Root cause:** local runtime is XAMPP PHP 8.2.12; `composer.json` requires `php: ^8.3`.
- **Impact:** `composer install/update` aborts on platform requirement; app cannot run.
- **Resolution:** install/select PHP 8.3.x (local PHP switcher or use the CI runner, already 8.3). On the
  host, confirm the plan offers PHP 8.3 (hPanel PHP selector).
- **Verification:** `php -v` → 8.3.x.
- **Pass criteria:** PHP ≥ 8.3.0, < 9.0.
- **Risk if unresolved:** total — nothing installs or runs. CRITICAL.
- **Class:** RESOLVABLE NOW (+ REQUIRES HOSTINGER to confirm host PHP).

### B2 — Laravel dependency / security-advisory constraint
- **Root cause:** with no lock on PHP 8.2, `composer install` ran `update` and reported `laravel/framework
  ^11.0` candidates "not loaded, affected by security advisories" (Composer 2.9.5 `block-insecure`), plus
  the PHP-platform failure. Cause is intertwined: the resolver could not pick a clean, platform-valid
  framework version.
- **Impact:** dependency tree unresolved → no vendor → nothing downstream runs; `composer audit` cannot be
  GREEN until advisories are addressed.
- **Resolution (no version assumed — determine empirically):** on PHP 8.3,
  `composer update -W laravel/framework` and inspect; run `composer audit` to enumerate the EXACT advisory
  IDs and affected version ranges; for each, EITHER (i) raise the constraint floor to the patched release
  Composer reports as fixed, OR (ii) if it is a historical/false-positive for the resolved version, record
  it in `config.audit.ignore` with written justification. Then commit `composer.lock`.
- **Verification:** `composer update` resolves; `composer audit` returns clean (or only documented,
  approved ignores); `composer install --dry-run` succeeds from the lock.
- **Pass criteria:** lock generated; `composer audit` exit 0 (no un-triaged advisories).
- **Risk if unresolved:** insecure framework in production, or unbuildable tree. CRITICAL.
- **Class:** RESOLVABLE NOW (+ REQUIRES DECISION: hard-fail vs ignore policy for `composer audit`).

### B3 — Missing Laravel skeleton (artisan / public / runtime dirs)
- **Root cause:** repo is the ICS **overlay** (`app/ config/ database/ routes/ tests/ resources/ lang/
  bootstrap/{app,providers}.php`) WITHOUT the Laravel application root: `artisan`, `public/`,
  `public/index.php`, `bootstrap/cache/`, `storage/framework/{cache,sessions,views}`, `storage/logs` are
  absent. ci.yml explicitly "assumes the bootstrapped Laravel project (skeleton + overlay) is committed."
- **Impact:** `php artisan` cannot run (key:generate, migrate, test); HTTP entrypoint missing; framework
  cannot cache/log/session. Blocks gates 1–4.
- **Resolution:** generate a Laravel 11 skeleton and MERGE the overlay (see BOOTSTRAP_RECOVERY_PLAN §A) —
  additive-only: add skeleton files the overlay lacks; never overwrite an ICS file.
- **Verification:** `php artisan --version` runs; `php artisan route:list` lists the 14 route groups;
  `php artisan about` boots.
- **Pass criteria:** artisan boots; `public/index.php` present; runtime dirs exist; providers register.
- **Risk if unresolved:** total — the platform is not a runnable application. CRITICAL.
- **Class:** RESOLVABLE NOW.

### B4 — ext-intl missing
- **Root cause:** local PHP build lacks `intl` (absent from `php -m`).
- **Impact:** i18n/localization (D-028), `LocalizationTest`, and any ICU formatting fail.
- **Resolution:** enable `ext-intl` (local php.ini; CI runner already includes it; host PHP selector).
- **Verification:** `php -m | grep intl`; `LocalizationTest` GREEN.
- **Pass criteria:** intl loaded.
- **Risk if unresolved:** localization broken; tests red. HIGH.
- **Class:** RESOLVABLE NOW (+ REQUIRES HOSTINGER to confirm host intl).

### B5 — Node / npm missing
- **Root cause:** Node not installed locally.
- **Impact:** `npm ci` + `npm run build` (Vite/Tailwind/Alpine CSP, D-048) cannot run → no compiled
  frontend assets. **NOTE:** ci.yml has NO npm step → this does NOT block first GREEN CI; it blocks
  production asset build/deploy.
- **Resolution:** install Node LTS (20/22) + npm; `npm ci`; `npm run build`.
- **Verification:** `node -v` / `npm -v`; `public/build/manifest.json` produced.
- **Pass criteria:** assets build without error.
- **Risk if unresolved:** no production UI assets (APIs still function). MEDIUM (HIGH for launch).
- **Class:** RESOLVABLE NOW (build only).

### B6 — MariaDB 10.4.32 instead of MySQL 8
- **Root cause:** XAMPP ships MariaDB 10.4; blueprint/CI mandate MySQL 8 (JSON, FULLTEXT, ENUM, TenantScope
  parity).
- **Impact:** engine-specific behaviour diverges (FULLTEXT search D-038, JSON casts, ENUM, isolation
  filtering) → false local results; production parity not guaranteed.
- **Resolution:** use MySQL 8 — CI engine-parity job already provisions `mysql:8.0`; locally install MySQL 8
  or rely on CI; for production confirm the host engine.
- **Verification:** `mysql --version` → MySQL 8.x; engine-parity job GREEN.
- **Pass criteria:** all tests GREEN against MySQL 8 (not MariaDB, not only sqlite).
- **Risk if unresolved:** prod behaviour differs from tested; FULLTEXT/JSON bugs. HIGH.
- **Class:** RESOLVABLE NOW in CI; REQUIRES DECISION (host engine: Hostinger shared is often MariaDB →
  may force MySQL-8 add-on or VPS, ties to R-010); possibly REQUIRES HOSTINGER.

### B7 — composer.lock absent
- **Root cause:** never generated/committed.
- **Impact:** non-reproducible installs; `composer install` falls back to `update`; CI can drift.
- **Resolution:** after B1/B2, `composer update` once on PHP 8.3 → commit `composer.lock`.
- **Verification:** `composer install` (not update) succeeds from lock; `composer validate --strict` clean.
- **Pass criteria:** committed lock; reproducible `composer install`.
- **Risk if unresolved:** drift, irreproducible builds, audit gaps. MEDIUM.
- **Class:** RESOLVABLE NOW.

### B8 — No GREEN CI run (R-013)
- **Root cause:** pipeline never advanced past install; gates never executed.
- **Impact:** zero objective verification of RBAC/Audit/Lifecycle/Security/Isolation/Billing/Membership.
- **Resolution:** clear B1–B3, B7, B11, B12 → push to a branch → run GitHub Actions; iterate failures.
- **Verification:** Actions run GREEN (both `quality` and `engine-parity` jobs).
- **Pass criteria:** all CI jobs green; artifact attached to go-live checklist.
- **Risk if unresolved:** R-013 stays open; cannot certify. HIGH.
- **Class:** RESOLVABLE NOW (after prerequisites).

### B9 — Hostinger capability spike not executed
- **Root cause:** no Hostinger access from this environment (local XAMPP box).
- **Impact:** host PHP/intl/MySQL/cron/webhook/SSL/.env-isolation unverified (D-049 #5).
- **Resolution:** run the VPS_MIGRATION_CHECKLIST Part A.4 spike on the actual host (SSH/hPanel).
- **Verification:** spike checklist completed; PASS/PARTIAL/FAIL recorded.
- **Pass criteria:** host provides PHP 8.3 + intl + MySQL 8 + cron + SSL + reachable webhook + .env off web root.
- **Risk if unresolved:** deploy to an incapable host; R-010 (shared-tenancy) unmitigated. HIGH.
- **Class:** REQUIRES HOSTINGER.

### B10 — Duplicate openssl load warning
- **Root cause:** php.ini enables `openssl` twice (extension line + bundled).
- **Impact:** cosmetic warning on every PHP invocation; can pollute CLI/JSON output and some tooling.
- **Resolution:** remove the duplicate `extension=openssl` line in php.ini.
- **Verification:** `php -v` emits no "already loaded" warning.
- **Pass criteria:** clean `php -v`.
- **Risk if unresolved:** noisy output; possible parser confusion in scripts. LOW.
- **Class:** RESOLVABLE NOW.

### B11 — database/factories absent, but tests use `User::factory()` (NEW)
- **Root cause:** `database/factories/` does not exist; yet `BillingSubstrateTest` and
  `MembershipEntitlementTest` (and others) call `User::factory()`. The overlay never shipped a `UserFactory`,
  and the ICS `User` maps to `core_users` with custom columns (tenant_id, account_id, status).
- **Impact:** those PHPUnit suites error at `User::factory()` → tests cannot pass even after bootstrap.
- **Resolution:** author `database/factories/UserFactory.php` matching the ICS `core_users` schema (and
  ensure `User` uses `HasFactory`). The skeleton's default UserFactory is a starting point but MUST be
  reconciled to the ICS columns. (This is verification-enabling test scaffolding, not feature/module work.)
- **Verification:** `User::factory()->create()` succeeds; the dependent suites run.
- **Pass criteria:** factory resolves; no "factory not found"/column errors.
- **Risk if unresolved:** Billing A–G and Membership 1–8 cannot execute → certification stays blocked. HIGH.
- **Class:** RESOLVABLE NOW (NEW).

### B12 — Standard config set absent (NEW)
- **Root cause:** `config/` ships only ICS-specific files (auth, cache, ics, locales, mail, queue, security,
  session). Standard `config/database.php`, `app.php`, `filesystems.php`, `logging.php`, `services.php` are
  absent; spatie `permission.php` and `sanctum.php` are not published.
- **Impact:** DB connections, filesystem disks (media, D-024), logging, and package config rely on framework
  defaults that may not match intent; `config/database.php` is needed for the MySQL/sqlite connections the
  CI env vars assume.
- **Resolution:** add the standard configs via the skeleton merge (additive); `php artisan vendor:publish`
  for sanctum + spatie permission; reconcile with `config/ics.php` (no overwrite of ICS files).
- **Verification:** `php artisan config:show database` lists mysql + sqlite; `php artisan about` clean.
- **Pass criteria:** all required configs resolvable; migrations connect.
- **Risk if unresolved:** migrations/tests fail to connect; disks/logging misconfigured. HIGH.
- **Class:** RESOLVABLE NOW (NEW).

---

## DEPENDENCY ORDER (what unblocks what)

```
B1 (PHP 8.3) ─┐
B4 (intl)     ├─► B2 (resolve deps) ─► B7 (lock) ─┐
B10(openssl)  ┘                                    ├─► B8 (GREEN CI)
B3 (skeleton) ─────────────────────────────────────┤
B11(factories)─────────────────────────────────────┤
B12(configs)  ─────────────────────────────────────┤
B6 (MySQL8 in CI service) ─────────────────────────┘
B5 (node)  → asset build (parallel; not on CI-GREEN path)
B9 (Hostinger) → production gate (after GREEN CI)
```
