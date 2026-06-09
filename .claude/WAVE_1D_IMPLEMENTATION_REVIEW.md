# WAVE 1D IMPLEMENTATION REVIEW — CRM (INTERNAL ENTERPRISE CRM)
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-01
Status: Implementation complete — Awaiting Approval (STOP before Wave 2)
Author: Lead Architect
Decision References: D-012, D-025, D-037, D-044, D-046, D-050, D-053, D-054; W1d-1..W1d-7
Design baseline: WAVE_1D_CRM_ARCHITECTURE_REVIEW.md (approved)

---

## EXECUTIVE SUMMARY

Wave 1d delivers the **Internal Enterprise CRM** (D-012): accounts, contacts, leads,
opportunities, and a polymorphic activity timeline. The defining property — proven in
code — is that **CRM visibility is permission + assignment-scoped, NOT organisation-
scoped**. CRM introduces a third, orthogonal isolation control (`HasAssignmentVisibility`)
that is provably separate from AccountScope and ContentAccessService, neither of which is
touched. The D-050 FK (`core_users.account_id → crm_accounts`) is activated. Stage,
assignment, and conversion events are audited under the new `crm_management` category.
A scheduled-friendly analytics aggregation hook feeds the D-025 layer.

**Verdict: IMPLEMENTATION SOUND — all approved decisions (D-053, D-054, W1d-2/4/6)
realised.** Standing caveat unchanged: overlay must bootstrap + run GREEN in CI
(MySQL, for the FK + engine parity) before being declared operationally done (R-012/R-013).

---

## DELIVERABLES

| Layer | Artifact |
|---|---|
| Migrations | crm_accounts, crm_contacts, crm_leads, crm_opportunities, crm_activities; **+ D-050 FK activation** on core_users (MySQL-guarded) |
| Models | Crm\Account, Contact, Lead, Opportunity, Activity |
| Concern | `HasAssignmentVisibility` (scopeVisibleTo / visibleToUser — D-053; NOT AccountScope) |
| Service | Crm\CrmService (stage transitions, assignment, lead→opportunity conversion) |
| Analytics | Crm\CrmPipelineAggregator (D-025 hook; scheduled snapshot) |
| Events | Crm\{LeadStageChanged, OpportunityStageChanged, CrmRecordAssigned, LeadConverted, CrmAccountDeleted} |
| Audit | `AuditCategory::CRM_MANAGEMENT` + 5 AuditEventSubscriber handlers/subscriptions |
| Controllers | Crm\{Account, Contact, Lead, Opportunity, Activity, CrmReport}Controller |
| Routes | routes/crm.php (registered in bootstrap/app.php; all behind auth:sanctum) |
| Permissions | `crm.*.read.own` added (seeder + ICS_CRM grant) — W1d-4 |
| Docs | DECISION_LOG (D-053/D-054/W1d-2/6), DATABASE_BLUEPRINT (CRM notes), this review, PROJECT_MEMORY |

---

## 1. CRM ARCHITECTURE VALIDATION

| Check | Result | Evidence |
|---|---|---|
| Internal-only CRM (D-012) | ✅ | All routes `auth:sanctum` + `crm.*` perms; only ICS roles hold them; no external exposure |
| Thin controller → CrmService → model | ✅ | Stage/assign/convert all flow through `CrmService` |
| Five entities in scope built; proposals/contracts deferred (W1d-6) | ✅ | No crm_proposals/crm_contracts migrations this wave |
| Notes = activity type (W1d-2) | ✅ | `Activity::TYPES` includes `note`; no crm_notes table |
| Pipelines modelled | ✅ | `Lead::STAGES`, `Opportunity::STAGES`; conversion via `convertLead()` |
| Lead → Opportunity conversion atomic + audited | ✅ | `DB::transaction`; fires LeadConverted + closes lead |
| Subject binding is whitelisted (no arbitrary class) | ✅ | `ActivityController::SUBJECTS` maps slug → model class |
| Default-deny + permission-gated | ✅ | Every action `abort_unless($user->can(...))`; Super Admin via Gate::before |

## 2. ACCESS CONTROL VALIDATION (D-053 / W1d-4)

| Check | Result | Evidence |
|---|---|---|
| Permission + assignment model | ✅ | `canAny([...read.all, ...read.own])` to enter; `visibleTo()` filters rows |
| `read.own` = assigned_to OR created_by | ✅ | `HasAssignmentVisibility::scopeVisibleTo` |
| `read.all` = full pipeline | ✅ | Scope returns unfiltered when `$user->can('crm.*.read.all')` |
| Per-record guard on show/update/delete | ✅ | `visibleToUser($user)` mirrors the scope |
| Permissions seeded + granted | ✅ | `crm.*.read.own` added to PermissionSeeder + ICS_CRM map |
| No auth → nothing | ✅ | scope returns `1=0`; routes also require auth:sanctum |
| External orgs have no CRM access | ✅ | Org roles hold no `crm.*` permission |

## 3. AUDIT VALIDATION (D-054 / D-046)

| Check | Result | Evidence |
|---|---|---|
| `crm_management` category added | ✅ | `AuditCategory::CRM_MANAGEMENT` |
| Lead/Opportunity stage changes audited | ✅ | handleLeadStageChanged / handleOpportunityStageChanged (before/after stage) |
| Assignment changes audited | ✅ | handleCrmRecordAssigned (before/after assigned_to) |
| Lead conversion audited | ✅ | handleLeadConverted (new opportunity_id) |
| Account deletion audited | ✅ | handleCrmAccountDeleted (fired before soft delete) |
| Fired once, in the service (not controllers) | ✅ | Events fire inside CrmService; delete event in controller pre-delete |
| Append-only + Super-Admin high-sensitivity | ✅ | AuditService unchanged (D-046 invariants intact) |
| Actor + record captured | ✅ | actorId/role from the acting user; record_type+id = model class+key |

## 4. ANALYTICS VALIDATION (D-025)

| Check | Result | Evidence |
|---|---|---|
| Aggregation hook present | ✅ | `CrmPipelineAggregator::snapshot()` |
| Separate from source tables; scheduled, not per-request | ✅ | Service intended for a scheduled job; documented; dashboards read persisted aggregates |
| KPIs computed | ✅ | pipeline value by stage (lead+opp), leads by source, win/loss, conversion |
| In-module report endpoint gated | ✅ | `CrmReportController::pipeline` behind `crm.reports.view` |
| Stage events double as analytics signal | ✅ | LeadStageChanged/OpportunityStageChanged carry from→to (D-025 + D-054) |
| No heavy per-request scan on read paths | ✅ | List endpoints `select()` minimal columns + paginate; aggregation is offloaded |

## 5. ISOLATION VALIDATION (the critical invariant)

| Check | Result | Evidence |
|---|---|---|
| CRM does NOT use AccountScope | ✅ | No `BelongsToAccount` on any Crm\* model; no AccountScope import |
| CRM does NOT use ContentAccessService | ✅ | No content-engine traits/services in CRM |
| `crm_*.account_id` treated as subject, never owner | ✅ | `HasAssignmentVisibility` filters on assigned_to/created_by, never account_id |
| AccountScope code untouched this wave | ✅ | No edits to AccountScope/BelongsToAccount/OrgOwnedPolicy |
| ContentAccessService untouched this wave | ✅ | No edits to the content engine |
| Three mechanisms remain distinct | ✅ | AccountScope (org-owned portals) · ContentAccessService (tiers) · assignment (CRM) |

> This is the highest-priority Sprint 2 invariant (separation of isolation mechanisms).
> Wave 1d adds a mechanism without mixing the existing two.

## 6. FUTURE TenantScope VALIDATION (D-037)

| Check | Result | Evidence |
|---|---|---|
| `tenant_id` on every CRM table | ✅ | Present + indexed on all five tables |
| Tenancy is additive (no rework) | ✅ | Assignment-scoping is orthogonal to tenant_id; a future TenantScope composes above it |
| No schema change to enable tenancy | ✅ | Columns exist; Phase-3 enablement is scope-class + .env (D-037) |
| D-050 composition intact | ✅ | account (org) nests under tenant; the new FK does not impede a composite scope |
| FK is reversible + env-safe | ✅ | MySQL-guarded; `down()` drops it; SQLite tests rely on the column only |

---

## account_id FK ACTIVATION (D-050 step 2) — VERIFICATION

| Item | Result |
|---|---|
| crm_accounts created BEFORE the FK (migration ordering) | ✅ 000001 (table) → 000006 (FK) |
| FK `core_users.account_id → crm_accounts(id)` | ✅ `fk_core_users_account` |
| ON DELETE SET NULL (never cascade-delete users) | ✅ `nullOnDelete()` |
| Column/index pre-existed (Wave 1a) — only constraint added | ✅ no column re-add |
| Reversible + MySQL-guarded | ✅ guarded `up()`/`down()` |

---

## OWNERSHIP-FRAMEWORK COMPATIBILITY

- The Wave 1a ownership framework (AccountScope/BelongsToAccount/OrgOwnedPolicy) is
  **untouched and remains available** for the Wave 2 portals, which WILL be org-owned.
- CRM deliberately does not consume it (D-053). When a Wave 2 client portal exposes a
  client's *own* records, those PORTAL models — not these internal CRM tables — use
  BelongsToAccount. The internal CRM is never directly exposed to external orgs.
- `BasePolicy` helpers (`owns`, `sameAccount`, `sameTenant`) remain intact; CRM uses the
  assignment concern instead, which is the correct control for an internal CRM.

---

## FINDINGS DISPOSITION

| ID | Finding | Status |
|---|---|---|
| W1d-1 | CRM assignment-scoped, not account-scoped | ✅ realised (D-053; HasAssignmentVisibility) |
| W1d-2 | crm_notes = activity type | ✅ realised (Activity TYPES; no table) |
| W1d-3 | Activate D-050 FK (ordering, SET NULL) | ✅ done (migration 000006) |
| W1d-4 | read.own vs read.all | ✅ done (perms seeded + scope) |
| W1d-5 | CRM_MANAGEMENT audit category | ✅ done (D-054; 5 handlers) |
| W1d-6 | Defer proposals/contracts | ✅ deferred (not built) |
| W1d-7 | crm_activities soft-deletes + assignment index | ✅ added (migration + blueprint) |

### Correctness decisions made during implementation (self-flagged)

1. **AI columns present but inert** — `crm_leads.ai_qualification_score/at` exist (schema
   parity) but are NOT written by Wave 1d; the AI sprint (D-029) populates them. No
   `qualify.ai` endpoint built this wave.
2. **Stage is not mass-assignable via update()** — `LeadController::update` /
   `OpportunityController::update` deliberately exclude `stage`; it can only change through
   the audited `changeStage()` path, so no stage transition escapes the audit trail.
3. **Activity subject binding whitelisted** — polymorphic `subject_type` is resolved from
   a fixed slug→class map, never from raw client input (prevents arbitrary morph binding).
4. **Delete event fired before soft delete** — `CrmAccountDeleted` captures the account
   name into the audit before the row is soft-deleted.

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| CRM internal-only (D-012); no client-facing CRM | ✅ |
| AccountScope + ContentAccessService untouched and unmixed | ✅ |
| D-050 FK activated (ordering + SET NULL) | ✅ |
| Audit (crm_management) + analytics hook live | ✅ |
| TenantScope-ready (D-037); no schema change to enable | ✅ |
| Wave 2 NOT implemented | ✅ |
| Bootstrap + GREEN CI still required before "done" (R-012/R-013) | ⚠ carried |

---

## REVIEW VERDICT

**IMPLEMENTATION SOUND.** The internal CRM is built as designed: permission +
assignment-scoped, provably outside both existing isolation mechanisms, with the D-050 FK
activated, CRM lifecycle audited under `crm_management`, and a D-025 analytics hook in
place. Cleared for approval.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **STOP — do not begin Wave 2 (Client/Partner Portal) until
approved.**
