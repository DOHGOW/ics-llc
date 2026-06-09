# TENANTSCOPE SECURITY VALIDATION — Phase 1 (D-088)
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05 → executed
Status: EXECUTED — actual test evidence.
Author: Lead Architect

## RESULT: ✅ ALL MANDATORY VERIFICATIONS PASS

| Mandatory verification | Result | Test (actual) |
|---|---|---|
| CrossTenantIsolationTest PASS | ✅ | `CrossTenantIsolationTest` 4/4 (isolate, fail-closed, super-tenant, disabled no-op) |
| Fail-closed verification PASS | ✅ | `reads fail closed when no tenant resolved` + `async fails closed without tenant` |
| Super-tenant bypass PASS | ✅ | `super tenant context sees across tenants` + `super tenant sees across in async` |
| Queue-context restoration PASS | ✅ | `queue middleware restores tenant context` (job bound to tenant 1 sees only tenant 1) |
| Scheduler-context verification PASS | ✅ | `scheduler explicit cross-tenant spans tenants` (downgrade-only sweep pattern) |

## SECURITY PROPERTIES NOW ENFORCED (post-remediation)

1. **Fail-closed in console/async** — with the `runningInConsole()` bypass removed, an enabled-tenancy
   query with no resolved tenant returns `WHERE 1=0` (zero rows) instead of silently crossing tenants.
   Verified in-console (PHPUnit is console) by `async fails closed without tenant` → 0 rows.
2. **Queue isolation** — `TenancyQueueMiddleware` + `TenantContext::runForTenant()` restore the
   originating tenant for the job's lifetime; a tenant-1 job cannot see tenant-2 rows. Verified.
3. **Explicit cross-tenant remains explicit** — `acrossTenants()` / `withoutGlobalScope(TenantScope)` /
   `runAsSuperTenant()` still span tenants for deliberate, downgrade-only jobs (reconciliation). Verified.
4. **System maintenance unaffected** — migrate/seed run tenancy-DISABLED (scope no-op); AccountScope's
   `$user === null` guard preserves system context. 80 migrations apply; 57 tests pass.
5. **No silent cross-tenant path** — the only ways to span tenants are now EXPLICIT and named.

## THREAT-MODEL CLOSURE (vs TENANTSCOPE_ASYNC_SECURITY_REVIEW)

| Pre-remediation risk | Status |
|---|---|
| Queue job leaks across tenants (accidental) | ✅ CLOSED — middleware restores context; default fail-closed |
| Scheduled per-tenant work leaks | ✅ CLOSED — explicit cross-tenant only; default fail-closed |
| Import/command implicit cross-tenant | ✅ CLOSED — fail-closed unless tenant/super bound |
| Isolation untestable (tests can't assert) | ✅ CLOSED — scope engages in console; 10 isolation tests assert it |
| D-079 franchise async exposure | ✅ MITIGATED — fail-closed default + opt-in tenant binding |

## RESIDUAL / CARRIED

- **Engine authority:** isolation verified on sqlite AND on a real MySQL-family server (MariaDB 10.4,
  see MYSQL_ENGINE_PARITY_REPORT). The MySQL 8 CI job is the final authoritative gate.
- **Production enablement** still gated by D-078-A/B + `ICS_TENANCY_ENABLED` (ships disabled → no-op).
- **Registry-model `acrossTenants()`:** the 33 registry-scoped models use `withoutGlobalScope(TenantScope)`
  (the underlying mechanism); only trait-using models expose the `acrossTenants()` sugar. Documented.

**Conclusion:** D-088 OPTION B is implemented and validated — tenant isolation is now enforced and
testable across HTTP, console, and async contexts, with fail-closed restored and no silent cross-tenant path.
