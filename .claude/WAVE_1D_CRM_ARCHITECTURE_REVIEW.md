# WAVE 1D ARCHITECTURE REVIEW — CRM (INTERNAL ENTERPRISE CRM)
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-01
Status: Architecture / Design — Awaiting Approval (NO CRM code in this wave)
Author: Lead Architect
Decision References: D-012, D-025, D-037, D-044, D-049, D-050; Wave 1a isolation framework
Scope under review: crm_accounts, crm_contacts, crm_leads, crm_opportunities,
crm_activities, crm_notes

Interpretation: the named deliverable is an ARCHITECTURE REVIEW with an explicit
"do not implement CRM yet / wait for approval after the review" gate. This is the
Wave 1d DESIGN; implementation follows approval.

---

## ⚠ TWO BLOCKING ITEMS TO RESOLVE BEFORE IMPLEMENTATION

Per the project constitution (identify conflicts; stop and explain; do not proceed until
approved), two items must be decided at approval:

1. **`crm_*.account_id` is a SUBJECT pointer, NOT an ownership/isolation key (W1d-1, CRITICAL).**
   It means "which account this CRM record is *about*," whereas `core_users.account_id`
   (D-050) means "which organisation the *viewing user belongs to*." They are different
   concepts that both happen to reference `crm_accounts(id)`. **CRM must NOT use
   BelongsToAccount / AccountScope in Phase 1** — doing so would mix the two isolation
   mechanisms the owner has repeatedly required be kept separate.

2. **`crm_notes` is in the Wave 1d scope but is NOT in DATABASE_BLUEPRINT (W1d-2, CONFLICT).**
   Notes are currently modelled as `crm_activities.type = 'note'`. Options below; a
   decision is required before implementation.

Everything else is sound and ready.

---

## EXECUTIVE SUMMARY

Wave 1d designs the **Internal Enterprise CRM** (D-012) — leads, opportunities, accounts,
contacts, activities — managed by ICS staff (the `ICS — CRM` role). It is **not a
client-facing CRM** (D-012 explicitly: "Client-Facing CRM: Not Required"). The central
architectural finding: **CRM visibility is ASSIGNMENT-scoped and STAFF-internal, not
organisation-scoped.** CRM therefore stands apart from BOTH existing isolation mechanisms
(AccountScope and ContentAccessService) and introduces a third, simpler model:
permission + assignment. Wave 1d also activates the deferred D-050 FK
(`core_users.account_id → crm_accounts(id)`) now that `crm_accounts` is created.

Verdict: **SOUND DESIGN, conditional on resolving W1d-1 and W1d-2 at approval.** No code is
produced in this wave.

---

## 1. ACCOUNT OWNERSHIP ARCHITECTURE

`crm_accounts` is the **organisation entity itself** — the row that `core_users.account_id`
points to. It cannot be "owned" via AccountScope (it would have to scope to itself). It is
ICS master data describing external organisations (clients, prospects, partners, government,
NGOs, SMEs, startups — per the `type` enum).

| Table | What `account_id` means here | Ownership model |
|---|---|---|
| crm_accounts | (it IS the account) | ICS master data; `assigned_to` = relationship owner (staff) |
| crm_contacts | the account this contact belongs to | child of crm_accounts (FK) |
| crm_leads | the account this lead concerns | child/related (FK, nullable) |
| crm_opportunities | the account this opportunity concerns | child/related (FK, nullable) |
| crm_activities | (via polymorphic subject) | belongs to its subject (lead/opp/account) |
| crm_notes | (see W1d-2) | belongs to its subject |

**Conclusion:** CRM rows are ICS-internal records *about* external organisations. The
"owner" in the operational sense is the **assigned staff member** (`assigned_to`), not an
external organisation. This is the basis for the Access Model in §4.

## 2. account_id FK ACTIVATION STRATEGY (D-050 step 2)

D-050 deferred the `core_users.account_id` **FK constraint** to Wave 1d because
`crm_accounts` did not yet exist. Wave 1d activates it:

- **Migration ordering (mandatory):** create `crm_accounts` FIRST, then add the FK
  `core_users.account_id → crm_accounts(id) ON DELETE SET NULL` in a later-timestamped
  migration. The column + index already exist (Wave 1a); this wave adds only the constraint.
- **ON DELETE SET NULL** (not CASCADE): deleting a CRM account must never delete the user
  accounts of its members — it detaches them (they become unbound, like ICS staff).
- **No data backfill needed** in Phase 1 (org users are introduced with the Client/Partner
  portals in Wave 2; existing rows are ICS staff with `account_id = NULL`).
- **Idempotent + reversible** migration (drop FK in `down()`), MySQL-guarded as needed for
  the SQLite test DB (FK semantics differ).

This is the literal realisation of D-050 requirement #2 and sequencing step "Wave 1d."

## 3. ORGANISATION ISOLATION REVIEW

**CRM is NOT org-isolated in Phase 1 — by design (D-012).** External organisations have no
CRM access at all until the Client/Partner portals (Wave 2). Therefore:

| Control | Applies to CRM in Phase 1? | Rationale |
|---|---|---|
| AccountScope (BelongsToAccount) | **NO** (W1d-1) | `crm_*.account_id` is a subject pointer, not an owner key; CRM is staff-internal |
| ContentAccessService (tiering) | NO | CRM is not tiered content |
| **Permission + assignment scoping** | **YES** (the CRM model, §4) | the correct Phase-1 control for an internal CRM |

The two existing isolation mechanisms remain **untouched and unmixed** by Wave 1d — which is
the highest-priority invariant. AccountScope continues to guard org-owned portal models
(Wave 2); ContentAccessService continues to guard CMS/Knowledge/Research. CRM adds a third,
orthogonal control that does not touch either.

> Forward note (Wave 2): when a client portal exposes *a client's own* CRM-derived records
> (e.g. their tickets/projects), those PORTAL models — not the internal CRM tables — will be
> org-owned via BelongsToAccount. The internal CRM tables are never directly exposed to
> external orgs.

## 4. CRM ACCESS MODEL + STAFF vs ORGANISATION VISIBILITY RULES

**Principle:** access = `permission` (only CRM/admin roles hold `crm.*`) **AND** `assignment
scope`.

Permissions are already seeded (D-044) as `crm.<resource>.read.all` etc. EP-1 (noted in
D-049) flagged that `read.all` must be assignment-refined. Wave 1d resolves EP-1:

| Capability | Holder | Visibility |
|---|---|---|
| `crm.*.read.all` | CRM Manager / Platform Admin / Super Admin | entire pipeline (all assignees) |
| `crm.*.read.own` (NEW, EP-1) | CRM Rep / staff | only rows where `assigned_to = user.id` (or created_by) |
| create / update / delete | per existing `crm.*` perms | + on `.own` variants, only assigned rows |
| External organisation users | — | **NO CRM access** (Phase 1, D-012) |

- A thin **CrmVisibility query scope** (e.g. `scopeVisibleTo($user)`) applies the
  assignment filter when the user lacks `read.all`. This is NOT AccountScope and does NOT
  use `account_id` — it filters on `assigned_to`/`created_by`. (Resolves EP-1.)
- **Staff vs organisation:** staff see CRM by assignment/permission; organisations see
  nothing. There is no "same organisation" rule in CRM Phase 1 (that rule belongs to the
  Wave 2 portals, via AccountScope, on different tables).
- Super Admin bypasses via `Gate::before`; default-deny everywhere else.

> Recommendation: add the two `read.own` permissions (`crm.accounts.read.own`,
> `crm.leads.read.own`, …) OR implement assignment-scoping behind the existing `read.all`
> with an explicit "own-only" fallback when `read.all` is absent. Either is acceptable;
> the permission-explicit form is cleaner for the PERMISSION_MATRIX. **Decision needed.**

## 5. PIPELINE ARCHITECTURE

- **Lead pipeline** (`crm_leads.stage`): new → contacted → qualified → proposal →
  negotiation → closed_won / closed_lost.
- **Opportunity pipeline** (`crm_opportunities.stage`): qualification → proposal →
  negotiation → closed_won / closed_lost.
- **Lead → Opportunity conversion:** a qualified lead spawns an opportunity
  (`crm_opportunities.lead_id` FK). Conversion is an explicit, audited action.
- **Activities** (`crm_activities`, polymorphic subject) record calls/emails/meetings/
  tasks/demos against a lead/opportunity/account — the engagement timeline.
- `probability`, `value`, `currency`, `expected_close_date`/`close_date` support
  weighted-pipeline reporting (feeds Analytics, §7).
- **AI hooks (D-029) are present but OUT OF SCOPE this wave:** `ai_qualification_score`,
  `crm.leads.qualify.ai`, proposals — deferred to the AI sprint. Wave 1d builds the manual
  pipeline only.
- **Out of Wave 1d scope (blueprint has them, scope list does not):** `crm_proposals`,
  `crm_contracts`. Deferred to a later CRM wave (likely with AI proposal generation, D-029).
  Flagged so the deferral is explicit, not accidental (W1d-6).

## 6. AUDIT REVIEW (D-046)

CRM carries material governance events that must hit the immutable trail:

| Event | Suggested sensitivity |
|---|---|
| Account created / deleted | normal / high (delete) |
| Lead/Opportunity stage change (esp. closed_won / closed_lost) | normal |
| Assignment change (`assigned_to`) | normal (accountability) |
| Lead → Opportunity conversion | normal |
| Contract changes (when contracts land, later wave) | high |

- **No CRM audit category exists yet.** Recommend adding `AuditCategory::CRM_MANAGEMENT`
  (mirroring the W1c `content_management` pattern) — **a new decision is required** (W1d-5).
- Mechanism reuse: fire domain events (e.g. `LeadStageChanged`, `OpportunityWon`,
  `CrmRecordAssigned`) → `AuditEventSubscriber` handlers → append-only `AuditService`
  (synchronous, D-046). No new audit infrastructure; same pattern as Wave 1c.
- All Super Admin CRM actions remain high-sensitivity automatically (AuditService rule).

## 7. ANALYTICS REVIEW (D-025)

- CRM is a primary feeder of the **central analytics layer** (D-025): cross-module
  aggregation tables + DB views, **separate from the CRM source tables**, refreshed by
  Laravel Task Scheduling (cron), surfaced on the Executive Dashboard (Chart.js).
- Phase-1 CRM KPIs: pipeline value by stage, win/loss rate, conversion rate, leads by
  source, average deal size, activities per rep, stage ageing.
- **Rule:** dashboards read **aggregates**, never run heavy ad-hoc scans on `crm_leads`/
  `crm_opportunities` per request. Aggregation is scheduled; reads are cheap.
- Permission `analytics.crm.reports` already seeded; `crm.reports.view` / `crm.reports.export`
  gate in-module reporting.
- Stage-transition history is the analytic backbone — recommend the `LeadStageChanged` /
  opportunity events (also used for audit, §6) double as the analytics signal. No separate
  event table needed in Phase 1; the aggregation job reads current state + activity rows.

## 8. FUTURE TenantScope MIGRATION REVIEW (D-037)

- Every CRM table carries **`tenant_id`** (nullable, indexed) — already in the blueprint.
  TenantScope (Phase 3) nests ABOVE account-level: `tenant > account > user`.
- Because CRM does NOT use AccountScope, the Phase-3 change is purely additive: a
  TenantScope global scope filters by `tenant_id`; no CRM access logic is reworked
  (the assignment-scoping is orthogonal and survives unchanged).
- **No schema change** is required to enable tenancy (D-037 guarantee): the columns exist;
  `.env` + the scope class flip it on. CRM is tenancy-ready by construction.
- D-050 composition holds: `core_users.account_id` (org) sits under `tenant_id` (tenant);
  the Wave 1d FK does not impede the future composite scope.

---

## VALIDATION MATRIX (as requested)

| Item | Validation | Result |
|---|---|---|
| **D-050 account_id strategy** | FK activation deferred to Wave 1d; ON DELETE SET NULL; column/index pre-exist | ✅ realised this wave (§2) |
| **Wave 1a isolation framework** | AccountScope/BelongsToAccount/OrgOwnedPolicy NOT applied to CRM; left intact | ✅ untouched & unmixed (§3) |
| **D-037 future TenantScope** | tenant_id present; additive scope; no rework; no schema change | ✅ compatible (§8) |
| **D-012 CRM scope** | internal enterprise CRM only; no client-facing CRM; leads/opps/accounts/contracts/renewals | ✅ honoured (scope-aligned) |
| **D-025 analytics architecture** | separate aggregation layer; cron; executive dashboard; Chart.js | ✅ compatible (§7) |

---

## FINDINGS

| ID | Finding | Severity | Disposition |
|---|---|---|---|
| W1d-1 | `crm_*.account_id` is a subject pointer, not an owner key → CRM must NOT use AccountScope/BelongsToAccount; access is assignment-scoped | **CRITICAL** | Decide at approval; prevents mixing isolation mechanisms |
| W1d-2 | `crm_notes` in scope but absent from blueprint (notes = crm_activities type='note') | **HIGH (conflict)** | Decide at approval (options below) |
| W1d-3 | Activate D-050 FK `core_users.account_id → crm_accounts`; ordering crm_accounts-first; ON DELETE SET NULL | MEDIUM | Implement in Wave 1d |
| W1d-4 | Resolve EP-1: add `crm.*.read.own` (assignment scope) vs `read.all` | MEDIUM | Decide permission form at approval |
| W1d-5 | No CRM audit category — propose `AuditCategory::CRM_MANAGEMENT` (new decision) | MEDIUM | Approve new category |
| W1d-6 | crm_proposals / crm_contracts in blueprint but OUT of Wave 1d scope | LOW | Confirm deferral |
| W1d-7 | crm_activities has no soft-deletes / no assigned-scope index — minor hardening | LOW | Address at implementation |

### W1d-2 resolution options (crm_notes)

| Option | Description | Recommendation |
|---|---|---|
| **A. No new table** | Keep notes as `crm_activities.type='note'` (blueprint-consistent; zero duplication) | **Recommended for Phase 1** — aligns with the "no duplicate features" rule |
| B. Dedicated polymorphic `crm_notes` | `notable_type`/`notable_id`, body, created_by; richer, pinnable, separate from timeline | Choose only if notes need semantics distinct from the activity timeline |

If the owner wants `crm_notes` as a first-class table (as the scope list implies), Option B
is clean and polymorphic — but it is a deliberate divergence from the current blueprint and
should be ratified as a blueprint amendment + decision, not assumed.

---

## RISKS

| Risk | Mitigation |
|---|---|
| Naive BelongsToAccount on CRM leaks/breaks visibility (staff see nothing; or wrong-org semantics) | W1d-1: explicitly forbid AccountScope on CRM; assignment-scope instead |
| `read.all` over-exposure across reps | W1d-4: assignment-scoped `read.own`; default to own-only without `read.all` |
| Unaudited pipeline/assignment changes | W1d-5: CRM_MANAGEMENT category + domain events → audit |
| Dashboard heavy queries on growing pipeline | D-025 aggregation layer; scheduled, not per-request |
| FK CASCADE accidentally deleting users | ON DELETE SET NULL on the D-050 FK |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| CRM is internal-only (D-012); no client-facing CRM | ✅ |
| AccountScope + ContentAccessService untouched and unmixed | ✅ |
| D-050 FK activation strategy defined (Wave 1d) | ✅ |
| TenantScope-ready (D-037); no schema change to enable | ✅ |
| Analytics via D-025 aggregation layer (not source-table scans) | ✅ |
| CRM NOT implemented; no code produced this wave | ✅ |
| D-049 validation gate (bootstrap + GREEN CI) still in force | ⚠ carried |

---

## REVIEW VERDICT

**SOUND DESIGN — conditional on approving W1d-1 (CRM is assignment-scoped, NOT
account-scoped) and resolving W1d-2 (crm_notes).** The internal CRM correctly stands
outside both existing isolation mechanisms, adds an orthogonal permission+assignment
control, activates the D-050 FK, and remains TenantScope- and Analytics-ready. Cleared to
proceed to Wave 1d implementation **after** approval and the two decisions above.

Pending approvals to record on sign-off:
- **D-053 (proposed):** CRM access model — internal-only, assignment-scoped; CRM tables do
  NOT use AccountScope; `crm_*.account_id` is a relationship pointer (resolves W1d-1/W1d-4).
- **D-054 (proposed):** `AuditCategory::CRM_MANAGEMENT` for CRM lifecycle/assignment/stage
  events (resolves W1d-5).
- **W1d-2 decision:** crm_notes Option A (activity type) or Option B (dedicated table).

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement CRM until approved.**
