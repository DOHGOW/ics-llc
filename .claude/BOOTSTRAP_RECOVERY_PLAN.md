# BOOTSTRAP RECOVERY PLAN
# ICS Enterprise Ecosystem Platform — Overlay → Runnable Laravel App (D-049 Recovery, Sections A & B)

Version: 1.0
Date: 2026-06-05
Status: Analysis/plan only — NO code, migrations, or merges performed here.
Author: Lead Architect
Pairs with: ENVIRONMENT_REMEDIATION_PLAN.md, GREEN_CI_EXECUTION_PLAN.md, BLOCKER_RESOLUTION_MATRIX.md

> Objective: convert the ICS **overlay** into a bootable Laravel 11 application **without overwriting any
> existing ICS file**. Strategy: **additive skeleton-into-overlay** — generate a pristine skeleton, then
> copy ONLY the files the overlay lacks. The overlay is the source of truth.

---

## INVENTORY — what the overlay HAS vs what the skeleton must ADD

### Present (overlay-owned — NEVER overwrite)
```
app/                         config/auth.php   config/cache.php   config/ics.php
bootstrap/app.php            config/locales.php config/mail.php   config/queue.php
bootstrap/providers.php      config/security.php config/session.php
database/migrations/ (80)    database/seeders/ (DatabaseSeeder, Permission, RolePermission, Role)
routes/ (auth,billing,cms,community,console,crm,library,marketplace,membership,
         portal,program,startup,tenant,training,web)
tests/ (10 suites) + phpunit.xml
resources/ (css/app.css, js/app.js, js/bootstrap.js, views/{components,layouts})
lang/  scripts/ci/  .github/workflows/ci.yml  .env.example
package.json  vite.config.js  tailwind.config.js  postcss.config.js
phpstan.neon  pint.json  composer.json  docker-compose.yml  README.md  .gitignore
```

### Missing (skeleton must ADD — these are the B3/B11/B12 gaps)
```
artisan                                   ← console entrypoint (B3)
public/index.php  public/.htaccess  public/favicon.ico  public/robots.txt   (B3)
bootstrap/cache/  (+ .gitignore)          ← compiled cache dir (B3)
storage/framework/{cache/data,sessions,views}/  storage/logs/   (B3)
database/factories/UserFactory.php (+ dir) ← tests call User::factory() (B11)
config/database.php  config/app.php  config/filesystems.php
config/logging.php   config/services.php   (B12)
config/sanctum.php   config/permission.php  ← vendor:publish (B12)
composer.lock                              ← generate + commit (B7)
vendor/                                    ← composer install (B1/B2)
public/build/                              ← npm run build (B5)
```

### Anomaly
- `src/` exists but is **empty** and non-standard for Laravel — recommend REMOVE (or leave ignored); it is
  not part of the PSR-4 autoload (`App\\` → `app/`).

---

## A. LARAVEL BOOTSTRAP RECOVERY — exact sequence

> Run on the CI-target runtime (PHP 8.3 + intl). Performed in a temp dir to avoid touching the repo, then
> additive-copy back. **Pre-req:** clean git working tree + a recovery branch (e.g., `chore/bootstrap-recovery`).

### A.1 Generate a pristine skeleton (temp, throwaway)
```
composer create-project laravel/laravel:^11.0 /tmp/ics-skel   # PHP 8.3
```
- This yields the canonical Laravel 11 root: `artisan`, `public/`, `bootstrap/cache/`,
  `storage/framework/*`, `database/factories/UserFactory.php`, the standard `config/*.php` set.

### A.2 Additive copy — skeleton → overlay (NEVER overwrite)
Copy ONLY paths the overlay lacks (use `cp -n` / `rsync --ignore-existing`, then **audit the diff**):
```
artisan
public/                       (index.php, .htaccess, favicon, robots)
bootstrap/cache/              (with its .gitignore)
storage/framework/...         storage/logs/   (preserve overlay storage/app/)
config/database.php  config/app.php  config/filesystems.php  config/logging.php  config/services.php
database/factories/           (UserFactory.php — to be reconciled, see A.5)
```
Rule: if a file exists in the overlay, **keep the overlay's** (it is intentional). `--ignore-existing`
guarantees this; still review `git status` to confirm no ICS file changed.

### A.3 Reconcile `bootstrap/app.php`
- The overlay's `bootstrap/app.php` is **authoritative** (it wires all 14 route groups + middleware +
  TenantScope). Do NOT replace it with the skeleton's. Verify it still references the skeleton's
  `withRouting`/`withMiddleware`/`withExceptions` shape (it does).

### A.4 Publish vendor config (after deps install)
```
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```
- Produces `config/sanctum.php`, `config/permission.php` (+ spatie migration if not already in the 80).
  **Check:** confirm spatie's permission tables are covered by existing migrations to avoid duplicates.

### A.5 Reconcile `UserFactory` to the ICS schema (B11)
- The skeleton's default `UserFactory` targets `users`; the ICS model maps to **`core_users`** with extra
  columns (tenant_id, account_id, status, MFA fields). Update the factory to produce valid `core_users`
  rows and ensure `User` uses `HasFactory`. **Scope note:** this is test scaffolding required for
  verification — NOT a feature/module change.

### A.6 Environment + keys
```
cp .env.example .env
php artisan key:generate
# set DB_CONNECTION=mysql, DB_* for MySQL 8 (or use docker-compose)
```

### A.7 Validate bootstrap integrity
```
php artisan about           # boots framework, lists drivers
php artisan route:list      # expect auth/cms/crm/portal/library/training/community/
                            #   marketplace/startup/program/tenant/billing/membership groups
php artisan config:show database   # mysql + sqlite connections present
```
- **Pass:** all three succeed; every route group resolves; providers (incl. TenancyServiceProvider) load.

### A.8 Commit the recovered skeleton (additive only)
- `git add` the skeleton-only files (artisan, public/, configs, factories, bootstrap/cache/.gitignore,
  storage/framework/.gitignore). Confirm `git diff` shows **no modification** to ICS overlay files.

---

## B. DEPENDENCY RESOLUTION — exact investigation

> "Do not assume versions" — the steps below **determine** the required changes empirically on PHP 8.3.

### B.1 Reproduce on the correct runtime
```
php -v   # must be 8.3.x first (B1)
composer update -W 2>&1 | tee /tmp/composer-update.log
```
- The PHP-platform error disappears on 8.3. What remains is the framework/security-advisory resolution.

### B.2 Enumerate the security advisories precisely
```
composer audit --format=plain
```
- Record EXACT advisory IDs + affected ranges for `laravel/framework` (and transitive deps). Do not guess —
  read what Composer reports as fixed-in.

### B.3 Decide the remediation per advisory (REQUIRES DECISION)
For each reported advisory, choose ONE, with written justification:
- **(a) Raise floor:** bump `laravel/framework` constraint to the patched release Composer cites as fixed
  (keep within `^11`). This is the default, preferred path.
- **(b) Triage-ignore:** if the advisory does not apply to the resolved version (false positive / historical),
  add it to `config.audit.ignore` in `composer.json` with a reason + ticket. Keep `block-insecure` ON.
- **Policy:** for production, `composer audit` SHOULD hard-fail on un-triaged advisories (flip the ci.yml
  `|| true` to hard-fail once triaged — currently report-only).

### B.4 Lockfile strategy (B7)
```
composer update            # resolves clean on 8.3 after B.3
composer install --dry-run # must succeed from the new lock
git add composer.lock
```
- Commit `composer.lock`. CI thereafter runs `composer install` (lock-faithful, reproducible).

### B.5 `composer audit` expectations
- After B.3/B.4: `composer audit` exit 0 (or only approved, documented ignores). This is the gate that
  makes ci.yml step "Dependency vulnerability audit" trustworthy (and eligible to become hard-fail).

---

## VALIDATION INTEGRITY CHECKS (bootstrap done)

| Check | Command | Pass |
|---|---|---|
| Framework boots | `php artisan about` | no errors; drivers listed |
| Routes registered | `php artisan route:list` | all 14 groups present |
| Providers load | (implicit in `about`) | Tenancy/Auth/Event/RateLimit/App ok |
| DB config | `php artisan config:show database` | mysql + sqlite |
| Migrate (MySQL 8) | `php artisan migrate --seed` | 80 migrations apply; RBAC + root tenant seeded |
| Factory | `php artisan tinker` → `User::factory()->make()` | valid core_users attributes |
| No overlay drift | `git diff --name-only` | only skeleton-only additions |

---

## OUTPUT OF THIS PLAN

A repeatable, **conflict-free** path: pristine skeleton → additive merge (overlay wins) → vendor publish →
factory/config reconciliation → install + lock → migrate/seed → integrity validation. No ICS overlay file is
modified; the result is a bootable Laravel 11 app ready for the GREEN-CI run (see GREEN_CI_EXECUTION_PLAN).
