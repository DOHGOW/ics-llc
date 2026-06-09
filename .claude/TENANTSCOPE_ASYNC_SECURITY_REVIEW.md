# TENANTSCOPE ASYNC SECURITY REVIEW — Blocker A
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05
Status: Security/architecture review — ANALYSIS ONLY (no code change).
Author: Lead Architect
Evidence: app/Authorization/Scopes/TenantScope.php, app/Tenancy/TenantContext.php, routes/console.php,
app/Services/Billing/ReconciliationService.php; CI run 47 pass / 5 fail (3 TenantScope isolation tests).
Decisions in scope: D-076, D-077, D-078, D-079.

---

## EXECUTIVE SUMMARY

`TenantScope::apply()` returns early — **no tenant filter** — whenever `app()->runningInConsole()` is true.
PHPUnit, queue workers, scheduled jobs, and every artisan command run in console, so **tenant isolation
does not engage in any non-HTTP context**. This is why 3 isolation tests fail. The active risk **today** is
LOW (the only console jobs — `billing:reconcile`, `marketplace:expire-listings` — are deliberately
cross-tenant and downgrade-only, and reconciliation already uses `acrossTenants()` explicitly). The risk is
**MEDIUM now, trending HIGH**: it is a latent defense-in-depth gap that will be exploited by *accident* as
soon as any per-tenant async feature (queued per-tenant notifications, exports, franchise batch ops) is
added by a developer who assumes TenantScope protects them.

**RECOMMENDATION: OPTION B — replace the blanket console bypass with context-aware tenancy resolution.**

---

## 1. WHY THE BYPASS EXISTS (root rationale)

Resolution is request-driven: `TenantContext::id()` lazily calls `TenantResolver::resolve(request())`. In
console there is no HTTP request → the resolver yields `null` → with tenancy ENABLED + unresolved + not
super, `TenantScope` falls to `whereRaw('1=0')` (**fail closed**). Without a bypass, **every console DB
operation would silently match nothing** — `migrate`, `db:seed`, `schedule:run`, and queue workers would
read/write zero rows. The `runningInConsole()` bypass was the blunt instrument to keep system/maintenance
console operations working. It solved a real problem with an over-broad exemption.

## 2. AFFECTED EXECUTION PATHS

| Path | Runs in console? | Tenant filter today | Concern |
|---|---|---|---|
| **PHPUnit** | yes | **bypassed** | isolation untestable → 3 failing tests (proven) |
| **Queue workers** (`queue:work`) | yes | **bypassed** | a queued job querying a tenant model w/o explicit context sees ALL tenants |
| **Scheduled jobs** (`schedule:run`) | yes | **bypassed** | per-tenant scheduled work would leak across tenants |
| **Artisan commands** | yes | **bypassed** | any custom command is implicitly cross-tenant |
| **Billing reconciliation** | yes | bypassed BUT uses `acrossTenants()` + downgrade-only | **safe by design today** (explicit) |
| **Marketplace expire** | yes | bypassed | sweep is intentionally global; downgrade-only → safe today |
| **Membership reconciliation** | (via billing) | same as billing | safe today (downgrade-only) |
| **Data imports** (future) | yes | **bypassed** | high risk — bulk writes with no tenant guard |
| **Future franchise async ops** | yes | **bypassed** | high risk — the exact use case TenantScope exists to protect |

## 3. THREAT MODEL

- **Asset:** per-tenant (franchise) data isolation (D-076 fail-closed guarantee).
- **Trust boundary:** the global scope is the LAST-LINE enforcement that a query cannot silently cross
  tenants. HTTP paths are protected (request → resolver → tenant). Console paths are NOT.
- **Adversary / failure mode:** primarily **accidental** — a developer adds a queued job or command that
  queries a tenant-scoped model assuming the global scope protects it; it does not, so tenant A's job
  processes/serves tenant B's rows. Secondarily, a compromised/abused job input could enumerate across
  tenants.
- **Not a pre-auth web exploit:** HTTP requests still resolve + fail-closed correctly; this is an
  **async/back-office** exposure.

### Attack / leakage paths
1. **Queued notification** keyed by a model id, processed without tenant context → renders/sends another
   tenant's data.
2. **Scheduled per-tenant export/report** iterating a tenant-scoped model without `acrossTenants()`/context
   → aggregates all tenants into one tenant's artifact.
3. **Import command** writing rows without tenant stamping/scoping → cross-tenant contamination.
4. **Test blindness:** because isolation can't be asserted in console, a regression that breaks isolation
   ships GREEN — the failing tests are actually the system telling us the guard is off.

## 4. RISK SEVERITY

| Dimension | Rating |
|---|---|
| Active exploitability today | LOW (only explicit cross-tenant, downgrade-only console jobs exist) |
| Latent / future risk | **HIGH** (any per-tenant async feature inherits the gap silently) |
| Detectability | LOW (no test can catch it while bypass exists) |
| Blast radius if triggered | HIGH (cross-tenant data exposure / contamination) |
| **Overall** | **MEDIUM, trending HIGH** — defense-in-depth + testability defect |

## 5. D-076..D-079 COMPLIANCE

- **D-076 (fail-closed, additive):** the bypass is a documented part of the FT-1 design (console = system
  context), so it does not *violate* D-076 — but it **suspends the fail-closed guarantee entirely in async
  contexts**, which is broader than the intent ("system maintenance"), and was not war-gamed for queue/
  scheduled per-tenant work.
- **D-077 (default tenant/backfill):** unaffected.
- **D-078 (reference-data tenancy):** unaffected directly; but D-078-A classification matters more once
  async tenant work exists.
- **D-079 (franchise admin):** **most exposed** — franchise operations are the headline use case and are
  likely to be async/batch; the bypass undermines exactly the boundary D-079 governs.
- **Verdict:** no hard violation, but a **material weakening** of the D-076 guarantee that must be closed
  before franchise async operations (D-079) are built.

## 6. FAIL-CLOSED ENFORCEABILITY IN ASYNC — today: NO

With the blanket bypass, fail-closed is **not** enforceable in console/async. It is enforceable only on HTTP
paths. Restoring it in async is the objective of the recommendation.

---

## 7. OPTIONS

### OPTION A — Remove the console bypass entirely
- **Effect:** console + async become fail-closed. **Breaks** `migrate`/`db:seed`/queue/scheduled unless
  every one sets a tenant or super context — including the bootstrap path we just recovered.
- **Verdict:** correct in spirit, **too blunt** — would re-break bootstrapping and all system ops. ❌

### OPTION B — Context-aware tenancy resolution (RECOMMENDED) ✅
Replace `if (runningInConsole()) return;` with intent-explicit handling:
1. **System/maintenance context (unscoped):** schema/migration/seed operations run under an explicit
   maintenance/super context (e.g., the migrator/seeders execute within `runAsSuperTenant()` or are
   inherently schema-level and never traverse Eloquent global scopes). Trusted, deliberate, auditable.
2. **Queue & scheduled jobs (context-propagating):** the originating tenant is **carried on the job** and a
   tenancy job-middleware restores `TenantContext::set($tenantId)` before `handle()` and resets after.
   Jobs that are *intentionally* cross-tenant call `runAsSuperTenant()` / `acrossTenants()` **explicitly**
   (the pattern `ReconciliationService` already uses).
3. **Default (enabled + unresolved + not super):** **fail-closed** (`1=0`) — now also in console/async.
4. **Tests:** isolation tests already `set()` a context → they pass; base-data setup runs under a
   super/maintenance context.
- **Effect:** fail-closed restored everywhere; system ops keep working; explicit cross-tenant remains
  explicit; the 3 TenantScope tests pass.
- **Verdict:** closes the gap with the **smallest blast radius** and matches existing code conventions. ✅

### OPTION C — Keep the bypass, accept the risk
- **Justification available:** current console jobs are explicitly cross-tenant + downgrade-only, so there
  is no *active* leak today.
- **Cost:** permanent footgun; isolation untestable; blocks D-079 async; relies on every future author
  remembering to scope manually.
- **Verdict:** acceptable only as a **documented interim** with a strict coding standard — **not
  future-proof**, and it leaves 3 release-gate tests RED. ❌ (for anything beyond a short interim)

---

## 8. RECOMMENDED ARCHITECTURE — OPTION B (design, not implementation)

```
TenantScope::apply():
  if (! tenancyEnabled())          return;                 // single-tenant no-op (unchanged)
  if (isSuperTenant())             return;                 // explicit HQ / maintenance (audited)
  $tid = TenantContext::id();      // HTTP: resolver; queue: restored from job; console sys: super
  if ($tid === null)               $builder->whereRaw('1=0');   // FAIL CLOSED (now incl. async)
  else                             $builder->where('tenant_id', $tid);
   // NOTE: the blanket runningInConsole() early-return is REMOVED.

Maintenance ops (migrate/seed/schema): executed within runAsSuperTenant() (or schema-level, unscoped).
Queue jobs: implement ShouldBeTenantAware → middleware sets/clears TenantContext from a serialized tenant_id.
Intentional cross-tenant jobs: call acrossTenants()/runAsSuperTenant() explicitly (e.g., reconciliation).
```

## 9. MIGRATION IMPACT

- **No database migration.** Authorization-layer behavior change only.
- **Blast radius:** any console command that legitimately needs cross-tenant/unscoped access must opt in to
  super/maintenance context. Audit shows current jobs already do (reconciliation = `acrossTenants()`);
  seeders/migrations need the maintenance context wrapper.
- **Config-gated:** still governed by `ics.tenancy.enabled` (ships disabled → total no-op), so production
  enablement remains controlled (D-078-A/B).

## 10. TESTING IMPACT

- The 3 `CrossTenantIsolationTest`/`BillingSubstrateTest>d` failures **convert to PASS** (they set context).
- Base-data/seeder setup in tests runs under maintenance/super context.
- **Add** a queue-tenancy test: a job enqueued under tenant 1 must not see tenant 2 rows (the new guarantee).
- The 2 `AccountIsolationTest` failures are a **separate mechanism (AccountScope, D-050)** — related but out
  of TenantScope scope; they need an authenticated acting-user/context in the test setup (or the same
  context-aware treatment for AccountScope). Track as a sibling item.

---

## FINAL RECOMMENDATION

**OPTION B — replace the console bypass with context-aware tenancy resolution.** It is the only option that
(a) restores the D-076 fail-closed guarantee in async/console, (b) keeps migrations/seeders/queues working,
(c) preserves explicit cross-tenant jobs, (d) makes the isolation release-gate tests pass, and (e) unblocks
D-079 franchise async operations safely — all without redesigning the scope's core contract.
