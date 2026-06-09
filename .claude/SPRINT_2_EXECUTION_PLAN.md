# SPRINT 2 EXECUTION PLAN — BUSINESS MODULES
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: PLANNING — Awaiting Approval (no module implementation until gate + approval)
Author: Chief Enterprise Architect
Decision References: D-010…D-013, D-027, D-030/D-033/D-034/D-036, D-038, D-049
Inputs: MODULE_DEPENDENCY_DIAGRAM, BUSINESS_CAPABILITY_MAP, DATABASE_BLUEPRINT,
EVENT_CATALOG, AUTHORIZATION_SECURITY_AUDIT (R-3)

---

## ⛔ VALIDATION GATE (D-049) — must pass before MAJOR module development

| # | Gate | Source |
|---|---|---|
| 1 | Bootstrap (composer/npm/artisan/env) | Go-Live A |
| 2 | Database (migrate + seed + RBAC verified) | Go-Live A |
| 3 | Conformance (6 Task-10 suites green) | Task 10.1 |
| 4 | CI (PHPUnit, Pint, Larastan, driver-gate, audit, gitleaks, engine-parity) | Governance §7 |
| 5 | Host (capability spike, intl, proxy, mail) | Spike / Go-Live |
| 6 | Go-Live checklist signed | SPRINT_1_GO_LIVE_CHECKLIST |

Planning may proceed now. **No CRM/CMS/etc. code merges until the gate passes.**

---

## 1. MODULE BUILD ORDER

Driven by MODULE_DEPENDENCY_DIAGRAM (everything depends on Core; modules depend only
on lower levels). Sprint 2 is sequenced in **waves** to control scope and risk.

```
WAVE 0 — GATE
  Validation gate (above) + Sprint 2 design sign-off.

WAVE 1 — FOUNDATIONS OF BUSINESS DATA  (highest priority — your focus)
  1a. Organisation Ownership Policy framework  (isolation — sole Phase 1 control)
  1b. Unified Content Engine (D-038)           (shared by CMS/Knowledge/Research)
  1c. Corporate Website / CMS                  (first content-engine consumer)
  1d. CRM (Internal)                           (leads→accounts→opportunities→contracts)

WAVE 2 — CRM-DEPENDENT MODULES
  2a. Client Portal      (depends on CRM accounts/contracts)
  2b. Partner Portal     (referrals → CRM; org-owned)

WAVE 3 — CONTENT MODULES (reuse the content engine)
  3a. Knowledge Center   (tiered, D-033/D-036)
  3b. Research Center     (tiered, D-030/D-034)

WAVE 4 — ECOSYSTEM MODULES
  4a. Opportunity Marketplace
  4b. Community Module
  4c. Training Institute (free courses; paid → Phase 2 billing)
```

Rationale: build the **isolation framework and content engine first** so every later
module inherits correct, tested ownership and a single content implementation.

---

## 2. DEPENDENCIES

| Module | Depends on (must exist first) | Integration (Events) |
|---|---|---|
| Org Ownership framework | Core (BasePolicy, User) | — |
| Unified Content Engine | Core | — |
| CMS | Core, Content Engine | content events |
| CRM | Core, Org Ownership | LeadCreated, ContractSigned… (E-CRM-*) |
| Client Portal | Core, CRM, Org Ownership | ProjectCreated, TicketCreated |
| Partner Portal | Core, CRM, Org Ownership | ReferralSubmitted→CreateCRMLead |
| Knowledge Center | Core, Content Engine | ArticlePublished/Downloaded |
| Research Center | Core, Content Engine | PublicationPublished/Downloaded |
| Marketplace | Core, Org Ownership | ListingApproved, ApplicationSubmitted |
| Community | Core | ProfileCreated→CreateCRMLead(consultant) |
| Training | Core (+Billing P2) | CourseEnrolled, CourseCompleted |

Rule (D-027): no module queries another module's tables; communication is Events only.

---

## 3. RISKS

| ID | Risk | Severity | Mitigation |
|---|---|---|---|
| S2-1 | Org isolation is the SOLE Phase 1 control (TenantScope deferred). A missing/incorrect policy = cross-organisation data exposure | HIGH | Build the ownership framework + isolation tests FIRST (Wave 1a); every org-owned model ships a tested policy |
| S2-2 | User→organisation linkage not yet modelled (BasePolicy::sameAccount needs `account_id`) | HIGH | Early Wave-1 decision: add `core_users.account_id` (or org-membership) — schema amendment, sign-off required |
| S2-3 | Content-engine over/under-abstraction across CMS/Knowledge/Research | MEDIUM | Build engine WITH CMS as first consumer; refactor when Knowledge lands; avoid speculative generality |
| S2-4 | Scope creep across 8 modules | HIGH | Strict wave sequencing + per-module review gate (Sprint-1 cadence) |
| S2-5 | FULLTEXT search + more tables on shared hosting | MEDIUM | Paginate + cache + Cloudflare; Meilisearch in Phase 2 |
| S2-6 | AI hooks (lead qualification, content drafting) intersect CRM/CMS | MEDIUM | Build data + event hooks now; AI use cases wired in the AI sprint (D-029) — not in Wave 1 |
| S2-7 | Cross-module event chains harder to debug as modules multiply | MEDIUM | Keep EVENT_CATALOG current; failed-listener alerting; idempotent listeners |

---

## 4. ISOLATION STRATEGY (the critical one)

Phase 1 has **no database tenant scoping** (TenantScope deferred to Phase 3, D-037).
Therefore application-layer **ownership policies are the sole control** preventing one
organisation from seeing another's data (AUTHORIZATION_SECURITY_AUDIT R-3 / RA-2).

Design:
1. **User↔Org linkage (S2-2):** add `core_users.account_id` (nullable FK → crm_accounts)
   so Client/Partner users belong to an organisation. Schema amendment — sign-off first.
2. **BasePolicy helpers** (already built): `owns()`, `sameAccount()`, `sameTenant()`.
   Org-owned policies MUST use `sameAccount()` for every read/write.
3. **Per-model policies** for every org-owned model (client_projects, client_tickets,
   crm_* visible to org, partner_* …). Default-deny; Super Admin via Gate::before.
4. **Query scoping:** controllers/services filter by the actor's `account_id` (a global
   `AccountScope` for org-owned models, analogous to the future TenantScope) so list
   endpoints never leak other orgs' rows.
5. **Mandatory isolation tests** (see §5): a Client Admin of Org A is DENIED any access
   to Org B's records — for every org-owned model.
6. **Audit:** cross-org access attempts (403) are audited (security_config category).

This makes isolation **provable**, not assumed, despite the deferred DB scope.

---

## 5. TESTING STRATEGY

Per module (CI gates, Governance §7):
- **Schema conformance:** migrations match DATABASE_BLUEPRINT (names/types/indexes).
- **Isolation tests (HIGH priority):** cross-account access denied for every org-owned
  model; list endpoints return only the actor's org rows.
- **Policy/default-deny tests:** no permission → 403; correct permission → allowed.
- **Event/audit coverage:** each module event dispatched + audited (sensitivity correct).
- **Capability tests:** BUSINESS_CAPABILITY_MAP items behave as specified.
- **Content engine tests:** lifecycle (draft→published), tiered access (hierarchical +
  lateral), engagement events, no duplicated logic across CMS/Knowledge/Research.
- **Boundary tests (D-027):** no direct cross-module table/model references (larastan
  rule + review).

Cross-cutting: extend the CI suite per module; the gate stays green to merge.

---

## 6. ACCEPTANCE CRITERIA

A Sprint 2 module is DONE when:
- [ ] Schema matches DATABASE_BLUEPRINT; no orphan tables; tenant_id present.
- [ ] Capabilities (BUSINESS_CAPABILITY_MAP) delivered and tested.
- [ ] Org-owned data: ownership policy enforced + **isolation tests green** (no cross-org access).
- [ ] Default-deny + permission gating per PERMISSION_MATRIX.
- [ ] Cross-module integration via Events only (no direct queries); events audited.
- [ ] No business logic in controllers; services/repositories used.
- [ ] WCAG 2.1 AA on any UI; i18n via translator; config-only runtime preserved.
- [ ] CI green (conformance + isolation + boundary + security).
- [ ] Module implementation review approved (Sprint-1 cadence: build → review → sign-off).

---

## 7. FOCUS DETAIL

### 7.1 Unified Content Engine (D-038) — build in Wave 1b
Shared components (no triplication across CMS/Knowledge/Research):
- `HasContentLifecycle` trait — draft → under_review → published → archived; slug; SEO.
- `HasFullTextSearch` trait — consistent FULLTEXT indexing + query (Phase 2 Meilisearch swap).
- `ContentAccessService` — ONE service evaluating BOTH access patterns: hierarchical
  (Research, D-034) and lateral (Knowledge, D-036), selected by a strategy flag.
- `content_engagement_events` — single polymorphic append-only table (views/downloads)
  replacing per-module duplicates.
Acceptance: a content-policy/lifecycle change is made ONCE; CMS is the first consumer.

### 7.2 Corporate Website / CMS — Wave 1c
- `content_pages`, `content_articles`, `content_media` (DATABASE_BLUEPRINT).
- Uses the content engine (lifecycle, SEO, FULLTEXT). Human-approval publish (P-1).
- Events: ArticlePublished. WCAG 2.1 AA (D-028); i18n-ready.

### 7.3 CRM (Internal) — Wave 1d
- `crm_accounts/contacts/leads/opportunities/proposals/contracts/activities`.
- Internal-only (D-012); ICS Staff roles; PERMISSION_MATRIX Module 4.
- Lead pipeline + contract lifecycle; events E-CRM-* (audited).
- AI hooks (lead qualification, proposal generation, digital maturity) are EVENT/
  service seams now; AI logic in the AI sprint (D-029).
- `crm_accounts` becomes the organisation anchor for `core_users.account_id` (§4/S2-2).

### 7.4 Organisation Ownership Policies — Wave 1a (FIRST)
- Schema: `core_users.account_id` linkage (amendment — sign-off).
- `AccountScope` global scope for org-owned models; per-model policies via BasePolicy.
- Isolation test harness reused by Client Portal, Partner Portal, and any org-owned data.
- This is the foundation that makes every later org-owned module safe.

---

## 8. PROPOSED SPRINT 2 SEQUENCE (review-gated, Sprint-1 cadence)

Each item: pre-build review → implement → implementation review → approval → next.

1. Validation gate (D-049) + Sprint 2 design sign-off (incl. S2-2 account_id amendment).
2. Org Ownership framework + isolation harness.
3. Unified Content Engine + CMS.
4. CRM.
5. Client Portal · Partner Portal.
6. Knowledge Center · Research Center.
7. Marketplace · Community · Training (free).

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Technical Lead | | | | |
| Security Officer | | | | |

**Status:** Planning complete. Awaiting approval. **No business-module implementation
begins until the D-049 validation gate passes AND this plan is approved.**
First amendment to sign off at Sprint 2 start: `core_users.account_id` (S2-2, isolation).
