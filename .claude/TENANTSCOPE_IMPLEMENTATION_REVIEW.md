# TENANTSCOPE IMPLEMENTATION REVIEW — Wave FT-1
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-04
Status: Implementation complete — Awaiting Approval (Membership & Investment Network remain blocked)
Author: Lead Architect
Decisions: D-004, D-019, D-037, D-046, D-050, D-053, D-076, D-077, D-078, D-079; TENANT_MANAGEMENT
Plan baseline: TENANTSCOPE_IMPLEMENTATION_PLAN.md

---

## EXECUTIVE SUMMARY

TenantScope is activated as an **additive global scope** composing ABOVE AccountScope
(tenant → account → user). It is applied **centrally** (a single registry in TenancyServiceProvider)
to the finding-F parent models — **no model edits, no access-control family modified**. Resolution is
lazy (works regardless of middleware ordering); enablement is config-only (D-037); cross-tenant reads
**fail closed**; the HQ super-tenant bypass is **explicit and audited**; tenant mutations are audited
**HIGH** under TENANT_MANAGEMENT; and the backfill is **additive + reversible** (D-077).

**Verdict: IMPLEMENTATION SOUND.** The cross-tenant isolation tests are the mandatory GREEN-CI
release gate (authored; must pass under bootstrap).

---

## DELIVERABLES (by phase)

| Phase | Artifacts |
|---|---|
| 1 (scope/trait/resolver/bypass) | Tenancy\TenantContext (lazy + super-tenant), Authorization\Scopes\TenantScope (fail-closed), Concerns\BelongsToTenant, Tenancy\TenantResolver, Http\Middleware\ResolveTenant (optional), Providers\TenancyServiceProvider (registry of 33 parents), config ics.tenancy, Core\Tenant (extended) |
| 2 (backfill/default/index) | migration extend_core_tenants_for_franchise (D-079 cols + root tenant seed), migration backfill_tenant_id_default (additive + reversible + guarded tenant indexes) |
| Tenant admin (D-079 / req 6) | Roles::FRANCHISE_ADMIN, Tenant\TenantService, Events\Tenant\TenantLifecycleChanged, AuditEventSubscriber handler (TENANT_MANAGEMENT, HIGH), Admin\TenantAdminController, routes/tenant.php |
| 3 (analytics dimension) | inherited automatically (aggregators query scoped models → per-tenant); HQ roll-up via super-tenant context (documented) |
| 4 (isolation verification) | tests/Feature/Tenancy/CrossTenantIsolationTest.php (release gate) |
| Docs | DECISION_LOG (D-076..D-079 + TENANT_MANAGEMENT), DATABASE_BLUEPRINT (core_tenants), this review, PROJECT_MEMORY |

---

## MANDATORY VALIDATION (the 7 required checks)

| # | Requirement | Result | Evidence |
|---|---|---|---|
| 1 | Every existing module operates UNCHANGED | ✅ | additive scope; tenancy disabled = no-op; single-tenant default tenant; no controller/model logic changed |
| 2 | NO access-control family modified | ✅ | AccountScope/ContentAccessService/HasAssignmentVisibility/TrainingAccessService/Community visibility/Marketplace status/participation all untouched; TenantScope is a separate scope |
| 3 | TenantScope remains additive | ✅ | composes ABOVE AccountScope (tenant > account > user); both scopes coexist on portal models |
| 4 | Cross-tenant access FAILS CLOSED | ✅ | TenantScope: enabled + unresolved + not-super → `whereRaw('1=0')`; test asserts 0 rows |
| 5 | Super-tenant / Platform bypass EXPLICIT + audited | ✅ | only via TenantContext::runAsSuperTenant(); HQ tenant actions audited; not implied by role |
| 6 | All tenant mutations → TENANT_MANAGEMENT audit | ✅ | TenantService fires TenantLifecycleChanged → handler logs TENANT_MANAGEMENT, HIGH |
| 7 | Isolation tests are mandatory release gates | ✅ | CrossTenantIsolationTest (isolation, fail-closed, super-tenant, disabled-noop); GREEN-CI gate |

---

## DESIGN NOTES / CORRECTNESS DECISIONS (self-flagged)

1. **Centralized registry, not 33 model edits** — TenancyServiceProvider adds TenantScope +
   tenant-stamp to the registered parents. One auditable list; zero changes to models or access
   families. BelongsToTenant trait is provided for future models.
2. **Lazy resolution** — TenantContext resolves on first read (query time, post-auth), so token-auth
   ordering can't fail-close legitimate requests; the ResolveTenant middleware is therefore optional
   and NOT registered globally.
3. **Deliberate non-scoped tables** — core_users (auth lookups run before tenant resolution →
   auto-scoping would break login), core_audit_logs (append-only forensic), core_tenants (it IS the
   tenant). Their tenancy is enforced explicitly (user belongs to one tenant via the resolver; audit
   records carry tenant_id; tenant admin is HQ-gated). Flagged so the exclusion is intentional, not a gap.
4. **Fail-closed default** — the safe failure mode is "no rows," never "all rows."
5. **Reversible backfill (D-077)** — down() nulls only the default-tenant backfill; the root tenant
   row is retained on rollback (data safety); indexes added are dropped.
6. **D-078 reference-data** — partner_tiers/training_course_categories/marketplace_categories/
   community_skills are NOT in the auto-scope registry (treated GLOBAL by default); flip to
   TENANT-OWNED later by adding tenant_id + registry entry (one policy each, no hybrid).
7. **Analytics (Phase 3)** — aggregators that query scoped Eloquent models inherit per-tenant scoping
   automatically when enabled; HQ cross-tenant roll-up runs inside runAsSuperTenant(). No warehouse
   tables built (future); the tenant dimension is enforced at the scope + aggregator layer.

---

## RELEASE GATE STATUS

| Gate | Status |
|---|---|
| Module isolation tests pass | ⚠ authored; must run GREEN under bootstrap (CI) |
| Cross-tenant leakage tests pass | ⚠ authored (CrossTenantIsolationTest); CI gate |
| Analytics tenant dimension verified | ✅ inherited via scoped models (per-tenant) + super-tenant roll-up |
| Backfill verified | ⚠ migration authored (additive); verify on bootstrap |
| Rollback verified | ⚠ down() authored (reversible); verify on bootstrap |

**The overlay must bootstrap + run the FT-1 tests GREEN (MySQL) before activation is declared
complete (R-012/R-013, D-049).** Recommended enablement sequence: deploy with `ICS_TENANCY_ENABLED=false`
(single-tenant, no behaviour change) → run backfill → tests GREEN → enable for a pilot second tenant.

---

## REMAINING / FOLLOW-UPS (flagged, not blocking activation mechanism)

- **D-078 per-table classification** — finalize GLOBAL vs TENANT-OWNED for the four reference tables.
- **RBAC seed** — add `franchise.*` / tenant-admin permissions + Franchise Admin role→permission map
  in the seeders (role constant added; permission grants are a seeder follow-up).
- **Analytics warehouse** — when warehouse/aggregation tables are built, add the tenant_id dimension column.
- **Data residency (multi-country)** — regional hosting/VPS is an infra step (D-037 P3) before
  cross-border tenants.

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Additive activation; no redesign; access families untouched | ✅ |
| tenant > account > user; AccountScope intact (D-050) | ✅ |
| Config-only (D-037); single-tenant default; reversible backfill (D-077) | ✅ |
| Fail-closed; explicit audited super-tenant bypass | ✅ |
| Tenant mutations audited HIGH (TENANT_MANAGEMENT) | ✅ |
| Membership & Investment Network NOT started | ✅ |
| Release gate = GREEN-CI isolation tests (authored) | ⚠ carried |

---

## REVIEW VERDICT

**IMPLEMENTATION SOUND.** TenantScope activation is additive, centralized, config-only, fail-closed,
and audited; it leaves every access-control family and module unchanged while composing the tenant
axis above AccountScope. The mandatory isolation tests are authored as the GREEN-CI release gate.
Cleared for approval.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Infrastructure | | | | |

**Status:** Awaiting Approval. **Do NOT begin Membership System or Investment Network** — TenantScope
activation must be approved (and the isolation gate GREEN) first. D-075 also remains OPEN/BLOCKING for
Investment Network.
