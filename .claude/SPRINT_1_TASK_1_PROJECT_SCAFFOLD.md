# SPRINT 1 · TASK 1 — LARAVEL 11 PROJECT SCAFFOLD (SPECIFICATION)
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Awaiting Approval (T-1.2 package list requires Architect sign-off)
Covers: T-1.1, T-1.2, T-1.3 (SPRINT_1_TASK_BREAKDOWN)
Decision References: D-002, D-014, D-020, D-021, D-022, D-024, D-037, D-039

---

## SCOPE & INTENT

This document specifies — concretely and ready to execute — the Laravel 11 project
scaffold: folder structure, package selection, composer/npm dependencies, the full
environment-variable specification, and the configuration strategy.

It is a **scaffold + configuration specification**, not application code. It contains
no models, controllers, migrations, or business logic. On approval, these exact
artifacts are materialized (project created, packages installed, `.env.example`
committed). Nothing is installed before approval, because the package list is an
approval-gated item (T-1.2, Governance §8).

---

## DELIVERABLE 1 — FOLDER STRUCTURE

Laravel 11 streamlined layout, extended with the ICS module-oriented structure
(Blueprint §3.1). Markers: **[S1]** created/used in Sprint 1 · **[stub]** created
empty/placeholder now · **[future]** added in later module sprints.

```
ics-platform/
├── app/
│   ├── Console/
│   │   └── Commands/              [stub]  (scheduled cmds added per module)
│   ├── Events/
│   │   └── Core/                  [S1]    UserRegistered, LoggedIn, RoleAssigned…
│   ├── Exceptions/                [S1]    custom handlers (via bootstrap/app.php)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php     [S1]    base (Laravel 11 abstract base)
│   │   │   ├── API/V1/            [stub]  versioned API controllers (later)
│   │   │   └── Web/               [stub]  Blade controllers (later)
│   │   ├── Middleware/            [S1]    SecurityHeaders, locale, etc.
│   │   ├── Requests/              [stub]  FormRequests (later)
│   │   └── Resources/             [stub]  API Resources (later)
│   ├── Listeners/                 [S1]    audit/notify listeners (ShouldQueue)
│   ├── Models/
│   │   ├── Core/                  [S1]    User, Tenant, AuditLog… (built T-3/T-4)
│   │   └── …                      [future] per-module model namespaces
│   ├── Notifications/             [S1]    Welcome, PasswordChanged, RoleAssigned
│   ├── Policies/                  [S1]    base policy pattern (T-5.4)
│   ├── Providers/
│   │   └── AppServiceProvider.php [S1]
│   └── Services/
│       ├── Auth/                  [S1]    AuthService (T-4)
│       ├── Audit/                 [S1]    AuditService (T-6)
│       ├── Security/              [S1]    breach check, headers helpers
│       └── …                      [future] CRM, Training, AI, Billing, Content…
├── bootstrap/
│   ├── app.php                    [S1]    middleware, routing, exceptions (L11)
│   └── cache/                     [S1]
├── config/
│   ├── app.php auth.php cache.php database.php filesystems.php
│   │   queue.php session.php mail.php logging.php                 [S1]
│   ├── sanctum.php                [S1]   (published by install:api)
│   ├── permission.php             [S1]   (Spatie, published)
│   └── ics.php                    [S1]   ICS feature flags + AI caps (D-037)
├── database/
│   ├── factories/                 [S1]   UserFactory (test support)
│   ├── migrations/                [S1]   core_/sys_/notify_/i18n_ (authored T-3*)
│   └── seeders/                   [S1]   Role/Permission seeders (T-5*)
├── lang/
│   └── en/                        [S1]   English baseline (D-014)
│       ├── auth.php validation.php pagination.php passwords.php
│       └── ics/                   [S1]   module string files
├── public/
│   ├── index.php                  [S1]   web entry (docroot target — D-039 SEC-02)
│   ├── build/                     [S1]   Vite output
│   └── (manifest.json, sw.js)     [future] PWA (D-005)
├── resources/
│   ├── css/app.css                [S1]   Tailwind entry (RTL-ready, T-8.2)
│   ├── js/
│   │   ├── app.js                 [S1]   Alpine.js bootstrap
│   │   └── bootstrap.js           [S1]
│   └── views/
│       ├── layouts/               [S1]   base layout (dir-aware, WCAG-ready)
│       ├── components/            [stub]
│       └── auth/                  [S1]   minimal auth views
├── routes/
│   ├── web.php                    [S1]
│   ├── api.php                    [S1]   (added by install:api)
│   └── console.php                [S1]   scheduler lives here in L11
├── storage/
│   ├── app/
│   │   ├── public/                [S1]   public disk (symlinked)
│   │   └── private/               [S1]   auth-gated files (D-024)
│   ├── framework/                 [S1]
│   └── logs/                      [S1]
├── tests/
│   ├── Feature/                   [S1]   auth, RBAC, audit feature tests
│   ├── Unit/                      [S1]
│   └── TestCase.php               [S1]
├── .env                           [S1]   NOT committed; lives OUTSIDE webroot in prod
├── .env.example                   [S1]   committed (Deliverable 4)
├── .gitignore                     [S1]   excludes .env, /vendor, /storage, /node_modules
├── composer.json                  [S1]   (Deliverable 3)
├── package.json                   [S1]   (Deliverable 3 — frontend)
├── phpunit.xml                    [S1]
├── pint.json                      [S1]   code style
├── phpstan.neon                   [S1]   larastan (boundary/static gates)
├── tailwind.config.js             [S1]   RTL-ready (logical utilities)
├── vite.config.js                 [S1]
└── artisan                        [S1]
```

**Laravel 11 notes (accuracy):**
- No `app/Http/Kernel.php` / `app/Console/Kernel.php`. Middleware, routing, and
  exceptions are configured in `bootstrap/app.php`.
- The scheduler is defined in `routes/console.php`.
- `php artisan install:api` creates `routes/api.php`, installs Sanctum, and wires
  the API — this is how the API layer (D-023) is enabled.
- Production document root points at `/public` only (D-039 SEC-02); the framework
  root (and `.env`) sit above it.

---

## DELIVERABLE 2 — PACKAGE SELECTION (with rationale)

Only packages needed for Sprint 1 scope (Core Platform). Business-module and
later-phase packages (Paystack SDK, S3 adapter, AI, search, Horizon) are
intentionally deferred to the sprint that needs them — keeping the dependency and
security surface minimal (Governance Golden Rule #5).

### Production (require)

| Package | Constraint | Purpose | Decision |
|---|---|---|---|
| php | ^8.3 | Runtime | D-002 |
| laravel/framework | ^11.0 | Core framework | D-020 |
| laravel/sanctum | ^4.0 | API token auth | D-021, D-023 |
| laravel/tinker | ^2.9 | REPL (ops/debug) | — |
| spatie/laravel-permission | ^6.0 | RBAC roles + permissions | D-021 |
| pragmarx/google2fa-laravel | ^2.2 | MFA / TOTP (admin MFA) | D-039, T-4.5 |
| bacon/bacon-qr-code | ^3.0 | TOTP QR provisioning | D-039, T-4.5 |

**Deliberately NOT included in Sprint 1** (added later, by the owning sprint):
- HIBP breach check → uses Laravel's **built-in** `Password::uncompromised()`
  validation rule (HIBP k-anonymity). No package needed (D-039 SEC, T-4.3).
- `league/flysystem-aws-s3-v3` → Phase 3 cloud storage only (D-024). Phase 1 = local.
- Paystack/Gemini/WhatsApp clients → use Laravel HTTP client in billing/AI sprints.
- `laravel/horizon` + Redis → VPS (D-037); shared uses the database queue driver.
- Meilisearch/Scout → Phase 2 search.

### Development (require-dev)

| Package | Constraint | Purpose |
|---|---|---|
| phpunit/phpunit | ^11.0 | Test framework (ships with L11) |
| mockery/mockery | ^1.6 | Mocking |
| fakerphp/faker | ^1.23 | Test data |
| nunomaduro/collision | ^8.0 | CLI error reporting |
| laravel/pint | ^1.13 | Code style (CI lint gate) |
| larastan/larastan | ^3.0 | Static analysis (module-boundary / quality gate, Governance §7) |
| laravel/sail | ^1.26 | Optional local Docker parity |

> Test framework note: PHPUnit 11 is the Laravel 11 default and is specified here.
> Pest 3 is an acceptable alternative if the team prefers it — flag at approval.

### Frontend (package.json — npm)

| Package | Purpose | Decision |
|---|---|---|
| vite + laravel-vite-plugin | Asset bundling | D-020 |
| tailwindcss, postcss, autoprefixer | Tailwind CSS (logical utilities → RTL-ready) | D-002, D-014, T-8.2 |
| @tailwindcss/forms | Accessible form styling (WCAG support) | D-028 |
| alpinejs | Reactive UI | D-002 |
| axios | HTTP (default) | — |

> RTL strategy (T-8.2): use Tailwind **logical** utilities (`ms-/me-`, `ps-/pe-`,
> `start/end`, `text-start/end`) + a `dir` attribute on `<html>`. No physical
> left/right utilities in base layout. A dedicated RTL plugin is **not required**;
> revisit only if a gap appears when Arabic (Phase 3) is built.

---

## DELIVERABLE 3 — COMPOSER & NPM DEPENDENCIES (manifests)

### composer.json (key sections — manifest, not code)

```jsonc
{
  "name": "ics/enterprise-platform",
  "type": "project",
  "require": {
    "php": "^8.3",
    "laravel/framework": "^11.0",
    "laravel/sanctum": "^4.0",
    "laravel/tinker": "^2.9",
    "spatie/laravel-permission": "^6.0",
    "pragmarx/google2fa-laravel": "^2.2",
    "bacon/bacon-qr-code": "^3.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "mockery/mockery": "^1.6",
    "fakerphp/faker": "^1.23",
    "nunomaduro/collision": "^8.0",
    "laravel/pint": "^1.13",
    "larastan/larastan": "^3.0",
    "laravel/sail": "^1.26"
  },
  "autoload": {
    "psr-4": { "App\\": "app/", "Database\\Factories\\": "database/factories/",
               "Database\\Seeders\\": "database/seeders/" }
  },
  "config": { "optimize-autoloader": true, "sort-packages": true },
  "scripts": {
    "post-autoload-dump": [
      "@php artisan package:discover --ansi"
    ]
  }
}
```

### Execution sequence (run ONLY after approval)

```
# 1. Create the project
composer create-project laravel/laravel ics-platform "11.*"

# 2. Add production packages
composer require laravel/sanctum:^4.0 spatie/laravel-permission:^6.0 \
  pragmarx/google2fa-laravel:^2.2 bacon/bacon-qr-code:^3.0

# 3. Add dev packages
composer require --dev larastan/larastan:^3.0 laravel/pint:^1.13

# 4. Enable the API layer (creates routes/api.php, wires Sanctum)
php artisan install:api

# 5. Publish Spatie permission config + migration stubs
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# 6. Frontend toolchain
npm install
npm install -D tailwindcss postcss autoprefixer @tailwindcss/forms
npm install alpinejs

# Production deploy build (no dev deps):
composer install --no-dev --optimize-autoloader
npm run build
```

> Versions are pinned at install (committed `composer.lock` / `package-lock.json`)
> so CI and deploys are reproducible (T-1.2 acceptance criteria).

---

## DELIVERABLE 4 — ENVIRONMENT VARIABLE SPECIFICATION

The single source of environment difference between shared hosting and VPS
(D-037). The `.env.example` below is committed; the real `.env` is never committed
and lives **outside the web root** in production (D-039 SEC-02).

### .env.example (committed template)

```ini
# ── Application ───────────────────────────────────────────────
APP_NAME="ICS Enterprise Platform"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://platform.ics.example
APP_TIMEZONE=UTC
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

# ── Logging ───────────────────────────────────────────────────
LOG_CHANNEL=stack
LOG_LEVEL=error

# ── Database ──  (engine pinned per Gate 0 result — LIM-03/M-3) ─
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

# ── Session ──  shared: file | VPS: redis ─────────────────────
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# ── Cache ──  shared: file (or apcu) | VPS: redis ─────────────
CACHE_STORE=file

# ── Queue ──  shared: database (cron) | VPS: redis (Horizon) ──
QUEUE_CONNECTION=database

# ── Filesystem ──  shared: local | Phase 3: s3 ───────────────
FILESYSTEM_DISK=local

# ── Mail (Brevo) + auth-critical fallback (D-039 SPOF-04) ─────
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@ics.example"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_FALLBACK_MAILER=
BREVO_API_KEY=

# ── Authentication / Sanctum ──────────────────────────────────
SANCTUM_STATEFUL_DOMAINS=platform.ics.example
SANCTUM_TOKEN_EXPIRATION=1440        # minutes (24h)

# ── Edge / Security (D-039) ───────────────────────────────────
CLOUDFLARE_ENABLED=true

# ── ICS Feature Flags (D-037) ──  all false on shared ─────────
ICS_WAREHOUSE_ETL_ENABLED=false
ICS_HEAVY_JOBS=false
ICS_AI_HIGH_VOLUME=false
ICS_COMMUNITY_SCALING=false

# ── ICS AI guardrails (used when AI sprints land) ─────────────
ICS_AI_DAILY_REQUEST_CAP=1000
ICS_AI_USER_HOURLY_CAP=20
ICS_AI_GUEST_SESSION_CAP=5

# ── Redis (VPS only — empty on shared) ────────────────────────
REDIS_HOST=
REDIS_PASSWORD=
REDIS_PORT=6379

# ── Deferred integrations (placeholders; later sprints) ───────
GEMINI_API_KEY=
GEMINI_MODEL=
PAYSTACK_PUBLIC_KEY=
PAYSTACK_SECRET_KEY=
PAYSTACK_WEBHOOK_SECRET=
WHATSAPP_API_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
```

### Variable reference (shared vs VPS — what differs at migration)

| Variable | Shared (Phase 1) | VPS (Phase 2) | Notes |
|---|---|---|---|
| SESSION_DRIVER | file | redis | off MySQL to spare connections (LIM-08, M-1) |
| CACHE_STORE | file / apcu | redis | M-1 |
| QUEUE_CONNECTION | database | redis | cron vs Horizon (D-037) |
| FILESYSTEM_DISK | local | local→s3 (P3) | config-only swap (D-024) |
| ICS_WAREHOUSE_ETL_ENABLED | false | true | LIM-09 |
| ICS_HEAVY_JOBS | false | true | — |
| ICS_AI_HIGH_VOLUME | false | true | LIM-10, COST-01 |
| ICS_COMMUNITY_SCALING | false | true | — |
| REDIS_* | empty | set | VPS only |

**The migration promise (D-037):** moving shared → VPS changes only the values in
the right column. No code, schema, or migration changes. This table IS the
config-only migration delta.

---

## DELIVERABLE 5 — CONFIGURATION STRATEGY

### 5.1 Driver resolution (D-037 guarantee #1)
Every infrastructure choice — queue, cache, session, filesystem, mail — is read
from `.env` via Laravel's config files. **No driver name is hardcoded in
application code.** The CI "hardcoded-driver" grep gate (T-2.2) fails the build if
a driver literal appears outside `config/`.

### 5.2 `config/ics.php` — the ICS control surface
A single custom config file holds all ICS-specific runtime switches, read only via
`config('ics.*')`:
```
ics.flags.warehouse_etl_enabled   ← ICS_WAREHOUSE_ETL_ENABLED
ics.flags.heavy_jobs              ← ICS_HEAVY_JOBS
ics.flags.ai_high_volume          ← ICS_AI_HIGH_VOLUME
ics.flags.community_scaling       ← ICS_COMMUNITY_SCALING
ics.ai.daily_request_cap          ← ICS_AI_DAILY_REQUEST_CAP
ics.ai.user_hourly_cap            ← ICS_AI_USER_HOURLY_CAP
ics.ai.guest_session_cap          ← ICS_AI_GUEST_SESSION_CAP
ics.edge.cloudflare_enabled       ← CLOUDFLARE_ENABLED
```
Deferred runtime behaviours are wrapped in `if (config('ics.flags.*'))` (D-037
guarantee #3). The flag decides; the code always ships.

### 5.3 Two environment profiles, one codebase
`.env.example` documents both profiles. Provisioning a host = copying the template
and setting the column of values for that environment. The application is identical
across both; only `.env` differs (Blueprint §15.5).

### 5.4 Secrets handling (D-039)
- `.env` is git-ignored and, in production, placed **above** the web root.
- `APP_KEY` generated per environment; never shared or committed.
- No secret value appears in `.env.example` (placeholders only).
- CI secrets scan blocks any committed secret (Governance §7).

### 5.5 Config & route caching
Production deploy runs `config:cache`, `route:cache`, `view:cache`. Because all
behaviour is config/flag-driven (not hardcoded), cached config is safe and a flag
change is applied by re-caching — still configuration only.

### 5.6 Localization config (D-014)
`APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en` in Phase 1. The i18n DB table exists but
is unused until French (Phase 2). Locale detection order (T-8.1): user preference →
session → `Accept-Language` → `en`.

### 5.7 Database engine alignment (LIM-03 / M-3)
`DB_CONNECTION=mysql` works for both MySQL 8 and MariaDB. The **actual** Gate 0
engine/version is recorded in the Limitations Register and local + staging are
pinned to match before any migration is authored (T-2.4). utf8mb4 / InnoDB are set
in `config/database.php`.

---

## APPROVAL REQUIRED (T-1.2 — Governance §8)

The following need sign-off before the execution sequence (Deliverable 3) runs:

| Item | Approver | Decision needed |
|---|---|---|
| Production package list | Lead Architect | Confirm the 5 prod packages + deferral list |
| Test framework | Lead Architect / Tech Lead | PHPUnit 11 (default) vs Pest 3 |
| DB engine value | Lead Architect | Record actual Gate 0 engine; pin envs (feeds T-2.4) |

On approval, what executes (Task 1 only):
1. `composer create-project` + the install sequence (Deliverable 3)
2. Commit folder structure, `composer.json`/`composer.lock`, `package.json`/lock,
   `.env.example`, `config/ics.php`, `tailwind.config.js`, `phpstan.neon`, `pint.json`
3. App boots; `php artisan --version` = 11.x; `.env` git-ignored (T-1.1 DoD)

Task 1 produces **no** migrations, models, controllers, or business logic — those
are Tasks 3+ and remain gated.

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval.
**Per instruction: Task 1 only. No work proceeds beyond the Laravel 11 scaffold
until this specification is approved.**
