# PHASE 1 — SPRINT 1 IMPLEMENTATION PLAN
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: DRAFT — BLOCKED pending Host Capability Review approval
Owner: Technical Lead
Decision References: D-020 (Laravel 11), D-021 (RBAC), D-027 (Events),
D-037 (config-driven runtime), D-038 (content engine), D-039 (security baseline)

---

## ⛔ EXECUTION GATE — READ FIRST

> **This plan must NOT begin until BOTH conditions are met:**
> 1. HOSTINGER_CAPABILITY_SPIKE.md has been executed and all CRITICAL checks PASS.
> 2. The Host Capability Review is signed APPROVED, and every PARTIAL/FAIL item has
>    a recorded entry in HOSTINGER_LIMITATIONS_REGISTER.md.
>
> Until then this is a **plan on paper**. No Laravel installation, no migrations,
> no models, no controllers, no application code is to be written.

---

## EXECUTIVE SUMMARY

Sprint 1 builds the **Core Platform** — the Level-0 foundation that every other
module depends on (MODULE_DEPENDENCY_DIAGRAM). Nothing of business value ships in
Sprint 1; instead it establishes the spine: project scaffold, authentication, RBAC,
audit logging, the i18n foundation, the notification + queue infrastructure, the
security baseline, and the config-driven runtime that makes the platform
shared-deployable and VPS-ready (D-037).

Getting Sprint 1 right is disproportionately important: a defect in auth or RBAC is
platform-wide (SPOF-06), and the config-driven patterns established here are what
guarantee config-only VPS migration later.

- Sprint length: 2 weeks (10 working days) — adjust to team capacity
- Scope: Core Platform only (Sprints 2+ add CMS, CRM, etc.)
- Deliverable: a deployable, secured, authenticated, role-aware skeleton with
  zero business modules and a green test suite.

---

## 1. SPRINT OBJECTIVES

| # | Objective | Why |
|---|---|---|
| O-1 | Stand up Laravel 11 with the approved structure & config-driven runtime | Foundation; enforces D-037 from line one |
| O-2 | Authentication (web + Sanctum API) with MFA scaffold | Every module depends on it |
| O-3 | RBAC: 14 roles + permission catalogue seeded (Spatie) | Gate for all future features (D-021) |
| O-4 | Audit logging (app-layer immutable) + consent log | Compliance baseline (D-006, D-039) |
| O-5 | i18n foundation (lang files; translation table present) | i18n-first principle (D-014) |
| O-6 | Notification + queue infrastructure (config-driven) | Async backbone (D-022, D-037) |
| O-7 | Security baseline applied (D-039) | Non-negotiable from the start |
| O-8 | CI pipeline + test suite + deployment to staging | Quality gate; proves deployability |

---

## 2. IN SCOPE / OUT OF SCOPE

**In scope (Sprint 1):**
- Laravel 11 project, folder structure (Blueprint §3.1), naming conventions (§3.3)
- `core_*`, Spatie, Sanctum, `sys_*`, `notifications`, `notify_*`, `i18n_translations` tables
- Auth flows, MFA scaffold, password policy, lockout, sessions/tokens
- RBAC roles + permissions seeders (USER_ROLE_MATRIX, PERMISSION_MATRIX)
- Audit + consent logging; core platform Events (E-CORE-001…011)
- Notification infrastructure + queue (database driver, cron processor)
- Config-driven runtime + env feature flags (D-037)
- Security baseline (D-039); Cloudflare in front; CI + staging deploy

**Explicitly OUT of scope (later sprints):**
- CMS, CRM, Client Portal, Training, Marketplace, Partner, Startup, Knowledge,
  Research, Community, Billing, AI use cases, Analytics dashboards, Data Warehouse
- Any `dw_*` ETL automation (flag OFF), French/Arabic content, PWA polish
- Note: the `dw_*`, billing, content, etc. **tables** are created in their own
  module sprints, not Sprint 1 — Sprint 1 builds only `core_*` and infrastructure.

---

## 3. PREREQUISITES (must be true before Day 1)

- [ ] Host Capability Review APPROVED (the gate above)
- [ ] HOSTINGER_LIMITATIONS_REGISTER.md finalized; workarounds chosen
- [ ] Confirmed DB engine/version (CHECK 07) → pin local + staging to match (LIM-03)
- [ ] Confirmed cron interval (CHECK 04) → set queue cadence accordingly
- [ ] Confirmed TRIGGER availability (CHECK 08) → choose audit enforcement path
- [ ] Confirmed connection cap (CHECK 14) → choose session/cache drivers
- [ ] Git repository + branching model (GitHub Flow, Blueprint §15.2) ready
- [ ] Staging subdomain + database provisioned
- [ ] `.env` placement confirmed outside web root (CHECK 03)
- [ ] Cloudflare account ready (D-039 SEC-09)

---

## 4. WORK BREAKDOWN (with dependencies)

Tasks are sequenced; later tasks depend on earlier ones. No code is written until
the gate clears — this is the planned order of work once approved.

### Workstream A — Foundation & Runtime (O-1, O-7)

| Task | Description | Depends on |
|---|---|---|
| A-1 | Laravel 11 install; folder structure per Blueprint §3.1 | Prereqs |
| A-2 | `config/ics.php` — feature flags (ETL, heavy jobs, AI volume, community scaling) | A-1 |
| A-3 | Driver config audit: queue/cache/session/filesystem/mail all from `.env`; CI grep gate for hardcoded drivers (D-037 guarantee #1) | A-1 |
| A-4 | `.env.example` for both runtime profiles (shared / VPS) per Blueprint §15.4 | A-2 |
| A-5 | Security baseline: `.env` off web root, headers (HSTS/CSP/X-Frame), error display off, `.htaccess`/docroot hardening (D-039) | A-1 |
| A-6 | Cloudflare in front of staging (D-039 SEC-09) | A-5 |

### Workstream B — Database Foundation (O-3, O-4, O-5)

| Task | Description | Depends on |
|---|---|---|
| B-1 | Migrations: `core_tenants`, `core_users` (tenant_id nullable), `core_consent_logs`, `core_retention_policies` | A-1 |
| B-2 | Migrations: Spatie tables + Sanctum `personal_access_tokens` | B-1 |
| B-2b | Migrations: `core_audit_logs` (append-only) + chosen enforcement (trigger if CHECK 08 PASS, else app-layer only) | B-1, CHECK 08 |
| B-3 | Migrations: `sys_jobs`, `sys_failed_jobs`, `sys_sessions`, `sys_cache` | B-1 |
| B-4 | Migrations: `notifications`, `notify_preferences`, `notify_push_subscriptions` | B-1 |
| B-5 | Migration: `i18n_translations` (present, unused in Phase 1) | B-1 |
| B-6 | Confirm every table: InnoDB, utf8mb4, tenant_id where applicable, indexes per DATABASE_BLUEPRINT | all B |

### Workstream C — Authentication (O-2)

| Task | Description | Depends on |
|---|---|---|
| C-1 | Web auth (session) + Sanctum API tokens | B-1, B-2 |
| C-2 | Password policy (12+, complexity, bcrypt 12, history), HIBP breach check | C-1 |
| C-3 | Login throttling + lockout (5 attempts) → E-CORE-005 | C-1 |
| C-4 | MFA scaffold (TOTP) — required for admin roles, optional others | C-1 |
| C-5 | Session hardening: httpOnly/Secure/SameSite, regenerate on privilege change | C-1 |
| C-6 | Data export + deletion endpoints (E-CORE-009/010) for NDPA/GDPR | C-1, B-1 |

### Workstream D — RBAC (O-3)

| Task | Description | Depends on |
|---|---|---|
| D-1 | Role seeder: 14 roles (USER_ROLE_MATRIX) | B-2 |
| D-2 | Permission seeder: full catalogue (PERMISSION_MATRIX) | B-2 |
| D-3 | Role→permission mapping seeder (PERMISSION_MATRIX grids) | D-1, D-2 |
| D-4 | Middleware + Gate/Policy base; least-privilege default-deny | D-3 |
| D-5 | Role lifecycle events: E-CORE-006/007; effect on next session refresh | D-4, C-1 |

### Workstream E — Audit, Events, i18n (O-4, O-5)

| Task | Description | Depends on |
|---|---|---|
| E-1 | Core Events + Listeners E-CORE-001…011 (EVENT_CATALOG), all heavy listeners `ShouldQueue` (D-037 guarantee #2) | B-2b, C-1 |
| E-2 | Audit service: write-only repository + (optional) off-box export hook (D-039 SEC-03) | B-2b, E-1 |
| E-3 | i18n foundation: `__('module.key')` everywhere; `/lang/en` populated; RTL-ready layout scaffolding (logical CSS) | A-1 |

### Workstream F — Notifications & Queue (O-6)

| Task | Description | Depends on |
|---|---|---|
| F-1 | Notification infra: mail (Brevo) + database channels; `notify_preferences` honored | B-4, A-3 |
| F-2 | Queue on `database` driver; cron processor; auth-critical mail synchronous (D-039 SPOF-04) + SMTP fallback | A-3, B-3 |
| F-3 | WelcomeNotification etc. wired to E-CORE events (validate end-to-end) | F-1, E-1 |

### Workstream G — Quality & Delivery (O-8)

| Task | Description | Depends on |
|---|---|---|
| G-1 | Test suite: unit + feature for auth, RBAC, audit, events | C/D/E done |
| G-2 | CI pipeline: lint, hardcoded-driver grep gate, tests, build `vendor/` | A-3, G-1 |
| G-3 | Staging deploy via Git; migrate; smoke test; cron + queue verified | all |
| G-4 | Security pass: headers, `.env` unreachable, audit immutability proven | A-5, E-2 |

---

## 5. SUGGESTED DAY-BY-DAY SEQUENCE (10 days)

| Day | Focus |
|---|---|
| 1 | A-1…A-4 scaffold + runtime config + feature flags |
| 2 | A-5/A-6 security baseline + Cloudflare; B-1 core tables |
| 3 | B-2/B-2b/B-3/B-4/B-5/B-6 remaining migrations + schema verification |
| 4 | C-1/C-2/C-3 auth + password policy + lockout |
| 5 | C-4/C-5/C-6 MFA scaffold + session hardening + GDPR endpoints |
| 6 | D-1/D-2/D-3 role + permission seeders |
| 7 | D-4/D-5 policies/middleware + role lifecycle events |
| 8 | E-1/E-2/E-3 events + audit service + i18n foundation |
| 9 | F-1/F-2/F-3 notifications + queue + cron |
| 10 | G-1…G-4 tests, CI, staging deploy, security pass, demo |

---

## 6. DEFINITION OF DONE (Sprint 1)

A user/admin can:
- [ ] Register and log in (web + API), with MFA available for admins
- [ ] Be assigned one of 14 roles; access is default-deny and permission-gated
- [ ] Trigger actions that write immutable audit-log entries (proven immutable)
- [ ] Receive a queued email + in-app notification (via cron queue)
- [ ] Reset password via a synchronous (non-queued) path with SMTP fallback
- [ ] Export / request deletion of their data (NDPA/GDPR)

Engineering gates:
- [ ] All migrations run cleanly on the confirmed host engine (LIM-03)
- [ ] CI green; hardcoded-driver grep gate passing (D-037)
- [ ] Every heavy listener implements `ShouldQueue`
- [ ] `.env` unreachable by URL; security headers present; Cloudflare fronting
- [ ] Deployed and smoke-tested on staging; cron + queue confirmed working
- [ ] Test coverage on auth/RBAC/audit meets the agreed threshold

---

## 7. CONFIG-DRIVEN RUNTIME — SPRINT 1 ENFORCEMENT (D-037)

Sprint 1 is where the migration guarantee is born. It must establish:
- No driver literal anywhere outside `config/` (CI gate, task A-3)
- `config/ics.php` feature flags consumed via `config('ics.*')` only
- Every non-instant listener `implements ShouldQueue`
- Two `.env` profiles documented (shared / VPS), differing only in config

If these are not true at the end of Sprint 1, the config-only VPS migration promise
is already broken. This is a hard exit criterion.

---

## 8. RISKS SPECIFIC TO SPRINT 1

| Risk | Mitigation |
|---|---|
| Host engine is MariaDB (LIM-03) | Pin local/staging to same engine; run JSON/FULLTEXT/FK probes before writing migrations |
| TRIGGER denied (LIM-05) | App-layer audit immutability path selected in B-2b; no rework |
| Cron interval > 1 min (LIM-04) | Queue cadence + synchronous auth mail decided in F-2 |
| Connection cap (LIM-08) | Session/cache off MySQL chosen in A-3 driver config |
| Scope creep into business modules | DoD strictly limits Sprint 1 to Core Platform |

---

## 9. EXIT → SPRINT 2

On Sprint 1 sign-off, Sprint 2 (per MODULE_DEPENDENCY_DIAGRAM build order) begins:
**Corporate Website / CMS** and **CRM** (both Level 1, depend only on Core Platform).
The unified Content Engine (D-038) is established during the CMS/Knowledge work.

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Technical Lead | | | | |

**Status:** DRAFT — BLOCKED pending Host Capability Review approval.
**Do not begin implementation until that review is APPROVED.**
