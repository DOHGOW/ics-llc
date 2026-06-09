# WAVE 2 ARCHITECTURE REVIEW — CLIENT PORTAL + PARTNER PORTAL
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-01
Status: Architecture / Design — Awaiting Approval (NO portal code in this wave)
Author: Lead Architect
Decision References: D-025, D-031, D-037, D-044, D-046, D-050, D-053; Wave 1a ownership framework
Scope under review: Client Portal (client_projects, client_project_milestones,
client_deliverables, client_tickets, client_ticket_replies) and Partner Portal
(partner_tiers, partner_profiles, partner_referrals, partner_agreements)

Interpretation: the deliverable is an ARCHITECTURE REVIEW with an explicit "do not
implement Wave 2 yet / wait for approval after the review" gate. This is the Wave 2 DESIGN;
implementation follows approval.

---

## ⚠ THE PIVOTAL DISTINCTION FROM WAVE 1D

In the **CRM** (Wave 1d, D-053), `account_id` was a SUBJECT pointer and AccountScope was
**forbidden**. In the **Client Portal**, `client_projects.account_id` (NOT NULL → crm_accounts)
is a genuine **OWNERSHIP key**: a project belongs to a client organisation, and a Client
Admin's `core_users.account_id` matches it. **Wave 2 is therefore the FIRST real consumer
of the Wave 1a ownership framework** (D-050: "first full enforcement: Wave 2"). This is
exactly the mechanism that was built and held in reserve — now it activates.

Three mechanisms, now all exercised and kept separate:
| Mechanism | Owner of | Wave 2 use |
|---|---|---|
| **AccountScope** (BelongsToAccount + OrgOwnedPolicy) | org-owned rows by `account_id` | **YES** — the portal isolation control |
| ContentAccessService | content tiers | no (portals are not tiered content) |
| HasAssignmentVisibility | CRM by `assigned_to` | no (internal CRM only) |

---

## EXECUTIVE SUMMARY

Wave 2 exposes two **external-facing, organisation-owned** portals. The Client Portal lets
a client organisation see *its own* projects, milestones, deliverables, and support
tickets. The Partner Portal lets a partner see *its own* profile, referrals (and
commissions), and agreements. Both are isolated by **AccountScope** (Layer 1) + an
**OrgOwnedPolicy** (Layer 2) — the sole Phase-1 isolation control (TenantScope deferred,
D-037). The hardest problems are (a) isolating **child** rows that carry no `account_id`,
(b) the **referral → CRM lead** one-way visibility boundary, and (c) **intra-org**
information leaks (internal ticket replies, draft deliverables). The design resolves all
three. Verdict: **SOUND, conditional on the W2 decisions below.** No code this wave.

---

## 1. CLIENT PORTAL REVIEW

Tables: client_projects, client_project_milestones, client_deliverables, client_tickets,
client_ticket_replies.

| Aspect | Design |
|---|---|
| Ownership | `client_projects.account_id` + `client_tickets.account_id` (NOT NULL → crm_accounts) ARE ownership keys → **BelongsToAccount + AccountScope + OrgOwnedPolicy** |
| Roles | ICS staff (Project Manager / ICS CRM) MANAGE; Client Admin VIEWS own (`client.*.read.own`, `client.*.manage` for ICS) |
| Children | milestones, deliverables, ticket_replies have NO `account_id` — isolated via PARENT (W2-1) |
| Tickets | client raises; staff replies; `is_internal` replies hidden from client (W2-4) |
| Deliverables | files served via policy-gated/signed URLs only (W2-5); drafts hidden until submitted/approved |
| Lifecycle | project planning→active→…→completed; deliverable draft→submitted→approved/rejected (approval audited) |

**W2-1 (child isolation):** milestones/deliverables/replies must never be queried
top-level by an org user. Two options:
- **A. Parent-scoped access (recommended):** children are reachable ONLY through their
  AccountScope-protected parent (nested routes `/projects/{project}/milestones`); the
  controller loads the parent (scoped) then its children; the policy re-checks parent
  ownership. ONE isolation point, no denormalisation.
- B. Denormalise `account_id` onto each child + BelongsToAccount on all. More columns, more
  write-stamping, more drift risk.
Recommendation: **A** — fewer moving parts, single source of truth.

## 2. PARTNER PORTAL REVIEW

Tables: partner_tiers (reference data), partner_profiles, partner_referrals,
partner_agreements.

| Aspect | Design |
|---|---|
| Ownership axis | `partner_profiles.user_id` (NOT NULL) + `account_id` (NULLABLE); referrals/agreements key on `partner_id` → partner_profiles, NOT `account_id` (W2-2) |
| Roles | Partner Admin manages own profile/referrals (`partner.*.own`); ICS staff manage all (`partner.*.read.all`, `partner.agreements.manage`) |
| Tiers | partner_tiers is public-ish reference data (commission_rate, min_referrals) — no per-org isolation |
| Referrals | partner submits → ICS qualifies → becomes a crm_lead (`lead_id`); commission tracked on the referral (D-031) |
| Agreements | partner_agreements files are policy-gated/signed (W2-5) |
| Lifecycle | profile pending→active→…; referral submitted→qualified→converted→lost (approval/commission audited) |

**W2-2 (partner ownership axis):** partner data keys on `partner_id`/`user_id`, while the
isolation framework keys on `account_id`. To keep **one** isolation mechanism, the
recommendation is:
- **Provision a `crm_account` for every partner organisation at onboarding** and set
  `partner_profiles.account_id`; **denormalise `account_id`** onto `partner_referrals` and
  `partner_agreements` so AccountScope applies uniformly (blueprint amendment).
- Individual partners (no org) still get a personal `crm_account` (type='partner'), so
  `account_id` is always present for portal rows → no nullable-ownership edge cases.
This unifies Client + Partner portals under AccountScope. **Decision needed (proposed D-055).**

## 3. ISOLATION REVIEW (the core invariant)

- **Layer 1 — AccountScope** (global scope via BelongsToAccount): org users see only their
  `account_id` rows; ICS_INTERNAL bypass; console bypass; `acrossAccounts()` for audited
  admin/reporting. Applied to: client_projects, client_tickets, partner_profiles, and
  (post-D-055) partner_referrals, partner_agreements.
- **Layer 2 — OrgOwnedPolicy**: every ability gated by BOTH a permission AND
  `accessible()` (internal staff OR sameAccount). Default-deny; Super Admin via Gate::before.
- **Children** isolated via parent (W2-1 Option A).
- **Mandatory isolation tests (W1-1)** per org-owned model: org A cannot read/update/delete
  org B's projects, tickets, deliverables (via parent), partner profiles, referrals,
  agreements. This is a release gate (W2-9).
- AccountScope/ContentAccessService/HasAssignmentVisibility remain **separate**; Wave 2
  touches only AccountScope (correctly) and does not modify the other two.

## 4. SECURITY REVIEW (cross-organisation + intra-organisation)

| Threat | Control |
|---|---|
| Org A reads Org B portal data | AccountScope + OrgOwnedPolicy + isolation tests (W2-9) |
| Child rows accessed cross-org | Parent-scoped access (W2-1) |
| **Partner sees the internal CRM lead** from their referral (W2-3, CRITICAL) | One-way boundary: partner sees `partner_referrals` only; `crm_leads` stays CRM-internal (D-053). The `lead_id` link is readable by ICS only; never serialised to the partner |
| **Client sees internal ticket notes** (W2-4) | `client_ticket_replies.is_internal=1` filtered from all client-facing responses; only staff see internal replies |
| Client sees unfinished deliverables | Draft deliverables hidden until `submitted`/`approved`; status-filtered for client reads |
| Direct file URL access (deliverables, agreements) (W2-5) | Files streamed through a policy-checked controller or short-lived signed URLs; never public `file_path` |
| Privilege drift (org user gains staff powers) | Permissions are role-bound (D-044); org roles hold only `*.own`; no `manage`/`read.all` |
| Partner self-approval / commission tampering | Profile approval + commission set/paid are STAFF-only actions, audited (W2-6) |

## 5. PARTNER REFERRAL ARCHITECTURE

- Flow: **Partner submits referral** (`partner_referrals`, stage=submitted) → **ICS CRM
  qualifies** → a `crm_lead` is created and linked (`partner_referrals.lead_id`) → referral
  stage tracks submitted→qualified→converted→lost → on conversion, **commission** is
  computed from the partner's tier `commission_rate` (D-031) and recorded on the referral.
- **Boundary (W2-3):** the partner side (`partner_referrals`) and the internal side
  (`crm_leads`, assignment-scoped D-053) are deliberately separate tables. The partner sees
  referral stage + commission; the partner NEVER sees the CRM lead, its assignee, pipeline
  value, or notes. ICS staff see both and own the link.
- Commission is a **billing/financial** concern (D-031) → commission set/paid events are
  audited (and likely high-sensitivity). Payment execution itself is deferred to the Billing
  module; Wave 2 records commission_amount/paid_at and emits the event.

## 6. AUDIT REVIEW (D-046)

Portal governance events that must hit the append-only trail:

| Event | Sensitivity |
|---|---|
| Project created / status change / completed | normal |
| Deliverable submitted / **approved / rejected** | normal (approval = governance) |
| Ticket opened / resolved / closed | normal |
| **Partner profile approved / suspended / terminated** | normal→high |
| Referral stage change / **converted** | normal |
| **Commission set / paid** (financial) | **high** |
| Agreement signed | high |

- **Propose `AuditCategory::PORTAL_MANAGEMENT`** (mirrors content_management/crm_management)
  for portal lifecycle + partner-approval events; financial events (commission, agreements)
  flagged high-sensitivity. **Decision needed (proposed D-056).**
- Mechanism reuse: domain events → AuditEventSubscriber → AuditService (synchronous,
  append-only). No new audit infrastructure. All Super Admin actions stay high (automatic).

## 7. ANALYTICS REVIEW (D-025)

- **Client analytics:** project status mix, milestone completion %, deliverable approval
  rate, ticket volume + resolution time (SLA), per `account_id`.
- **Partner analytics:** referral funnel (submitted→converted) conversion rate, commission
  earned, tier progression, per `partner_id`/`account_id`.
- D-025 rule holds: aggregation layer + scheduled jobs; dashboards read **persisted
  aggregates scoped to the org**, never live source-table scans. A client/partner sees only
  their own org's aggregates (the analytics read path is itself account-scoped).
- Permissions `partner.reports.view`, `analytics.partner.reports`, and client reporting
  gate the dashboards.

## 8. FUTURE TenantScope REVIEW (D-037)

- `tenant_id` is present on the parent portal tables (client_projects, client_tickets,
  partner_profiles, partner_referrals, partner_agreements). TenantScope (Phase 3) composes
  ABOVE AccountScope: **tenant > account > user** (D-050 #4).
- Enablement is additive: a TenantScope global scope filters by `tenant_id`; AccountScope
  and the portal policies are unchanged. **No schema change** to enable (D-037).
- Children inherit tenancy via their parent (consistent with W2-1).

---

## VALIDATION MATRIX (as requested)

| Item | Validation | Result |
|---|---|---|
| **Wave 1a Ownership Framework** | BelongsToAccount + AccountScope + OrgOwnedPolicy fit the portals exactly; Wave 2 is their first enforcement | ✅ validated (now activates) |
| **D-050 account_id strategy** | FK active (Wave 1d); client_projects/tickets reference crm_accounts; user.account_id matches | ✅ |
| **D-037 TenantScope roadmap** | tenant_id present; additive; tenant>account>user; no schema change | ✅ |
| **D-025 Analytics Architecture** | per-org aggregation layer; scheduled; org-scoped reads | ✅ |
| **D-046 Audit Architecture** | portal lifecycle + financial events via append-only trail (new category proposed) | ✅ (pending D-056) |

---

## FINDINGS

| ID | Finding | Severity | Disposition |
|---|---|---|---|
| W2-1 | Child rows (milestones/deliverables/replies) have no account_id → isolate via parent | HIGH | Adopt Option A (parent-scoped) |
| W2-2 | Partner ownership keys on partner_id/user_id; account_id nullable → unify under AccountScope | HIGH | Proposed D-055 (provision crm_account per partner; denormalise account_id) |
| W2-3 | Partner must NEVER see the internal crm_lead from their referral | **CRITICAL** | One-way boundary; lead_id ICS-only |
| W2-4 | client_ticket_replies.is_internal must be hidden from clients | HIGH | Filter internal replies on client reads |
| W2-5 | Deliverable/agreement files must be policy-gated/signed, not public | MEDIUM | Stream via policy / signed URLs |
| W2-6 | Partner approval + commission are staff-only, audited (financial = high) | MEDIUM | Proposed D-056 (PORTAL_MANAGEMENT) |
| W2-7 | Multi-user orgs: Client Admin manages own org users (client.users.manage.own); partner multi-user via account_id | MEDIUM | Confirm at implementation |
| W2-8 | client_projects.account_id FK → crm_accounts has no ON DELETE action (RESTRICT) — deleting an account with projects is blocked | LOW | Intended; document |
| W2-9 | Mandatory isolation tests per org-owned model (W1-1) | HIGH | Release gate |

---

## RISKS

| Risk | Mitigation |
|---|---|
| Child-row cross-org leak | Parent-scoped access (W2-1); never expose child top-level routes to org users |
| Referral leaks CRM internals | Separate tables; lead_id ICS-only; partner serialisers exclude CRM fields (W2-3) |
| Internal ticket notes leak to client | is_internal filter at query + serialiser (W2-4) |
| Nullable partner account_id → isolation gap | D-055: always provision account_id for partners |
| Public file URLs expose deliverables | Signed/streamed delivery (W2-5) |
| Financial events unaudited | D-056 PORTAL_MANAGEMENT; commission/agreements high-sensitivity |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Portals are org-owned; AccountScope is the isolation control (Wave 1a activates) | ✅ |
| AccountScope / ContentAccessService / HasAssignmentVisibility remain separate | ✅ |
| Referral→lead one-way boundary preserves CRM internality (D-053) | ✅ design |
| D-050 / D-037 / D-025 / D-046 all validated/compatible | ✅ |
| Wave 2 NOT implemented; no code produced | ✅ |
| D-049 validation gate (bootstrap + GREEN CI) still in force | ⚠ carried |

---

## REVIEW VERDICT

**SOUND DESIGN — conditional on approving the W2 decisions.** Wave 2 correctly activates
the long-held Wave 1a ownership framework as the portal isolation control, keeps the three
isolation mechanisms separate, preserves the CRM internality boundary across the referral
seam, and remains TenantScope/Analytics/Audit-ready. Cleared to proceed to Wave 2
implementation **after** approval and the decisions below.

Pending approvals to record on sign-off:
- **D-055 (proposed):** Provision a `crm_account` per partner (org or individual); set
  `partner_profiles.account_id`; denormalise `account_id` onto `partner_referrals` and
  `partner_agreements` so AccountScope is the single portal isolation mechanism (W2-2).
- **D-056 (proposed):** `AuditCategory::PORTAL_MANAGEMENT` for portal lifecycle + partner
  approval; commission/agreement events high-sensitivity (W2-6).
- **W2-1 decision:** child isolation via parent-scoped access (Option A, recommended).

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement Wave 2 until approved.**
