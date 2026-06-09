# ENVIRONMENT REMEDIATION PLAN
# ICS Enterprise Ecosystem Platform — Runtime Requirements (D-049 Recovery, Section C)

Version: 1.0
Date: 2026-06-05
Status: Analysis/plan only — no provisioning performed here.
Author: Lead Architect
Pairs with: BLOCKER_RESOLUTION_MATRIX.md, BOOTSTRAP_RECOVERY_PLAN.md, GREEN_CI_EXECUTION_PLAN.md

> Definitive runtime requirements, each marked **MANDATORY / RECOMMENDED / OPTIONAL**. "MANDATORY" = the
> platform cannot build, test, or run correctly without it.

---

## OBSERVED vs REQUIRED (this environment)

| Component | Observed (local) | Required | Gap |
|---|---|---|---|
| PHP | 8.2.12 (XAMPP) | 8.3.x | ❌ B1 |
| ext-intl | absent | present | ❌ B4 |
| Composer | 2.9.5 | 2.x | ✅ |
| Node/npm | absent | LTS | ❌ B5 (build only) |
| DB engine | MariaDB 10.4.32 | MySQL 8.x | ❌ B6 |
| git | 2.54.0 | any | ✅ |
| openssl | loaded twice (warning) | single | ⚠ B10 |

---

## C.1 PHP — **MANDATORY**

- **Version:** PHP **8.3.x** (`composer.json: php ^8.3`; CI uses 8.3). Not 8.2, not 8.4-only assumptions.
- **php.ini:** remove the duplicate `extension=openssl` (B10); set sane `memory_limit` (≥256M for
  composer/larastan), `max_execution_time`, `date.timezone`.

### PHP extensions

| Extension | Level | Why |
|---|---|---|
| intl | **MANDATORY** | i18n/localization (D-028); currently MISSING (B4) |
| mbstring | **MANDATORY** | string handling, framework |
| openssl | **MANDATORY** | TLS, hashing, Sanctum, Paystack HMAC (single-load — B10) |
| pdo_mysql | **MANDATORY** | MySQL 8 connection (app + engine-parity) |
| pdo_sqlite | **MANDATORY** | phpunit.xml default `sqlite :memory:` (local/default test run) |
| ctype, curl, dom, fileinfo, tokenizer, xml | **MANDATORY** | framework + HTTP + XML |
| bcmath | **MANDATORY** | money/precision (billing amounts) |
| zip | **MANDATORY** | composer dist installs |
| gd | **RECOMMENDED** | image/media handling (D-024); 2FA QR (bacon-qr-code) — SVG fallback exists |
| gmp | OPTIONAL | perf for large-int math (present locally) |
| imap, exif, gettext | OPTIONAL | not required by the platform |

> Local box already has: bcmath, ctype, curl, dom, fileinfo, gd, mbstring, openssl, pdo_mysql, pdo_sqlite,
> tokenizer, xml, zip. **Only `intl` is missing** among MANDATORY — enabling it closes the extension gap.

---

## C.2 Composer — **MANDATORY**

- **Version:** 2.x (have 2.9.5 — fine).
- **Policy decisions (REQUIRES DECISION):**
  - `audit.block-insecure` — recommend **keep ON** for production safety; document any `audit.ignore`.
  - Generate and **commit `composer.lock`** (B7) on PHP 8.3 for reproducible installs.

---

## C.3 Node.js / npm — **MANDATORY (for build) / not required for CI-GREEN**

- **Version:** Node **LTS 20 or 22** + bundled npm.
- **Use:** `npm ci` then `npm run build` (Vite → Tailwind + Alpine CSP, D-048) producing
  `public/build/manifest.json`.
- **Note:** `.github/workflows/ci.yml` runs **no** npm step → Node is NOT on the first-GREEN-CI critical
  path. It is required for asset build and any deploy that serves compiled assets.

---

## C.4 MySQL — **MANDATORY (MySQL 8, not MariaDB)**

- **Version:** **MySQL 8.0.x** (CI service is `mysql:8.0`; if prod runs 8.4, pin CI to match).
- **Why not MariaDB:** FULLTEXT (D-038 search), JSON casts, ENUM semantics, and TenantScope isolation must
  be validated on the production engine; MariaDB 10.4 diverges. **Local MariaDB results are not authoritative.**
- **Local options:** install MySQL 8 standalone, or use the `docker-compose.yml` present in the repo, or
  rely on the CI engine-parity job for authoritative results.
- **Decision (REQUIRES DECISION + possibly REQUIRES HOSTINGER):** confirm the production host provides
  MySQL 8; Hostinger shared frequently ships MariaDB → may require a MySQL add-on or VPS (ties to R-010).

---

## C.5 OS / shell / tooling — **RECOMMENDED**

- Bash available (git-bash) for `scripts/ci/check-hardcoded-drivers.sh`; PowerShell for Windows ops.
- `docker` (OPTIONAL) — `docker-compose.yml` present enables a reproducible MySQL 8 + app locally.

---

## C.6 ENVIRONMENT TOPOLOGY (where each requirement must hold)

| Requirement | Local dev | GitHub Actions CI | Hostinger prod |
|---|---|---|---|
| PHP 8.3 + intl | for local runs | ✅ already (runner) | ❓ confirm (B9) |
| MySQL 8 | for local parity | ✅ engine-parity job | ❓ confirm/decision (B6/B9) |
| Node LTS | for asset build | not used | for deploy build |
| Composer + lock | yes | yes | yes (install from lock) |

**Fastest authoritative environment = GitHub Actions** (already PHP 8.3 + MySQL 8 + full extension set).
Local Windows/XAMPP remediation (install PHP 8.3, intl, MySQL 8, Node) is OPTIONAL for first GREEN CI and
RECOMMENDED for day-to-day development.

---

## C.7 PASS CRITERIA (environment ready)

1. `php -v` → 8.3.x, no openssl warning.
2. `php -m` includes all MANDATORY extensions (esp. **intl**).
3. `composer --version` 2.x; `composer.lock` committed; `composer audit` clean/triaged.
4. `mysql --version` → 8.x (or CI engine-parity GREEN).
5. `node -v`/`npm -v` present (for build); `npm run build` succeeds.
