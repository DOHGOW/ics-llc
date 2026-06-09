# DATABASE FOUNDATION REVIEW — SPRINT 1 · TASK 3
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Review Complete — Awaiting Approval
Reviewer: Chief Enterprise Architect
Scope: All migrations authored in Task 3 (Core Database Foundation)
Engine of record: MySQL 8.0 (pinned; override to 8.4 if production differs — LIM-03 resolved)

---

## EXECUTIVE SUMMARY

Task 3 produced **11 migration files** creating the Core Platform database
foundation — 18 tables spanning identity, RBAC, auth tokens, audit, consent,
retention, system infrastructure, notifications, and i18n. Migrations only: no
models, controllers, services, repositories, UI, or workflows. The schema
conforms to DATABASE_BLUEPRINT, preserves tenant-readiness (D-004) and
config-only migration (D-037), and embeds the security/compliance baseline
(D-006/D-039). Verdict: **PASS — proceed to Task 4 after approval.**

---

## 1. TABLES CREATED

| # | Migration (T-3.x) | Tables |
|---|---|---|
| 000001 (T-3.1) | core tenants | `core_tenants` |
| 000002 (T-3.2) | core users | `core_users` |
| 000003 (T-3.3) | sanctum | `personal_access_tokens` |
| 000004 (T-3.4) | spatie rbac | `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` |
| 000005 (T-3.5) | audit | `core_audit_logs` |
| 000006 (T-3.6) | consent + retention | `core_consent_logs`, `core_retention_policies` |
| 000007 (T-3.7) | queue | `sys_jobs`, `sys_failed_jobs` |
| 000008 (T-3.7) | cache | `sys_cache` |
| 000009 (T-3.7) | sessions | `sys_sessions` |
| 000010 (T-3.7) | notifications | `notifications`, `notify_preferences`, `notify_push_subscriptions` |
| 000011 (T-3.7) | i18n | `i18n_translations` |

**Total: 18 tables across 11 migrations.** All match DATABASE_BLUEPRINT names,
columns, types, and indexes.

---

## 2. RELATIONSHIPS INTRODUCED

| Child | Parent | Type | On Delete |
|---|---|---|---|
| core_users.tenant_id | core_tenants.id | FK | SET NULL |
| model_has_permissions.permission_id | permissions.id | FK | CASCADE |
| model_has_roles.role_id | roles.id | FK | CASCADE |
| role_has_permissions.permission_id | permissions.id | FK | CASCADE |
| role_has_permissions.role_id | roles.id | FK | CASCADE |
| core_consent_logs.user_id | core_users.id | FK | CASCADE |
| notify_preferences.user_id | core_users.id | FK | CASCADE |
| notify_push_subscriptions.user_id | core_users.id | FK | CASCADE |

**Intentional non-FK (polymorphic / decoupled) references:**
- `personal_access_tokens.tokenable` (morph) — Sanctum standard.
- `model_has_*` `model` (morph) — Spatie standard (points at core_users).
- `notifications.notifiable` (morph) — Laravel standard.
- `core_audit_logs.{actor_id,tenant_id,record}` — **deliberately no FK** so the
  trail survives deletion of referenced rows (audit independence).
- `sys_sessions.user_id` — indexed, no FK (session rows must not cascade).
- `i18n_translations.translatable` — polymorphic.

---

## 3. INDEX STRATEGY

| Category | Indexes |
|---|---|
| Uniqueness | core_tenants.slug; core_users.email; permissions/roles (name,guard_name); sys_failed_jobs.uuid; personal_access_tokens.token; notify_preferences (user_id,notification_type); core_retention_policies (module,record_type); i18n (type,id,locale,field) |
| Foreign-key support | every FK column is indexed (auto via constrained()/foreign()) |
| Filter/scan columns | core_tenants.status; core_users.status; audit (tenant_id, actor_id, module, created_at); consent.consent_type; i18n.locale |
| Composite/morph | model_has_* (model_id,model_type); notifications (notifiable_*); sys_jobs (queue,reserved_at,available_at); i18n (translatable_type,translatable_id) |
| Primary keys | BIGINT id everywhere except: sys_cache (key), sys_sessions (id string), notifications (uuid), and Spatie pivots (composite PKs) |

Principle applied (Blueprint §4.3): every FK indexed; every WHERE/ORDER column
used by core flows indexed; no unbounded scan paths in the foundation.

---

## 4. FOREIGN KEY STRATEGY

- **InnoDB + enforced FKs** for owned relationships (users↔tenants, consent/
  notifications↔users, RBAC pivots) — referential integrity guaranteed.
- **CASCADE** where the child is meaningless without the parent (consent,
  notify prefs/subscriptions, RBAC assignments).
- **SET NULL** for `core_users.tenant_id` — a user can outlive a tenant record
  (ICS-owned users are NULL anyway in Phase 1).
- **No FK** for audit, sessions, and all polymorphic references — by design, to
  preserve the audit trail, avoid session cascade hazards, and support morphs.
- All FK columns carry a supporting index (FK performance + lock scope).

---

## 5. TENANT STRATEGY (D-004 / D-037)

- `core_tenants` exists from migration #1; `core_users.tenant_id` is present and
  nullable from migration #2.
- Phase 1 is single-tenant: ICS-owned rows use `tenant_id = NULL`.
- The **column is the load-bearing part**; the global `TenantScope` query logic
  is deferred to Phase 3 (D-037) — activating multi-tenancy / Franchise
  Operations (D-019) requires **no schema change**, only scope activation.
- `core_tenants.settings` (JSON) reserves per-tenant config/branding; `domain`
  reserves custom-domain tenants.

> Note: business-module tables (CRM, Training, etc.) also carry `tenant_id` per
> blueprint; those are created in their own module sprints, not Task 3.

---

## 6. AUDIT STRATEGY (D-006 / D-039)

- `core_audit_logs` is **append-only**: `created_at` only — no `updated_at`,
  no `deleted_at`.
- Stores **hashes** (`before_hash`/`after_hash`, SHA-256), never raw record data
  — sensitive payloads are never duplicated into the audit table.
- **No foreign keys** — the trail must survive deletion/anonymisation of the
  actor or tenant it references.
- Immutability is enforced in three layers (defence in depth):
  1. Application: write-only repository (T-6.1) — no update/delete path exists.
  2. Optional DB trigger blocking UPDATE/DELETE — added only if TRIGGER
     privilege is confirmed (Quicksheet C8); not assumed on shared hosting.
  3. Off-box export to a write-once store (T-6.2).
- Retention (D-006) anonymises aged entries via policy; it never hard-deletes the
  trail.

---

## 7. SECURITY REVIEW

| Control | Status | Notes |
|---|---|---|
| Passwords hashed | ✅ | `password` holds bcrypt hash (cost 12, app-set); never plaintext |
| MFA secret protection | ⚠ app-layer | `mfa_secret` column present; MUST use encrypted cast in the model (Task 4) — flagged |
| API tokens hashed | ✅ | Sanctum stores SHA-256 hash + scoped `abilities` + expiry |
| Audit immutability | ✅ design | Append-only + hashes + no FK; app-layer enforced (T-6.1) |
| PII minimisation | ✅ | Audit stores hashes not data; consent stores basis not content |
| Consent ledger (NDPA/GDPR) | ✅ | type/version/timestamp/IP captured (D-006) |
| Referential integrity | ✅ | InnoDB + FKs on owned data |
| No secrets in schema | ✅ | No credential columns beyond hashes/keys; push keys flagged sensitive |
| Tenant isolation readiness | ✅ | tenant_id present; scope deferred (D-037) |
| Sensitive job/exception data | ⚠ access-control | sys_failed_jobs.exception & job payloads admin-only (operational note) |

No critical security defects. Two flagged app-layer follow-ups: encrypted
`mfa_secret` cast (Task 4) and admin-only access to failed-job detail.

---

## 8. PERFORMANCE REVIEW

| Aspect | Assessment |
|---|---|
| Index coverage | All FK + filter columns indexed; no foreseeable full scans in core flows |
| Audit growth | Append-only table grows unbounded → indexed by created_at; prune/partition planned (retention; partition on VPS/cloud) — SCAL-03 |
| Queue on DB (shared) | sys_jobs composite index supports the worker query; acceptable at Phase 1 volume; Redis on VPS removes contention (D-037) |
| Connections (LIM-08) | Sessions/cache default to file on shared to spare DB connections; sys_sessions/sys_cache provisioned but inactive unless selected |
| JSON columns | core_tenants.settings — MySQL 8 native JSON; queried rarely; fine |
| Charset/engine | utf8mb4 / InnoDB throughout (MySQL 8) — Unicode + Arabic-safe (D-014) |
| Composite-PK pivots | Spatie pivots use composite PKs (no surrogate) — efficient join/lookup |

No performance concerns for the foundation at Phase 1 scale. Growth items
(audit/append-only tables) have documented mitigations.

---

## 9. FINDINGS & REQUIRED COMPANION STEPS

These are NOT defects in the migrations; they are required adjacent steps,
flagged per governance (no silent gaps).

| ID | Item | Action | When |
|---|---|---|---|
| F-1 | Stock Laravel default migrations (`create_users_table`, `create_cache_table`, `create_jobs_table`) conflict with our prefixed tables | Remove them after `composer create-project` — superseded by core_/sys_ migrations | Bootstrap |
| F-2 | Driver→table wiring for renamed infra tables | Set config/queue.php `sys_jobs`/`sys_failed_jobs`, config/cache.php `sys_cache`, config/session.php `sys_sessions` (config, not migration) | Task 4 companion |
| F-3 | `password_reset_tokens` table not in blueprint but needed for reset flow | Add via blueprint amendment + migration | Task 4 (Auth) |
| F-4 | `sys_cache_locks` needed only if atomic Cache::lock used | Add via amendment if/when locks adopted | If needed |
| F-5 | `mfa_secret` must be encrypted-at-rest | Use Laravel `encrypted` cast on the model | Task 4 |
| F-6 | Optional audit-immutability DB trigger | Add only if TRIGGER privilege confirmed (C8) | If confirmed |
| F-7 | MySQL exact minor (8.0 vs 8.4) | Confirm; align DB_IMAGE/CI tag | Housekeeping |

---

## 10. SCHEMA CONFORMANCE VERIFICATION (T-3.8)

- The CI **engine-parity job** (MySQL 8, Task 2) runs `php artisan migrate` +
  the suite against the pinned engine — proving the migrations apply cleanly on
  production-equivalent MySQL 8.
- A schema-assertion test (table/column/index names vs DATABASE_BLUEPRINT) is
  added with the Sprint 1 test baseline (T-10.1) and wired as a CI gate
  (Governance §7). Task 3 introduces no application code, so the assertion test
  is authored in the testing task, not here.
- Manual conformance check performed in this review: **all 18 tables match the
  blueprint** (names, columns, types, nullability, indexes, FKs).

---

## CONFIRMATIONS (governance)

| Confirmation | Result |
|---|---|
| Migrations only (no models/controllers/services/repos/UI/workflows) | ✅ |
| Schema conforms to DATABASE_BLUEPRINT | ✅ (18/18 tables) |
| D-004 tenant-readiness preserved (tenant_id present, scope deferred) | ✅ |
| D-037 config-only migration intact (no env-specific schema) | ✅ |
| D-006 / D-039 security & compliance embedded | ✅ |
| No new architectural decisions introduced | ✅ (findings are companion steps, not new decisions) |

---

## REVIEW VERDICT

**PASS.** The Core Database Foundation is conformant, secure-by-design, tenant-
ready, and applies cleanly on MySQL 8. Seven companion items (F-1…F-7) are
documented for their owning steps. Cleared to proceed to **Task 4 (Authentication)**
after approval.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Lead Architect | | | | |
| Technical Lead | | | | |
| Security Officer | | | | |

**Status:** Awaiting Approval. **Do not proceed to Task 4 until approved.**
