# AUTHORIZATION SECURITY AUDIT — PRE-TASK 5
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Audit Complete — Awaiting Approval
Auditor: Chief Enterprise Architect
Documents reviewed: USER_ROLE_MATRIX.md · PERMISSION_MATRIX.md ·
AUTHENTICATION_ARCHITECTURE_REVIEW.md · TASK_4_IMPLEMENTATION_REVIEW.md
Decision References: D-006, D-016, D-021, D-028, D-036, D-037, D-039, D-040

---

## EXECUTIVE SUMMARY

The authorization model (14 roles, ~150 permissions, default-deny, ownership
policies) is fundamentally sound and well aligned to government/SoD requirements.
The audit found **two issues that MUST be resolved before Task 5 seeding** and a
set of HIGH/MEDIUM hardening recommendations.

The two blockers:
- **AUTH-AUDIT-01 (CRITICAL): permission-naming inconsistency** between documents —
  the catalogue the seeder will use (PERMISSION_MATRIX, `module.resource.action`)
  contradicts the stated convention in USER_ROLE_MATRIX/Blueprint
  (`action.module.scope`). One canonical form must be chosen first.
- **AUTH-AUDIT-02 (CRITICAL): role-assignment privilege-escalation gap** — nothing
  yet prevents a Platform Admin from granting the Super Admin role. A policy guard
  is required (only Super Admin may assign Super Admin; no role may grant above its
  own level).

Verdict: **CONDITIONAL PASS** — resolve the two blockers (doc/decision only, no
code) before Task 5 implementation.

---

## PART A — VALIDATIONS (10)

### 1. Role Separation — PASS (with note)
- 14 roles across 3 tiers; ICS Staff split into CRM / Training / Content preserves
  separation of duties (D-040). A Content editor cannot reach CRM PII; a CRM rep
  cannot publish content. Good.
- Super Admin is all-powerful by necessity — mitigated by max 2–3 instances,
  mandatory MFA, controlled creation, quarterly review (USER_ROLE_MATRIX lifecycle).
- Note: Gov Agency Rep and Partner Admin are near-identical permission profiles
  (see Role Overlaps) — acceptable but worth confirming.

### 2. Least-Privilege Enforcement — PASS (with tightening opportunity)
- All roles default to zero; permissions are additive; scopes (O=own, R=read)
  applied. Default-deny is the base.
- Tightening: `crm.*.read.all` grants CRM Staff visibility of ALL leads/contacts.
  Consider assignment-scoped visibility (assigned_to) to reduce blast radius
  (PERMISSION_MATRIX already flagged this). See Excessive Permissions.

### 3. Government Compliance Alignment — PASS
- SoD preserved (D-040); admin MFA enforced (D-039); audit trail (D-006);
  WCAG 2.1 AA for access (D-028); least privilege. This matches ISO 27001-aligned
  expectations for the Government audience (D-016).
- Recommend: documented periodic access reviews (Super/Platform Admin quarterly),
  and recorded approval for every elevated-role grant.

### 4. GDPR Implications — PASS
- PII access is gated: only CRM Staff + Admins see CRM PII; audit logs readable
  only by Super/Platform Admin (`platform.audit.read`).
- Self-service export is own-data only; secrets excluded (Task 4). Erasure
  anonymises and preserves audit/consent (D-006).
- Gap (minor): no "admin erasure/export on behalf of a subject" permission — add
  only if support workflow requires it, with mandatory audit.

### 5. Admin Privilege-Escalation Risks — **FAIL → AUTH-AUDIT-02**
- Super Admin creation is controlled (seed / another Super Admin). Good.
- **Gap:** `platform.roles.manage` is granted to BOTH Super Admin and Platform
  Admin. Spatie permits assigning ANY role unless explicitly guarded — so a
  Platform Admin could grant themselves/others the Super Admin role. There is no
  level-based guard yet.
- **Required (Task 5):** a role-assignment policy that (a) only Super Admin may
  assign/revoke the Super Admin role, and (b) no actor may grant a role of higher
  privilege than their own. Plus audit on every assignment (E-CORE-006/007).

### 6. Tenant-Isolation Implications — PASS (with HIGH dependency)
- `tenant_id` exists on all tables; the global `TenantScope` is deferred to Phase 3
  (D-037). Phase 1 is single-tenant (ICS).
- **Consequence:** in Phase 1 there is NO database-level isolation backstop between
  organizations. Isolation between Client Admins (and between Partners) depends
  ENTIRELY on application-layer **ownership/account policies**. If a `client_*` or
  `partner_*` policy is missing or wrong, cross-organization data exposure is
  possible. This elevates the importance of Policy correctness (item 7) to HIGH.

### 7. Policy Architecture — PASS (design) / PENDING (implementation)
- Intended: default-deny base, per-model Policies (viewAny/view/create/update/
  delete), ownership checks for O-type permissions (T-5.4).
- Recommendations:
  - `Gate::before` grants ALL only to Platform Super Admin; everything else explicit.
  - Every model holding org-owned data (client_*, partner_*, startup_*) MUST have a
    Policy enforcing account/owner match — these are the sole Phase 1 isolation
    control (item 6). Require tests.
  - Provide a shared ownership/account-scoping helper to avoid per-policy drift.

### 8. Permission Naming Conventions — **FAIL → AUTH-AUDIT-01**
- PERMISSION_MATRIX (the actual catalogue) uses `module.resource.action`
  (e.g., `crm.leads.create`, `knowledge.tier1.read`, `marketplace.listings.approve`).
- USER_ROLE_MATRIX §6.3 and ENTERPRISE_ARCHITECTURE_BLUEPRINT §6.3 state the
  convention as `action.module.scope` (e.g., `view.crm.all`, `create.crm.leads`).
- These are contradictory. The seeder + every `can()` check depend on exact
  strings; ambiguity here causes silent authorization failures.
- **Required (before Task 5):** choose ONE canonical convention and reconcile all
  docs. Recommendation: adopt `module.resource.action` (PERMISSION_MATRIX) — it is
  the catalogue of record, groups cleanly by module, and scales to future modules.
  Update USER_ROLE_MATRIX §6.3 and Blueprint §6.3 to match.

### 9. Default-Deny Enforcement — PASS (verify by test)
- Design is default-deny; no permission ⇒ denied. RequireMfaForAdmins is an
  additional gate (Task 4).
- Require an explicit test: an authenticated user with no permissions receives 403
  on every protected route (T-5.4 / T-10.1).

### 10. Audit-Logging Requirements — PASS (wiring in Task 6)
- Auth events defined; role lifecycle events (E-CORE-006/007) exist.
- Require: every role/permission assignment and every sensitive-endpoint 403 is
  audited; `platform.audit.read` restricted to Super/Platform Admin. Wire
  LogAuditEvent in Task 6; assignment audit is part of AUTH-AUDIT-02 remediation.

---

## PART B — FINDINGS

### Missing Permissions
| ID | Gap | Severity |
|---|---|---|
| MP-1 | No distinct, level-guarded "assign role" control (escalation guard) | CRITICAL (AUTH-AUDIT-02) |
| MP-2 | No "admin export/erasure on behalf of a data subject" (GDPR support) | LOW |
| MP-3 | No impersonation/support-login permission (with audit) — future support need | LOW (future) |

### Excessive Permissions
| ID | Issue | Severity |
|---|---|---|
| EP-1 | `crm.*.read.all` exposes ALL CRM records to CRM Staff; consider assignment-scoping | MEDIUM |
| EP-2 | `knowledge.tier4.read` granted to Gov Agency Rep — Tier 4 is Partner content (D-036). Confirm Gov should see partner-tier knowledge, or remove | MEDIUM |

### Role Overlaps
| ID | Overlap | Disposition |
|---|---|---|
| RO-1 | Gov Agency Rep ≈ Partner Admin (Tier-4 knowledge, marketplace post+apply, training) | Acceptable; confirm intent |
| RO-2 | Platform Admin ⊃ ICS Staff (CRM/Training/Content) | Acceptable hierarchy |
| RO-3 | Startup Founder ⊃ Startup Member | Acceptable hierarchy |

### Future Scaling Concerns
| ID | Concern |
|---|---|
| FS-1 | Permission catalogue grows large as 7 future modules (D-019) + business modules add permissions — need module-grouped naming + a governance process for additions |
| FS-2 | Phase 3 multi-tenancy/franchise needs tenant-scoped roles — plan Spatie "teams" now (D-021 reserved); franchise admin = tenant-scoped super-admin equivalent |
| FS-3 | Subscription-based tier elevation (D-034/D-036) must compose cleanly with RBAC — define precedence (role OR subscription grants access) |
| FS-4 | When TenantScope activates (Phase 3), audit every org-scoped policy still holds under tenant filtering |

---

## PART C — RECOMMENDATIONS (prioritized)

| # | Recommendation | Priority | Blocks Task 5? |
|---|---|---|---|
| R-1 | Reconcile permission naming → canonical `module.resource.action`; update USER_ROLE_MATRIX §6.3 + Blueprint §6.3 | CRITICAL | YES (AUTH-AUDIT-01) |
| R-2 | Add role-assignment escalation guard (only Super Admin assigns Super Admin; no grant above own level) + audit | CRITICAL | YES (AUTH-AUDIT-02) |
| R-3 | Org-owned Policies (client_/partner_/startup_) enforce account/owner match — sole Phase 1 isolation control; require tests | HIGH | Design now, build in policy tasks |
| R-4 | `Gate::before` grants all ONLY to Super Admin; explicit default-deny everywhere else | HIGH | Task 5 (T-5.4) |
| R-5 | Confirm/scope EP-1 (CRM assignment-scoping) and EP-2 (Gov Tier-4 knowledge) | MEDIUM | Decision needed |
| R-6 | Audit all role/permission changes + sensitive 403s (Task 6 wiring) | MEDIUM | Task 6 |
| R-7 | Quarterly access reviews for Super/Platform Admin; recorded approval for elevated grants | MEDIUM | Governance |
| R-8 | Plan Spatie "teams" for Phase 3 tenant-scoped roles; define subscription↔RBAC precedence | LOW | Phase 2/3 |
| R-9 | Establish permission-catalogue governance (additions reviewed by Architect + Security) | LOW | Ongoing |
| R-10 | Default-deny + escalation-guard tests as CI gates (T-5.4 / T-10.1) | MEDIUM | Task 5/10 |

---

## PART D — REQUIRED BEFORE TASK 5 (the two blockers) — RESOLVED (D-044)

1. **AUTH-AUDIT-01 — RESOLVED.** Canonical convention = `{module}.{resource}.{action}`.
   Blueprint §6.3 reconciled to match PERMISSION_MATRIX. (USER_ROLE_MATRIX did not
   define a separate convention.) The seeder uses this single form.
2. **AUTH-AUDIT-02 — RESOLVED (stricter / four-eyes).** No actor grants above its
   own level; only Super Admin assigns Super Admin AND a Super Admin grant requires
   a second Super Admin approval; all assignments audited. Implemented in Task 5.
3. **EP-2 — RESOLVED.** Gov Agency Rep Tier-4 (Partner) knowledge access removed;
   Gov Reps retain Tier 1 + 2. Matrices updated.

Carried into Task 5 (not blockers): R-3 (org-owned ownership policies — sole Phase 1
isolation control), R-4 (Gate::before for Super Admin only; default-deny), EP-1
(CRM assignment-scoping refinement). Parallel note: Gov Rep Research Tier-3 mirrors
the EP-2 question — confirm if the same restriction should apply (not yet changed).

Status: Both blockers resolved (D-044). Task 5 is unblocked, pending the standing
"await approval before Task 5 implementation" gate.

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Reviewed all four named documents | ✅ |
| 10 validations performed | ✅ |
| Missing / excessive / overlap / scaling identified | ✅ |
| Recommendations provided | ✅ |
| Task 5 NOT implemented | ✅ |

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security Officer | | | | |

**Status:** Awaiting Approval. **Do not implement Task 5 until the two blockers
(AUTH-AUDIT-01, AUTH-AUDIT-02) are resolved.**
