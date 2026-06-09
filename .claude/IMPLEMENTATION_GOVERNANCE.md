# IMPLEMENTATION GOVERNANCE
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: Governing Document — effective from first line of implementation code
Owner: Lead Architect
Decision References: Constitution Parts IV–VII, D-021, D-027, D-037, D-038, D-039

---

## EXECUTIVE SUMMARY

This document governs HOW the platform is built. The architecture documents define
WHAT to build; this defines the rules, roles, gates, and controls under which
implementation proceeds. It is binding on every contributor from the first line of
code through Phase 1 launch and into VPS migration.

Its purpose: ensure that what gets built matches the approved architecture, that no
decision is silently bypassed, that quality and security are gates not afterthoughts,
and that the config-only VPS migration guarantee (D-037) survives contact with real
code.

Core promise: **No code is written that is not traceable to an approved decision,
verified by an automated gate, and documented.**

---

## 1. GOVERNING HIERARCHY (ORDER OF AUTHORITY)

When two sources conflict, the higher one wins.

```
1. PROJECT_CONSTITUTION.md        ← supreme; principles P-1…P-10
2. DECISION_LOG.md (D-001…D-040)  ← approved decisions
3. ENTERPRISE_ARCHITECTURE_BLUEPRINT.md
4. Module specification docs       ← Capability Map, Role/Permission Matrix,
                                      Event Catalog, Data Flow, DATABASE_BLUEPRINT
5. Sprint plans                    ← e.g. PHASE_1_SPRINT_1_IMPLEMENTATION_PLAN
6. Application code                ← the implementation
```

Code may never contradict a document above it. If reality demands a contradiction,
the document is amended FIRST (Section 6), then the code follows. Never the reverse.

---

## 2. ROLES & RESPONSIBILITIES

| Role | Owns | Authority |
|---|---|---|
| Platform Owner | Vision, budget, go-live, risk acceptance | Approves decisions, phase gates, risk sign-offs |
| Lead Architect | Architecture integrity, decisions, reviews | Approves designs, amendments, merges to main |
| Technical Lead | Delivery, code quality, CI gates | Approves PRs, enforces standards |
| DevOps / Operator | Hosting, deployment, spike, migration | Runs spike, deploys, owns env config |
| Developer(s) | Feature implementation | Builds to spec; raises conflicts, never bypasses |
| Security Officer | NDPA/GDPR, OWASP, WCAG, audit | Approves security-affecting changes |

Rule: a contributor who finds a conflict between the spec and reality STOPS and
escalates (Section 9) — they do not "just make it work" by deviating.

---

## 3. THE GOLDEN RULES (from Constitution Part IV)

Binding on every task. A change violating any of these is rejected at review.

1. No feature without an approved decision (Decision ID or Capability ID).
2. No duplicate functionality — reuse the shared engine (D-038) and existing services.
3. No code that bypasses the architecture (no direct cross-module DB queries; Events only — D-027).
4. No undocumented features — docs updated in the same change.
5. No technical debt introduced silently — debt is logged and approved or not taken.
6. No security traded for convenience — D-039 baseline is non-negotiable.
7. No broken naming conventions (Blueprint §3.3).
8. No orphan tables — every table is in DATABASE_BLUEPRINT and owned by a module.
9. No hardcoded infrastructure drivers — config-driven runtime (D-037).
10. No content/access logic re-implemented per module — unified engine (D-038).

---

## 4. PHASE & SPRINT GATES

Work flows through gates. A gate must be signed before the next stage starts.

```
GATE 0 — Host Capability Review (HOSTINGER_CAPABILITY_SPIKE)
   ↓  APPROVED (real spike results, all CRITICAL PASS)
GATE 1 — Sprint Ready (Definition of Ready met, prerequisites confirmed)
   ↓
[ SPRINT BUILD ]
   ↓
GATE 2 — Sprint Done (Definition of Done met, all quality gates green)
   ↓
GATE 3 — Phase 1 Launch Readiness (security pass, pre-SLO declared)
   ↓
GATE 4 — VPS Migration (triggers met; VPS_MIGRATION_CHECKLIST executed)
```

| Gate | Owner sign-off | Blocks |
|---|---|---|
| 0 Host Capability | Owner + Architect + Tech Lead + DevOps | All implementation |
| 1 Sprint Ready | Architect + Tech Lead | Sprint start |
| 2 Sprint Done | Tech Lead (+ Architect for module sprints) | Next sprint |
| 3 Launch Readiness | Owner + Architect + Security Officer | Go-live |
| 4 VPS Migration | Owner + Tech Lead + DevOps | Cutover |

**Current state: GATE 0 is OPEN. No implementation may begin until it is signed
APPROVED with real spike results.**

---

## 5. DEFINITION OF READY / DONE

### Definition of Ready (before a task is started)
- [ ] Traces to a Decision ID and/or Capability ID
- [ ] Spec exists in the relevant architecture doc
- [ ] Dependencies (per MODULE_DEPENDENCY_DIAGRAM) are complete
- [ ] Acceptance criteria written
- [ ] No unresolved conflict with a higher governing document

### Definition of Done (before a task is merged)
- [ ] Meets acceptance criteria
- [ ] Conforms to naming conventions and module boundaries
- [ ] Unit + feature tests written and passing
- [ ] Automated quality gates green (Section 7)
- [ ] Security baseline items applicable to the change satisfied (D-039)
- [ ] Documentation updated (spec, and Decision Log/Memory if behavior changed)
- [ ] Reviewed and approved by the role required for that change type (Section 8)

---

## 6. CHANGE CONTROL & AMENDMENTS (Constitution Part VI)

Architecture changes during build are expected. They are governed, not forbidden.

```
Proposed change
  → Impact assessment vs all approved decisions
  → Conflict / risk identified
  → Explicit approval (Architect; Owner if scope/cost/security)
  → New or updated Decision ID
  → Update affected architecture documents
  → Update PROJECT_MEMORY.md
  → THEN implement
```

Rules:
- A developer NEVER amends approved architecture unilaterally in code.
- Minor clarifications: Architect may approve and log.
- Structural changes (Constitution Parts I–V, schema, security, deployment model):
  require Owner approval and a Decision ID.
- Every amendment is traceable: which decision, why, what it supersedes.

---

## 7. AUTOMATED QUALITY GATES (CI — must be green to merge)

| Gate | Enforces | Decision |
|---|---|---|
| Hardcoded-driver grep | No queue/cache/session/filesystem/mail literal outside config/ | D-037 |
| ShouldQueue check | Heavy/non-instant listeners implement ShouldQueue | D-037 |
| Feature-flag guard | Deferred runtime behaviour wrapped in config('ics.*') | D-037 |
| Test suite | Unit + feature pass; coverage threshold on auth/RBAC/audit | — |
| Schema conformance | Migrations match DATABASE_BLUEPRINT (table/column/index names) | D-002 |
| Module boundary | No cross-module model/table reference; Events only | D-027 |
| Mass-assignment | $fillable defined; no $guarded=[] | Blueprint §14 |
| Security headers | HSTS/CSP/X-Frame/X-Content-Type present | D-039 |
| Accessibility | UI components pass WCAG 2.1 AA checks | D-028 |
| Secrets scan | No secrets committed; .env not in repo | D-039 |

A red gate blocks the merge. Gates are not waived to "ship faster."

---

## 8. CODE REVIEW & APPROVAL MATRIX

| Change type | Required approver(s) |
|---|---|
| Routine feature within a module | Technical Lead |
| New module / cross-module Event | Lead Architect |
| Database migration / schema | Lead Architect |
| RBAC roles or permissions | Lead Architect + Security Officer |
| Security / auth / audit / PII path | Security Officer + Lead Architect |
| Deployment / env / runtime config | Technical Lead + DevOps |
| Architecture amendment | Lead Architect (+ Owner if structural) |
| Merge to `main` (production) | Lead Architect |

Every Pull Request MUST state, in its description, the Decision ID(s) and
Capability ID(s) it implements. PRs without traceability are rejected unreviewed.

---

## 9. ESCALATION & EXCEPTIONS

Escalate (do not improvise) when:
- The spec conflicts with reality or with another approved document.
- A host limitation blocks a planned approach (log it in the Limitations Register).
- A security or compliance control cannot be met as specified.
- A change would introduce duplication, debt, or a boundary violation.

Process:
```
STOP → document the conflict + options → notify the role that owns the area
     → decision recorded (Decision ID if architectural) → proceed per decision
```

Temporary exceptions (rare) require: written justification, an expiry date, a
tracking entry, and Architect (or Owner) approval. No silent or permanent exceptions.

---

## 10. SECURITY & COMPLIANCE CHECKPOINTS

Continuous, not a final-stage audit:

| Checkpoint | When | Standard |
|---|---|---|
| Auth/session/MFA review | Sprint 1 + any auth change | D-039, OWASP |
| RBAC least-privilege audit | Each role/permission change | D-021 |
| Audit-log immutability proof | Sprint 1 + before launch | D-039 SEC-03 |
| NDPA/GDPR data-subject flows | Before handling real PII | D-006 |
| Gemini PII redaction + DPA | Before any AI on user data | D-039 SEC-04 |
| WCAG 2.1 AA component check | Every UI component | D-028 |
| Prompt-injection hardening | Each AI use case | D-039 SEC-05 |
| Dependency / secrets scan | Every CI run | D-039 |

---

## 11. RISK & LIMITATIONS MANAGEMENT

- HOSTINGER_LIMITATIONS_REGISTER.md is a LIVING document. Any new host limitation
  found during build is logged with impact + workaround + VPS resolution.
- PROJECT_MEMORY.md risk register is reviewed at every Sprint Done gate; risks are
  opened, downgraded, or closed with evidence.
- A new CRITICAL risk pauses dependent work until mitigated or accepted by the Owner.

---

## 12. DOCUMENTATION DISCIPLINE

- Code and docs change together. A behavior change with stale docs fails review.
- Decision Log is append-only history; superseded decisions are marked, not deleted.
- Every new table, event, role, permission, or capability appears in its canonical
  document before or with the code that introduces it.
- The Architecture Review Report findings are tracked to closure; resolved findings
  are marked with the Decision ID that resolved them.

---

## 13. DEPLOYMENT GOVERNANCE

- Branching: GitHub Flow (Blueprint §15.2). `main` = production-ready only.
- Promotion: feature → staging (verify) → main (deploy). No direct prod edits.
- Config-only migration discipline (D-037): the ONLY difference between shared and
  VPS is `.env`. A change that requires code/schema difference between environments
  is a governance violation and is rejected.
- Every deploy: migrate, cache config/routes/views, verify cron + queue, smoke test.
- Backups verified before any migration or destructive operation.

---

## 14. CADENCE

| Ceremony | Frequency | Purpose |
|---|---|---|
| Sprint Ready review | Per sprint start | Gate 1 |
| Daily standup | Daily | Surface blockers/conflicts early |
| Sprint Done review | Per sprint end | Gate 2; risk register review |
| Architecture checkpoint | Every 2 sprints | Drift check vs Blueprint |
| Security checkpoint | Per security-affecting change | Section 10 |
| Phase readiness review | End of Phase 1 | Gate 3 |

---

## 15. AUTHORITY OF THIS DOCUMENT

This document is subordinate to the Constitution and Decision Log and supersedes ad
hoc practice. It takes effect the moment Gate 0 (Host Capability Review) is approved
and the first implementation task begins. Amendments to it follow Section 6.

---

## APPROVAL SECTION

| Role | Name | Signature | Date |
|---|---|---|---|
| Platform Owner | | | |
| Lead Architect | | | |
| Technical Lead | | | |
| Security Officer | | | |
| DevOps / Operations | | | |

**Status:** Governing Document — awaiting ratification alongside Gate 0 approval.
