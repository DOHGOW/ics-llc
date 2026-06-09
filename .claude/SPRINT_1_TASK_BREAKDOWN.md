# SPRINT 1 — TASK BREAKDOWN
# ICS Enterprise Ecosystem Platform — Core Platform Foundation

Version: 1.0
Date: 2026-05-30
Status: Awaiting Approval (no code until approved)
Owner: Technical Lead
Governing: IMPLEMENTATION_GOVERNANCE.md · PHASE_1_SPRINT_1_IMPLEMENTATION_PLAN.md
Decision References: D-002, D-006, D-014, D-020, D-021, D-022, D-024, D-027,
D-028, D-037, D-038, D-039

---

## ENTERPRISE COMPLIANCE CHECK (attestation)

Performed before generating this breakdown, per IMPLEMENTATION_GOVERNANCE §1.

| Validation | Result |
|---|---|
| Decision set integrity (D-001 → D-040, no gaps/dupes) | PASS |
| Open decisions outstanding | NONE |
| Orphaned "OPEN DECISIONS" header before D-033 | FOUND → CORRECTED |
| Stack consistency (D-002 ↔ D-020: Blade refines HTML5 for server views) | CONSISTENT |
| Access patterns (D-034 hierarchical + D-036 lateral via one ContentAccessService, D-038) | CONSISTENT |
| Community CTI (D-035) + 14-role model (D-021) retained (D-040) | CONSISTENT |
| VPS-ready/shared-deployable (D-037) keeps DW/i18n/tenant intact (D-032/D-014/D-004) | CONSISTENT |
| WCAG 2.1 AA (D-028) amends compliance (D-006) | CONSISTENT |
| Sprint 1 scope = Core Platform Level 0 (MODULE_DEPENDENCY_DIAGRAM) | CONSISTENT |
| Sprint 1 builds only core_*/sys_*/notify_*/i18n_ tables (not all 119) | CONSISTENT |

**Residual item carried into Sprint 1 (not a blocker):**
The actual production DB engine/version (MySQL 8 vs MariaDB — LIM-03) must be
recorded from the Gate 0 results and local/staging pinned to match (task T-2.4 / M-3).

**Compliance verdict: PASS — internally consistent. Sprint 1 may be planned to task level.**

---

## SPRINT OVERVIEW

| Field | Value |
|---|---|
| Sprint | Phase 1 · Sprint 1 — Core Platform |
| Goal | Deployable, secured, authenticated, role-aware skeleton; zero business modules |
| Length | 10 working days (≈ 34 ideal-dev-days of effort; team-sized) |
| Scope areas | 9 (scaffold, env, DB, auth, RBAC, audit, user mgmt, i18n, security) |
| Out of scope | All business modules; any dw_ ETL; FR/AR content; PWA polish |
| Exit | Sprint Done gate (GATE 2) per IMPLEMENTATION_GOVERNANCE §4 |

Effort scale: ideal-dev-days (idd). 0.5 = half day. Points in parentheses (Fibonacci).

---

## TASK INDEX

| ID | Task | Area | Effort | Approval? |
|---|---|---|---|---|
| T-1.1 | Initialize Laravel 11 project + repository | Scaffold | 0.5 (2) | — |
| T-1.2 | Install & pin core packages | Scaffold | 0.5 (2) | ⚠ pkg list |
| T-1.3 | Folder structure & namespaces | Scaffold | 0.5 (2) | — |
| T-2.1 | Feature-flag config + .env profiles | Env | 1.0 (3) | — |
| T-2.2 | Driver config audit + hardcoded-driver CI gate | Env | 1.0 (3) | — |
| T-2.3 | CI pipeline (gates, tests, build) | Env | 1.0 (3) | — |
| T-2.4 | Pin DB engine to production (LIM-03) | Env | 0.5 (2) | ⚠ Architect |
| T-3.1 | Migration set: core_tenants, core_users | DB | 1.0 (3) | ⚠ Architect |
| T-3.2 | Migration set: Spatie + Sanctum tables | DB | 0.5 (2) | ⚠ Architect |
| T-3.3 | Migration: core_audit_logs + enforcement | DB | 1.0 (3) | ⚠ Arch+Sec |
| T-3.4 | Migration: consent + retention policy | DB | 0.5 (2) | ⚠ Architect |
| T-3.5 | Migration: sys_* (jobs/cache/sessions) | DB | 0.5 (2) | ⚠ Architect |
| T-3.6 | Migration: notifications + notify_* | DB | 0.5 (2) | ⚠ Architect |
| T-3.7 | Migration: i18n_translations | DB | 0.5 (2) | ⚠ Architect |
| T-3.8 | Schema conformance verification | DB | 0.5 (2) | — |
| T-4.1 | Web session authentication | Auth | 1.0 (3) | — |
| T-4.2 | Sanctum API token auth | Auth | 1.0 (3) | — |
| T-4.3 | Password policy + HIBP breach check | Auth | 1.0 (3) | ⚠ Security |
| T-4.4 | Login throttle + lockout | Auth | 0.5 (2) | — |
| T-4.5 | MFA (TOTP) scaffold | Auth | 1.5 (5) | ⚠ Security |
| T-4.6 | Session hardening | Auth | 0.5 (2) | ⚠ Security |
| T-4.7 | GDPR data export + deletion | Auth | 1.0 (3) | ⚠ Security |
| T-5.1 | Role seeder (14 roles) | RBAC | 0.5 (2) | ⚠ Arch+Sec |
| T-5.2 | Permission seeder (catalogue) | RBAC | 1.0 (3) | ⚠ Arch+Sec |
| T-5.3 | Role→permission mapping seeder | RBAC | 1.0 (3) | ⚠ Arch+Sec |
| T-5.4 | Gates/Policies base + default-deny | RBAC | 1.0 (3) | ⚠ Architect |
| T-5.5 | Role lifecycle events | RBAC | 0.5 (2) | — |
| T-6.1 | Audit service (app-layer immutable) | Audit | 1.0 (3) | ⚠ Arch+Sec |
| T-6.2 | Off-box audit export hook | Audit | 0.5 (2) | ⚠ Security |
| T-6.3 | Wire audit to core events | Audit | 0.5 (2) | — |
| T-7.1 | User management (admin CRUD + status) | User | 1.0 (3) | — |
| T-7.2 | Role assignment flow | User | 0.5 (2) | ⚠ Security |
| T-8.1 | i18n foundation + locale detection | i18n | 1.0 (3) | — |
| T-8.2 | RTL-ready layout scaffolding | i18n | 0.5 (2) | — |
| T-9.1 | Security headers middleware | Security | 0.5 (2) | ⚠ Security |
| T-9.2 | Rate limiting middleware | Security | 0.5 (2) | — |
| T-9.3 | CSRF + secure cookie config | Security | 0.5 (2) | — |
| T-9.4 | Cloudflare front + .env isolation verify | Security | 0.5 (2) | ⚠ DevOps |
| T-10.1 | Test suite baseline | Quality | 1.5 (5) | — |
| T-10.2 | Staging deploy + cron/queue verify | Quality | 1.0 (3) | ⚠ DevOps |
| T-10.3 | Sprint security pass + DoD verification | Quality | 1.0 (3) | ⚠ Arch+Sec |

⚠ = requires sign-off before execution (see Section "Approval-Required Tasks").

---

## DETAILED TASKS

> NOTE: "Deliverables" name the artifacts a task produces (e.g., a migration file).
> Per this request, this document does NOT contain the code, migrations, models, or
> controllers themselves — only the plan for them.

---

### AREA 1 — LARAVEL 11 PROJECT SCAFFOLD

#### T-1.1 — Initialize Laravel 11 project + repository
- **Decision refs:** D-020 · **Depends on:** Gate 0 APPROVED
- **Effort:** 0.5 idd
- **Deliverables:** Laravel 11 app skeleton; Git repo; GitHub Flow branches; `.gitignore` excluding `.env`, `/vendor`, `/storage`.
- **Acceptance criteria:** App boots locally; `php artisan --version` = 11.x; repo clean; `main`/`staging` branches exist.
- **Security checkpoint:** `.env` confirmed git-ignored; `APP_DEBUG=false` default for non-local.
- **Testing:** Smoke: default route returns 200 locally.
- **Rollback:** Delete branch/repo; no data yet.
- **DoD:** Boots; repo initialized; branching in place; CI placeholder runs.

#### T-1.2 — Install & pin core packages  ⚠ APPROVAL (package list)
- **Decision refs:** D-020, D-021, D-022 · **Depends on:** T-1.1
- **Effort:** 0.5 idd
- **Deliverables:** Composer requires (pinned versions): `laravel/sanctum`, `spatie/laravel-permission`, TOTP lib (e.g. `pragmarx/google2fa` + QR), HIBP check approach. `composer.lock` committed.
- **Acceptance criteria:** All packages install on the confirmed PHP/engine; `--no-dev` install succeeds (deploy path).
- **Security checkpoint:** Dependency scan clean; no abandoned packages.
- **Testing:** CI installs from lock file reproducibly.
- **Rollback:** Revert composer changes.
- **DoD:** Approved package list installed, pinned, scanned, lock committed.
- **Why approval:** Package selection affects security surface — Architect signs the final list.

#### T-1.3 — Folder structure & namespaces
- **Decision refs:** Blueprint §3.1/§3.3 · **Depends on:** T-1.1
- **Effort:** 0.5 idd
- **Deliverables:** Module-oriented directory + namespace skeleton (App\Services, App\Events\Core, App\Listeners, App\Models\Core, etc.); naming-convention doc link in README.
- **Acceptance criteria:** Structure matches Blueprint §3.1; autoload resolves.
- **Security checkpoint:** No secrets in structure; storage paths outside webroot.
- **Testing:** Autoload + a trivial class resolve test.
- **Rollback:** Revert commit.
- **DoD:** Structure conforms to Blueprint; documented; autoloads.

---

### AREA 2 — ENVIRONMENT CONFIGURATION

#### T-2.1 — Feature-flag config + .env profiles
- **Decision refs:** D-037 · **Depends on:** T-1.3
- **Effort:** 1.0 idd
- **Deliverables:** `config/ics.php` (flags: warehouse ETL, heavy jobs, AI high-volume, community scaling); `.env.example` for shared + VPS profiles (Blueprint §15.4).
- **Acceptance criteria:** Flags read via `config('ics.*')`; shared profile sets all deferred flags false.
- **Security checkpoint:** No secret values in `.env.example`; placeholders only.
- **Testing:** Config cache builds; flag values assert correctly per profile.
- **Rollback:** Revert config commit.
- **DoD:** Both profiles documented; flags consumed via config; D-037 guarantee #3 in place.

#### T-2.2 — Driver config audit + hardcoded-driver CI gate
- **Decision refs:** D-037 (guarantee #1) · **Depends on:** T-2.1
- **Effort:** 1.0 idd
- **Deliverables:** Confirm queue/cache/session/filesystem/mail resolved from `.env`; CI grep gate failing on driver literals outside `config/`.
- **Acceptance criteria:** A planted hardcoded driver literal fails CI; clean code passes.
- **Security checkpoint:** No driver/secret literals in app code.
- **Testing:** CI negative test (planted violation) + positive (clean).
- **Rollback:** Disable gate (not permitted post-merge); revert.
- **DoD:** Gate live in CI; proven to catch violations; documented.

#### T-2.3 — CI pipeline
- **Decision refs:** IMPLEMENTATION_GOVERNANCE §7 · **Depends on:** T-2.2
- **Effort:** 1.0 idd
- **Deliverables:** CI: lint, hardcoded-driver gate, test run, secrets scan, build `vendor/` for deploy.
- **Acceptance criteria:** Pipeline green on a trivial commit; red on a failing test.
- **Security checkpoint:** Secrets scan active; CI secrets stored securely.
- **Testing:** Pipeline self-test commit.
- **Rollback:** Revert pipeline config.
- **DoD:** All §7 gates wired (those applicable to Sprint 1); green build required to merge.

#### T-2.4 — Pin DB engine to production  ⚠ APPROVAL (Architect)
- **Decision refs:** D-002, LIM-03, M-3 · **Depends on:** Gate 0 results
- **Effort:** 0.5 idd
- **Deliverables:** Record actual prod engine/version (MySQL 8 vs MariaDB x.y) in Limitations Register (CONFIRMED); pin local + staging to match.
- **Acceptance criteria:** Local/staging/prod run the same engine/major version; JSON + FULLTEXT + FK probes pass on it.
- **Security checkpoint:** n/a.
- **Testing:** Run the C4/C5 probe equivalents against local engine.
- **Rollback:** Re-pin to corrected version.
- **DoD:** Engine confirmed, registered, environments aligned; Architect signs.
- **Why approval:** Engine choice affects every migration (MariaDB JSON/FULLTEXT nuances).

---

### AREA 3 — CORE DATABASE FOUNDATION

> All Area 3 tasks are migration-authoring tasks. Per this request the migrations
> are NOT written here. Each is **⚠ APPROVAL (Architect)** per governance §8
> (schema changes), and T-3.3 is **⚠ Arch + Security** (audit/PII).

#### T-3.1 — Migration set: core_tenants, core_users  ⚠ APPROVAL (Architect)
- **Decision refs:** D-004, DATABASE_BLUEPRINT · **Depends on:** T-1.3, T-2.4
- **Effort:** 1.0 idd
- **Deliverables:** Migration specs for `core_tenants`, `core_users` exactly per DATABASE_BLUEPRINT (tenant_id nullable, indexes, utf8mb4, InnoDB).
- **Acceptance criteria:** Migrate up/down clean on pinned engine; columns/indexes match blueprint byte-for-byte (names/types).
- **Security checkpoint:** `password` column for bcrypt hash; `mfa_secret` encrypted-at-rest plan noted; no plaintext secrets.
- **Testing:** Migration up/down; schema assertion test vs blueprint.
- **Rollback:** `migrate:rollback` (no production data in Sprint 1).
- **DoD:** Tables created per blueprint; reversible; schema test green; Architect signs.

#### T-3.2 — Migration set: Spatie + Sanctum  ⚠ APPROVAL (Architect)
- **Decision refs:** D-021, DATABASE_BLUEPRINT · **Depends on:** T-3.1
- **Effort:** 0.5 idd
- **Deliverables:** Spatie permission tables + `personal_access_tokens` migration specs.
- **Acceptance criteria:** Tables match Spatie/Sanctum + blueprint; FKs valid on engine.
- **Security checkpoint:** Token table indexes; abilities column present.
- **Testing:** Migration up/down; FK enforcement probe.
- **Rollback:** Rollback.
- **DoD:** Auth/RBAC tables present, reversible, conformant; Architect signs.

#### T-3.3 — Migration: core_audit_logs + enforcement  ⚠ APPROVAL (Arch + Security)
- **Decision refs:** D-006, D-039 (SEC-03), LIM-05 · **Depends on:** T-3.1
- **Effort:** 1.0 idd
- **Deliverables:** `core_audit_logs` (append-only) migration; enforcement decision recorded — DB TRIGGER **only if** Gate 0 confirmed TRIGGER privilege, else app-layer write-only repository (default).
- **Acceptance criteria:** Insert works; UPDATE/DELETE blocked by chosen mechanism; matches blueprint.
- **Security checkpoint:** Immutability proven; actor/IP/UA captured; no PII beyond IDs in hashes.
- **Testing:** Attempt UPDATE/DELETE → rejected; insert → succeeds.
- **Rollback:** Rollback table.
- **DoD:** Append-only proven by test; enforcement path chosen + documented; Arch + Security sign.

#### T-3.4 — Migration: consent + retention  ⚠ APPROVAL (Architect)
- **Decision refs:** D-006 · **Depends on:** T-3.1
- **Effort:** 0.5 idd
- **Deliverables:** `core_consent_logs`, `core_retention_policies` migration specs.
- **Acceptance criteria:** Match blueprint; FKs valid.
- **Security checkpoint:** Consent timestamp + policy version captured (NDPA/GDPR).
- **Testing:** Migration up/down.
- **Rollback:** Rollback.
- **DoD:** Tables present, reversible, conformant; Architect signs.

#### T-3.5 — Migration: sys_* (jobs/cache/sessions)  ⚠ APPROVAL (Architect)
- **Decision refs:** D-037, DATABASE_BLUEPRINT · **Depends on:** T-3.1
- **Effort:** 0.5 idd
- **Deliverables:** `sys_jobs`, `sys_failed_jobs`, `sys_sessions`, `sys_cache` migration specs.
- **Acceptance criteria:** Match blueprint; usable by database queue/session/cache drivers.
- **Security checkpoint:** Session table indexed; no sensitive payload exposure.
- **Testing:** Queue driver writes/reads a job; session persists.
- **Rollback:** Rollback.
- **DoD:** Infra tables present; queue/session/cache operate on them; Architect signs.

#### T-3.6 — Migration: notifications + notify_*  ⚠ APPROVAL (Architect)
- **Decision refs:** D-022 · **Depends on:** T-3.1
- **Effort:** 0.5 idd
- **Deliverables:** `notifications`, `notify_preferences`, `notify_push_subscriptions` specs.
- **Acceptance criteria:** Match blueprint; preferences enforce channel toggles.
- **Security checkpoint:** Push keys stored safely; no PII leakage in data column.
- **Testing:** Migration up/down; insert a DB notification.
- **Rollback:** Rollback.
- **DoD:** Notification tables present, conformant; Architect signs.

#### T-3.7 — Migration: i18n_translations  ⚠ APPROVAL (Architect)
- **Decision refs:** D-014, D-037 · **Depends on:** T-3.1
- **Effort:** 0.5 idd
- **Deliverables:** `i18n_translations` migration (present, unused in Phase 1).
- **Acceptance criteria:** Matches blueprint; unique key on (type,id,locale,field).
- **Security checkpoint:** n/a.
- **Testing:** Migration up/down.
- **Rollback:** Rollback.
- **DoD:** Table present for future FR/AR; conformant; Architect signs.

#### T-3.8 — Schema conformance verification
- **Decision refs:** IMPLEMENTATION_GOVERNANCE §7 · **Depends on:** T-3.1…T-3.7
- **Effort:** 0.5 idd
- **Deliverables:** Automated schema-vs-blueprint assertion (table/column/index names).
- **Acceptance criteria:** All Sprint 1 tables match DATABASE_BLUEPRINT; gate green.
- **Security checkpoint:** No unexpected/orphan tables.
- **Testing:** Conformance test in CI.
- **Rollback:** n/a (read-only check).
- **DoD:** Conformance gate green; no orphan tables.

---

### AREA 4 — AUTHENTICATION

#### T-4.1 — Web session authentication
- **Decision refs:** D-021 · **Depends on:** T-3.2
- **Effort:** 1.0 idd
- **Deliverables:** Session login/logout flow; E-CORE-002/003 dispatch.
- **Acceptance criteria:** Valid creds log in; invalid rejected; logout clears session.
- **Security checkpoint:** Session driver = file/db (not exposed); regenerate on login.
- **Testing:** Feature tests: login success/fail, logout.
- **Rollback:** Revert; no data impact.
- **DoD:** Web auth works; events fire; tests green.

#### T-4.2 — Sanctum API token auth
- **Decision refs:** D-021, D-023 · **Depends on:** T-3.2
- **Effort:** 1.0 idd
- **Deliverables:** API login issuing Sanctum token; bearer-protected route group; token revocation.
- **Acceptance criteria:** Token grants access; revoked token denied; expiry honored.
- **Security checkpoint:** Token abilities scoped; expiry set; httpOnly refresh where used.
- **Testing:** Feature tests: token issue/use/revoke/expire.
- **Rollback:** Revert.
- **DoD:** API auth works; revocation/expiry tested.

#### T-4.3 — Password policy + HIBP breach check  ⚠ APPROVAL (Security)
- **Decision refs:** D-039, Blueprint §14 · **Depends on:** T-4.1
- **Effort:** 1.0 idd
- **Deliverables:** Policy (≥12 chars, complexity, bcrypt cost 12, history); HIBP k-anonymity check on set/change.
- **Acceptance criteria:** Weak/breached passwords rejected; bcrypt cost verified.
- **Security checkpoint:** HIBP via k-anonymity (no full hash sent); no plaintext logged.
- **Testing:** Tests: weak rejected, breached rejected, valid accepted.
- **Rollback:** Revert policy.
- **DoD:** Policy enforced; HIBP integrated safely; Security signs.

#### T-4.4 — Login throttle + lockout
- **Decision refs:** D-039, E-CORE-005 · **Depends on:** T-4.1
- **Effort:** 0.5 idd
- **Deliverables:** 5-attempt lockout w/ exponential backoff; E-CORE-005 + alert email.
- **Acceptance criteria:** 6th attempt locked; lockout email sent; backoff applies.
- **Security checkpoint:** Lockout keyed safely (no user enumeration); IP logged.
- **Testing:** Tests: lockout triggers; alert dispatched.
- **Rollback:** Revert.
- **DoD:** Lockout works; event + alert fire; tested.

#### T-4.5 — MFA (TOTP) scaffold  ⚠ APPROVAL (Security)
- **Decision refs:** D-039, Role Matrix (admin MFA required) · **Depends on:** T-4.1
- **Effort:** 1.5 idd
- **Deliverables:** TOTP enrol/verify; QR provisioning; admin roles require MFA, others optional.
- **Acceptance criteria:** Admin cannot complete login without MFA once enrolled; codes validate.
- **Security checkpoint:** `mfa_secret` encrypted at rest; recovery codes hashed.
- **Testing:** Tests: enrol, verify, admin enforcement.
- **Rollback:** Disable MFA flag; revert.
- **DoD:** MFA scaffold works; admin enforcement proven; Security signs.

#### T-4.6 — Session hardening  ⚠ APPROVAL (Security)
- **Decision refs:** D-039, Blueprint §14 · **Depends on:** T-4.1
- **Effort:** 0.5 idd
- **Deliverables:** httpOnly + Secure + SameSite=Strict cookies; session regeneration on privilege change.
- **Acceptance criteria:** Cookie flags present; session id changes on role change/login.
- **Security checkpoint:** Verified via response headers; no session fixation.
- **Testing:** Tests/inspection of cookie flags + regeneration.
- **Rollback:** Revert config.
- **DoD:** Hardening verified; Security signs.

#### T-4.7 — GDPR data export + deletion  ⚠ APPROVAL (Security)
- **Decision refs:** D-006, E-CORE-009/010 · **Depends on:** T-3.1, T-4.1
- **Effort:** 1.0 idd
- **Deliverables:** Self-service data export (JSON) + deletion (soft delete + PII nullify); events + audit.
- **Acceptance criteria:** Export returns own data; deletion nullifies PII, preserves anonymized audit.
- **Security checkpoint:** Authenticated + rate-limited + audited; token revocation on deletion.
- **Testing:** Tests: export content; deletion nullifies + revokes tokens.
- **Rollback:** Revert endpoints.
- **DoD:** NDPA/GDPR flows work + audited; Security signs.

---

### AREA 5 — RBAC

#### T-5.1 — Role seeder (14 roles)  ⚠ APPROVAL (Arch + Security)
- **Decision refs:** D-021, USER_ROLE_MATRIX · **Depends on:** T-3.2
- **Effort:** 0.5 idd
- **Deliverables:** Seeder creating exactly the 14 roles (R-01…R-14).
- **Acceptance criteria:** 14 roles present, named per matrix; idempotent seeder.
- **Security checkpoint:** No default super-admin with blank password; super-admin creation controlled.
- **Testing:** Test: role count + names.
- **Rollback:** Re-seed/rollback.
- **DoD:** Roles seeded per matrix; Arch + Security sign.

#### T-5.2 — Permission seeder (catalogue)  ⚠ APPROVAL (Arch + Security)
- **Decision refs:** D-021, PERMISSION_MATRIX · **Depends on:** T-3.2
- **Effort:** 1.0 idd
- **Deliverables:** Seeder for the full permission catalogue (naming `{action}.{module}.{scope}`).
- **Acceptance criteria:** Catalogue matches PERMISSION_MATRIX; idempotent.
- **Security checkpoint:** No accidental wildcard/over-grant.
- **Testing:** Test: permission set matches matrix.
- **Rollback:** Re-seed/rollback.
- **DoD:** Catalogue seeded; matches matrix; Arch + Security sign.

#### T-5.3 — Role→permission mapping seeder  ⚠ APPROVAL (Arch + Security)
- **Decision refs:** D-021, PERMISSION_MATRIX · **Depends on:** T-5.1, T-5.2
- **Effort:** 1.0 idd
- **Deliverables:** Seeder mapping each role to its permissions per the matrix grids.
- **Acceptance criteria:** Every role's grant set equals the matrix; least-privilege (no extras).
- **Security checkpoint:** Separation of duties preserved (e.g., Content cannot reach CRM).
- **Testing:** Test per role: granted == matrix; denied == not in matrix.
- **Rollback:** Re-seed/rollback.
- **DoD:** Mapping matches matrix exactly; Arch + Security sign.

#### T-5.4 — Gates/Policies base + default-deny  ⚠ APPROVAL (Architect)
- **Decision refs:** D-021 · **Depends on:** T-5.3
- **Effort:** 1.0 idd
- **Deliverables:** Authorization middleware + base Policy pattern; default-deny.
- **Acceptance criteria:** No permission → denied; correct permission → allowed; server-side only.
- **Security checkpoint:** Frontend visibility never used for authz; ownership checks scaffolded.
- **Testing:** Tests: deny by default; allow with permission; ownership.
- **Rollback:** Revert.
- **DoD:** Default-deny proven; Architect signs.

#### T-5.5 — Role lifecycle events
- **Decision refs:** D-021, E-CORE-006/007 · **Depends on:** T-5.1
- **Effort:** 0.5 idd
- **Deliverables:** RoleAssigned/RoleRevoked events + listeners (notify, audit); effect on next session refresh.
- **Acceptance criteria:** Role change fires event, audits, notifies; applies next refresh.
- **Security checkpoint:** Privilege change audited; session regenerated (links T-4.6).
- **Testing:** Tests: assign/revoke fire events + audit.
- **Rollback:** Revert.
- **DoD:** Lifecycle events work + audited; tested.

---

### AREA 6 — AUDIT LOGGING

#### T-6.1 — Audit service (app-layer immutable)  ⚠ APPROVAL (Arch + Security)
- **Decision refs:** D-006, D-039 · **Depends on:** T-3.3
- **Effort:** 1.0 idd
- **Deliverables:** Write-only audit repository/service; before/after hashing; actor/IP/UA capture.
- **Acceptance criteria:** Records written on auditable actions; no update/delete path exposed.
- **Security checkpoint:** Immutability enforced app-side (and DB trigger if available); PII minimized.
- **Testing:** Tests: write occurs; mutation methods absent/blocked.
- **Rollback:** Revert service.
- **DoD:** Audit service immutable + tested; Arch + Security sign.

#### T-6.2 — Off-box audit export hook  ⚠ APPROVAL (Security)
- **Decision refs:** D-039 (SEC-03) · **Depends on:** T-6.1
- **Effort:** 0.5 idd
- **Deliverables:** Scheduled export of audit logs to a write-once external store (config-driven; may be deferred-active on shared).
- **Acceptance criteria:** Export job runs; produces tamper-evident output; gated by config.
- **Security checkpoint:** Transport secured; destination access controlled.
- **Testing:** Test: export job produces expected artifact.
- **Rollback:** Disable export flag.
- **DoD:** Export hook present + configurable; Security signs.

#### T-6.3 — Wire audit to core events
- **Decision refs:** EVENT_CATALOG (E-CORE-*) · **Depends on:** T-6.1, T-4/T-5 events
- **Effort:** 0.5 idd
- **Deliverables:** LogAuditEvent listener attached to all AUDIT-flagged core events.
- **Acceptance criteria:** Each audited event produces exactly one audit record.
- **Security checkpoint:** No duplicate/missing audit entries.
- **Testing:** Tests across login/role/password/deletion events.
- **Rollback:** Revert wiring.
- **DoD:** All core AUDIT events logged; tested.

---

### AREA 7 — USER MANAGEMENT

#### T-7.1 — User management (admin CRUD + status)
- **Decision refs:** D-021, USER_ROLE_MATRIX, E-CORE-008 · **Depends on:** T-5.4
- **Effort:** 1.0 idd
- **Deliverables:** Admin user create/read/update/deactivate; status lifecycle; deactivation revokes tokens.
- **Acceptance criteria:** Only permitted roles manage users; deactivation revokes access immediately.
- **Security checkpoint:** Creation authority per matrix; deactivation token revocation; audited.
- **Testing:** Tests: CRUD authz; deactivation revokes tokens + audits.
- **Rollback:** Revert.
- **DoD:** User management gated + audited; tested.

#### T-7.2 — Role assignment flow  ⚠ APPROVAL (Security)
- **Decision refs:** D-021 · **Depends on:** T-7.1, T-5.5
- **Effort:** 0.5 idd
- **Deliverables:** Assign/revoke roles to users via gated flow; fires lifecycle events.
- **Acceptance criteria:** Only Architect-tier admins assign elevated roles; change audited.
- **Security checkpoint:** No privilege escalation; super-admin assignment restricted.
- **Testing:** Tests: assignment authz; audit; event fire.
- **Rollback:** Revert.
- **DoD:** Role assignment gated, audited, event-driven; Security signs.

---

### AREA 8 — LOCALIZATION FOUNDATION

#### T-8.1 — i18n foundation + locale detection
- **Decision refs:** D-014, D-037 · **Depends on:** T-1.3
- **Effort:** 1.0 idd
- **Deliverables:** `__('module.key')` usage standard; `/lang/en` baseline; locale detection (user pref → session → header → en).
- **Acceptance criteria:** UI strings routed through translator; English complete; locale resolves by priority.
- **Security checkpoint:** No untrusted input in translation keys.
- **Testing:** Tests: key resolution; locale priority.
- **Rollback:** Revert.
- **DoD:** i18n-first established; English baseline; detection works.

#### T-8.2 — RTL-ready layout scaffolding
- **Decision refs:** D-014 (Arabic Phase 3), D-028 · **Depends on:** T-8.1
- **Effort:** 0.5 idd
- **Deliverables:** Tailwind logical-property convention (ms/me, ps/pe, start/end); `dir` attribute hook; RTL plugin configured.
- **Acceptance criteria:** No physical left/right in base layout; `dir=rtl` flips layout cleanly.
- **Security checkpoint:** n/a.
- **Testing:** Visual check + lint rule for physical-direction CSS.
- **Rollback:** Revert.
- **DoD:** Layout RTL-ready from day one; convention documented.

---

### AREA 9 — SECURITY MIDDLEWARE

#### T-9.1 — Security headers middleware  ⚠ APPROVAL (Security)
- **Decision refs:** D-039, Blueprint §14 · **Depends on:** T-1.3
- **Effort:** 0.5 idd
- **Deliverables:** Middleware: HSTS, CSP, X-Frame-Options, X-Content-Type-Options; remove Server/X-Powered-By.
- **Acceptance criteria:** Headers present on all responses; CSP scoped to approved sources.
- **Security checkpoint:** CSP not `unsafe-*` beyond necessity; HSTS max-age set.
- **Testing:** Tests asserting headers; CI header check.
- **Rollback:** Revert middleware.
- **DoD:** Headers enforced + tested; Security signs.

#### T-9.2 — Rate limiting middleware
- **Decision refs:** D-039, Blueprint §14 · **Depends on:** T-4.2
- **Effort:** 0.5 idd
- **Deliverables:** Per-IP + per-user throttling (60/min public, 120/min auth defaults).
- **Acceptance criteria:** Excess requests get 429 with retry-after.
- **Security checkpoint:** Limits protect auth + (future) AI endpoints; no bypass.
- **Testing:** Tests: throttle triggers 429.
- **Rollback:** Revert.
- **DoD:** Rate limiting active + tested.

#### T-9.3 — CSRF + secure cookie config
- **Decision refs:** D-039 · **Depends on:** T-4.1
- **Effort:** 0.5 idd
- **Deliverables:** CSRF on state-changing web routes; secure cookie config aligned with T-4.6.
- **Acceptance criteria:** Missing/invalid CSRF token rejected on web POST.
- **Security checkpoint:** API uses token auth (CSRF-exempt by design); web protected.
- **Testing:** Tests: CSRF rejection/acceptance.
- **Rollback:** Revert.
- **DoD:** CSRF enforced on web; tested.

#### T-9.4 — Cloudflare front + .env isolation verify  ⚠ APPROVAL (DevOps)
- **Decision refs:** D-039 (SEC-09, SEC-02) · **Depends on:** staging up (T-10.2)
- **Effort:** 0.5 idd
- **Deliverables:** Cloudflare in front of staging; verification that `.env` is unreachable by URL; docroot = /public.
- **Acceptance criteria:** `.env` URL returns 403/404; Cloudflare serving; docroot confirmed.
- **Security checkpoint:** WAF/bot baseline on; TLS via Cloudflare; origin not bypassable trivially.
- **Testing:** External fetch of `.env` path fails; headers show Cloudflare.
- **Rollback:** Bypass Cloudflare (DNS) if needed; fix origin.
- **DoD:** Cloudflare fronting + `.env` isolation proven; DevOps signs.

---

### AREA 10 — QUALITY & DELIVERY (cross-cutting)

#### T-10.1 — Test suite baseline
- **Decision refs:** IMPLEMENTATION_GOVERNANCE §5/§7 · **Depends on:** Areas 4–6
- **Effort:** 1.5 idd
- **Deliverables:** Unit + feature tests for auth, RBAC, audit, events; coverage threshold agreed.
- **Acceptance criteria:** Suite green in CI; threshold met on auth/RBAC/audit.
- **Security checkpoint:** Security-path tests present (lockout, authz deny, audit immutability).
- **Testing:** The suite itself; CI runs it.
- **Rollback:** n/a.
- **DoD:** Suite green; threshold met; required by merge gate.

#### T-10.2 — Staging deploy + cron/queue verify  ⚠ APPROVAL (DevOps)
- **Decision refs:** D-003, D-037, Blueprint §15 · **Depends on:** Area 3, T-2.3
- **Effort:** 1.0 idd
- **Deliverables:** Git deploy to staging; migrate; config/route/view cache; cron + database-queue processor verified.
- **Acceptance criteria:** App live on staging; a queued job processes via cron; scheduler runs.
- **Security checkpoint:** Prod-like `.env` (debug off); secrets not exposed.
- **Testing:** Smoke + queue + cron heartbeat checks.
- **Rollback:** Redeploy previous commit; `migrate:rollback`.
- **DoD:** Staging live; cron + queue proven; DevOps signs.

#### T-10.3 — Sprint security pass + DoD verification  ⚠ APPROVAL (Arch + Security)
- **Decision refs:** IMPLEMENTATION_GOVERNANCE §4/§10 · **Depends on:** all tasks
- **Effort:** 1.0 idd
- **Deliverables:** Security checklist run (headers, `.env` unreachable, audit immutable, authz deny-default, MFA admin); Sprint DoD sign-off pack.
- **Acceptance criteria:** All Sprint DoD items pass; no open CRITICAL/HIGH security item.
- **Security checkpoint:** This IS the checkpoint — full D-039 baseline verified.
- **Testing:** Re-run gates; manual security review.
- **Rollback:** Block Gate 2 until resolved.
- **DoD:** Sprint DoD met; Gate 2 sign-off ready; Arch + Security sign.

---

## APPROVAL-REQUIRED TASKS (Gate before execution — Governance §8)

| Task | Approver(s) | Reason |
|---|---|---|
| T-1.2 | Architect | Package/security surface selection |
| T-2.4 | Architect | DB engine choice affects all migrations |
| T-3.1–T-3.7 | Architect (T-3.3 also Security) | Schema migrations / audit + PII |
| T-4.3, T-4.5, T-4.6, T-4.7 | Security | Password/MFA/session/PII paths |
| T-5.1, T-5.2, T-5.3 | Architect + Security | Roles, permissions, separation of duties |
| T-5.4 | Architect | Authorization core |
| T-6.1, T-6.2 | Architect + Security / Security | Audit immutability + export |
| T-7.2 | Security | Role assignment / escalation risk |
| T-9.1 | Security | Security headers / CSP |
| T-9.4 | DevOps | Edge + secret isolation |
| T-10.2 | DevOps | Deployment |
| T-10.3 | Architect + Security | Sprint security sign-off |

Per Governance §8, every Pull Request must cite the Decision ID(s) and Task ID it
implements, or it is rejected unreviewed.

---

## SPRINT-LEVEL DEFINITION OF DONE (GATE 2)

- [ ] All in-scope tasks meet their individual DoD
- [ ] CI fully green: driver gate, ShouldQueue, schema conformance, tests, headers, secrets scan
- [ ] 14 roles + full permission catalogue + mapping match the matrices exactly
- [ ] Audit log proven immutable; all core AUDIT events logged
- [ ] Auth: web + API + MFA(admin) + lockout + password policy + GDPR flows working
- [ ] Security baseline (D-039) verified; `.env` unreachable; Cloudflare fronting
- [ ] Deployed + smoke-tested on staging; cron + database queue confirmed
- [ ] No hardcoded drivers; both `.env` profiles documented (D-037 guarantee intact)
- [ ] Documentation updated; no undocumented features; no orphan tables
- [ ] Limitations Register updated with any new findings; engine pinned (M-3)

---

## ROLLBACK & RISK SUMMARY (Sprint level)

- No production data exists in Sprint 1 → migrations are freely reversible (`migrate:rollback`).
- Deployment rollback: redeploy prior commit + rollback migrations on staging.
- Highest-risk tasks: T-3.3 (audit enforcement vs TRIGGER availability), T-4.5 (MFA),
  T-5.3 (permission mapping correctness). Each is approval-gated and test-covered.
- Engine uncertainty (LIM-03) retired early by T-2.4 before migrations are authored.

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Technical Lead | | | | |
| Security Officer | | | | |

**Status:** Awaiting Approval.
**Per instruction: no application code, migrations, models, or controllers are
produced. Implementation begins only after this breakdown is approved.**
