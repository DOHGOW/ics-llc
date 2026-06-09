# ACCESS CONTROL CONSOLIDATION REVIEW
# ICS Enterprise Ecosystem Platform — Mechanism Inventory & Maintainability

Version: 1.0
Date: 2026-06-03
Status: Architecture review — no code. Inventory + consolidation recommendation.
Author: Lead Architect
Governing decisions: D-044 (RBAC), D-050, D-051, D-053, D-055, D-057, D-060; D-037 (TenantScope reserved)

---

## PURPOSE

The platform now has **six** module access mechanisms plus a reserved seventh (TenantScope).
This review inventories every mechanism, validates each for necessity / scope / overlap /
future maintainability, and recommends whether any should be consolidated or remain separate —
so that Wave 5+ does not accrete redundant access logic.

---

## INVENTORY

| # | Mechanism | Layer | Keyed on | Used by | Decision |
|---|---|---|---|---|---|
| 1 | **AccountScope** (+BelongsToAccount, OrgOwnedPolicy) | global scope + policy | `account_id` (org) | Client Portal, Partner Portal | D-050, D-055 |
| 2 | **ContentAccessService** (Hierarchical + Lateral strategies) | service | content `access_tier` + role | CMS, Knowledge, Research | D-038, D-051 |
| 3 | **HasAssignmentVisibility** | query scope | `assigned_to` / `created_by` | CRM | D-053 |
| 4 | **TrainingAccessService** | service | enrollment (join table) | Training | D-057 |
| 5 | **Community visibility** (scopeVisibleTo + owner) | query scope | `visibility` + `user_id` | Community | D-057 |
| 6 | **Marketplace listing-status** | service + scope | `status` + owner/applicant | Marketplace | D-057, D-060 |
| 7 | **TenantScope** (RESERVED) | global scope | `tenant_id` (tenant) | (Franchise, Phase 3) | D-004, D-037 |

Underneath all of them: **RBAC permissions** (Spatie, `module.resource.action`, D-044) +
`Gate::before` Super Admin bypass + default-deny. Permissions answer "may this ROLE do this
ACTION"; the seven mechanisms answer "may this user see this ROW/RESOURCE".

---

## VALIDATION — NECESSITY, SCOPE, OVERLAP

### By family
The seven cluster into **five distinct families**, each answering a different question:

| Family | Question | Mechanisms |
|---|---|---|
| **Org isolation** | "is this row owned by the user's organisation?" | AccountScope; (TenantScope = the tenant tier of the same axis) |
| **Content tiering** | "does the user's tier reach the content's tier?" | ContentAccessService |
| **Assignment** | "is this internal record assigned to / created by the user?" | HasAssignmentVisibility |
| **Membership / participation** | "is the user a participant in this relationship?" | TrainingAccessService (+ future Startup/Investment) |
| **Visibility / status** | "is this row publicly/owner-visible given its visibility/status?" | Community visibility; Marketplace listing-status |

### Necessity (each is load-bearing — none redundant)
| Mechanism | Necessary because | Could another cover it? |
|---|---|---|
| AccountScope | org-to-org isolation across portals | No — content/assignment/membership don't isolate orgs |
| ContentAccessService | role/tier content gating (2 strategies) | No — not org, not membership |
| HasAssignmentVisibility | internal CRM (account_id is a SUBJECT there, D-053) | No — AccountScope would mean the wrong thing on CRM |
| TrainingAccessService | enrollment gates paid lessons | No — not a tier, not org |
| Community visibility | public/authenticated identity | No — not a tier (no lifecycle), not org |
| Marketplace listing-status | review/publish workflow + private applications | No — distinct state machine + applicant privacy |
| TenantScope (reserved) | franchise tenant isolation (nests above account) | No — sits above all six |

### Scope (no scope creep / clean boundaries)
- Each mechanism is **module-local except the two global scopes** (AccountScope, TenantScope).
- They were deliberately kept separate at each wave (D-053 forbade CRM using AccountScope;
  D-057 forbade Training/Community/Marketplace using the content engine). This separation is the
  reason no single change can silently broaden access across modules.

### Overlap analysis (the only real adjacency)
- **Membership family overlap:** TrainingAccessService and the future Startup/Investment access
  checks all ask "is the user a participant?" via a join table. This is the ONE place where code
  *shape* repeats. It is **shape overlap, not scope overlap** — the tables and rules differ
  (enrollment vs team membership vs data-room grant).
- **Org axis "overlap":** AccountScope and TenantScope are the SAME axis at two levels
  (tenant > account). They COMPOSE (D-050 #4), they don't conflict — TenantScope wraps
  AccountScope; both can be active simultaneously.
- **No other overlaps.** ContentAccessService / HasAssignmentVisibility / Community / Marketplace
  are mutually disjoint in both data and intent.

---

## CONSOLIDATION RECOMMENDATION

### Keep all SEVEN separate. Do NOT merge.
Merging would couple unrelated modules and re-introduce exactly the conflation each decision was
written to prevent (e.g., CRM ≠ AccountScope per D-053). The separation is a feature: it bounds
blast radius and keeps each rule auditable. **Recommendation: remain separate.**

### One bounded improvement (optional, low-risk): a shared contract for the membership family
To stop the membership/participation pattern drifting as Startup Hub and Investment Network land,
introduce a **thin conformance contract** (NOT a merged implementation):

```
interface ParticipationGate {
    public function participates(?User $user, Model $context): bool;  // is the user a participant?
}
```
- TrainingAccessService, the future StartupAccessService, and InvestmentAccessService each
  implement it with their own join-table logic. No shared state, no coupling — just a uniform
  shape + a single place to assert the family's invariants (e.g., "staff/owner bypass; default
  deny; never falls back to AccountScope").
- This is a maintainability aid, not a consolidation. It is OPTIONAL; if the team prefers zero
  shared surface, the family can stay fully independent with a documented pattern instead.

### TenantScope activation guidance (Franchise)
- Activate as a **global scope composing ABOVE AccountScope** (tenant_id filter applied first).
- Do NOT modify the existing six; TenantScope is additive (every table already has tenant_id,
  D-037). Requires its own tenancy security review + exhaustive cross-tenant isolation tests.

---

## FUTURE MAINTAINABILITY ASSESSMENT

| Concern | Status | Action |
|---|---|---|
| Mechanism proliferation | Controlled — 5 families, no new family needed for Wave 5 | reuse membership family |
| Drift within membership family | Low risk, rising as Startup/Investment land | optional ParticipationGate contract |
| Global-scope composition (account↔tenant) | Designed (D-050 #4) | verify with tests at Franchise |
| "Which mechanism applies here?" clarity | High — each module's wave doc states it | keep documenting per module |
| Accidental cross-module access | Prevented by separation + per-module tests (W1-1, W2-9) | continue isolation tests per model |
| RBAC vs mechanism confusion | Clear split (role-action vs row-visibility) | keep both layers |

**Governance guardrail (recommend adopting as a standing rule):** every new module's
architecture review MUST state which of the seven mechanisms it uses, and MUST justify any
proposal for a new one against this inventory (the "mandatory validation" already used in Wave 4).
This review formalises that as the canonical checklist.

---

## VERDICT

**Seven mechanisms, five families — all necessary, scopes clean, only the membership family
shows (benign) shape overlap.** Recommendation: **keep all separate**; optionally add a thin
`ParticipationGate` contract for the membership family as Wave 5 introduces Startup Hub and
Investment Network; activate the reserved TenantScope only with Franchise under a dedicated
tenancy review. No consolidation/merge is warranted or advisable.
