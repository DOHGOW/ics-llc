# TENANTSCOPE IMPLEMENTATION PLAN — Wave FT-1
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-04
Status: Implementation plan (controlled phases). Authorized by D-076..D-079 + TENANT_MANAGEMENT.
Author: Lead Architect
Decisions: D-004, D-019, D-037, D-046, D-050, D-053, D-076, D-077, D-078, D-079

> **Activation, not redesign.** TenantScope is a NEW global scope composing ABOVE AccountScope
> (tenant → account → user). It modifies NO existing access-control family. `core_tenants` and
> `tenant_id` already exist. Activation is centralized + additive + config-gated + reversible.

---

## DESIGN PRINCIPLES

1. **Additive** — add `TenantScope` via a centralized `TenancyServiceProvider` registry (one list of
   tenant-scoped models); do NOT edit access-control families or rewrite models en masse.
2. **Config-gated (D-037)** — `config('ics.tenancy.enabled')` from `.env`. Disabled → single-tenant
   (scope no-op / default tenant). Enabled → multi-tenant.
3. **Fail closed** — tenancy enabled + no resolvable tenant + not an explicit super-tenant/console →
   the scope returns NO rows.
4. **Explicit, audited super-tenant bypass** — HQ cross-tenant access requires an explicit
   `TenantContext::runAsSuperTenant()` act, audited under TENANT_MANAGEMENT.
5. **Reversible (D-077)** — backfill is additive; rollback reverses without data loss; no destructive
   migration.

---

## PHASE 1 — TenantScope · BelongsToTenant · Tenant resolver · bypass hierarchy

| Artifact | Role |
|---|---|
| `App\Models\Core\Tenant` | tenant model (core_tenants) |
| `App\Tenancy\TenantContext` | request-scoped current tenant + super-tenant/console flags; `runAsSuperTenant()` |
| `App\Authorization\Scopes\TenantScope` | global scope: console/disabled/super-tenant bypass; else `tenant_id = current`; FAIL CLOSED |
| `App\Models\Concerns\BelongsToTenant` | trait: adds TenantScope + stamps tenant_id on create (default tenant fallback) |
| `App\Tenancy\TenantResolver` + `App\Http\Middleware\ResolveTenant` | resolve tenant from user / domain; set TenantContext (web+api) |
| `App\Providers\TenancyServiceProvider` | REGISTRY of tenant-scoped models → addGlobalScope(TenantScope) + creating-stamp; registered in bootstrap/providers.php |
| `config/ics.php` → `tenancy` | enabled, default_tenant_id, resolver mode |

**Bypass hierarchy (TenantScope.apply):** (a) `app()->runningInConsole()` → bypass; (b)
`! config('ics.tenancy.enabled')` → bypass (single-tenant); (c) `TenantContext::isSuperTenant()` →
bypass (explicit HQ, audited); (d) resolved tenant → `where(table.tenant_id, id)`; (e) else (enabled,
no tenant, not super) → `whereRaw('1=0')` (fail closed).

**The registry** lists every tenant-scoped PARENT model (the 38 from finding F). Children inherit via
parent (no scope). This central list is the single auditable source of what is tenant-scoped.

## PHASE 2 — Backfill · default tenant · indexing (D-077)

| Step | Detail |
|---|---|
| Default tenant | a migration inserts the ROOT default tenant (id from config) if absent (idempotent) |
| Backfill | additive migration: `UPDATE <scoped tables> SET tenant_id = <default> WHERE tenant_id IS NULL` (per table; MySQL + SQLite safe) |
| Default stamping | BelongsToTenant stamps `default_tenant_id` when no TenantContext tenant (single-tenant) |
| Indexing | add `tenant_id` (and composite) indexes on scoped parents lacking them |
| Rollback | `down()` nulls back the backfilled tenant_id + drops added indexes; default tenant row removed only if created here |

NO destructive migration. Existing single-tenant behaviour is identical post-backfill (all rows = root
tenant; scope no-op while disabled).

## PHASE 3 — Analytics tenant dimension

- Aggregators that query Eloquent scoped models inherit tenant scoping AUTOMATICALLY when tenancy is
  enabled (per-tenant dashboards). For HQ cross-tenant roll-up, run the aggregator inside
  `TenantContext::runAsSuperTenant()` (or group by tenant_id).
- Aggregators using raw queries / `acrossAccounts()` get an explicit tenant filter / grouping.
- Document the per-tenant vs HQ-roll-up access (HQ = super-tenant, audited). No warehouse tables built
  here (future); the dimension is enforced at the aggregator + source-scope level.

## PHASE 4 — Isolation verification (the release gate)

- `tests/Feature/Tenancy/*` — cross-tenant isolation tests per module: tenant A cannot read/write
  tenant B's rows (CRM, portals, content, knowledge/research, training, community, marketplace,
  startup, programs); super-tenant bypass works + is audited; fail-closed when no tenant; backfill +
  rollback verified.
- These are MANDATORY GREEN-CI release gates (R-012/R-013).

---

## MANDATORY VALIDATION (woven through all phases)

1. Every existing module operates UNCHANGED (additive scope; single-tenant = no-op).
2. NO access-control family modified (AccountScope/ContentAccessService/HasAssignmentVisibility/
   TrainingAccessService/Community visibility/Marketplace status/participation family all untouched).
3. TenantScope remains additive (composes above AccountScope).
4. Cross-tenant access FAILS CLOSED.
5. Super-tenant/Platform bypass is EXPLICIT + audited (TENANT_MANAGEMENT).
6. All tenant mutations → TENANT_MANAGEMENT audit.
7. Isolation tests are mandatory release gates.

---

## RELEASE GATE (activation complete only when ALL pass)

- module isolation tests pass · cross-tenant leakage tests pass · analytics tenant dimension verified
  · backfill verified · rollback verified.

Deliverable on completion: **TENANTSCOPE_IMPLEMENTATION_REVIEW.md** → await approval before Membership
or Investment Network.
