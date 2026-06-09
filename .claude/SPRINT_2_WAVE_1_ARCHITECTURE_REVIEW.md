# SPRINT 2 · WAVE 1 — ARCHITECTURE REVIEW
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Architecture Review — Awaiting Approval (no CMS/CRM implementation yet)
Author: Chief Enterprise Architect
Decision References: D-004, D-012, D-027, D-037, D-038, D-049, D-050

---

## EXECUTIVE SUMMARY

Wave 1 builds the foundations every later business module inherits: the Organisation
Ownership framework (the sole Phase 1 isolation control), the `core_users.account_id`
linkage (D-050), the `AccountScope` global scope, the ownership policy architecture,
and the Unified Content Engine (D-038), with CMS and CRM as the first consumers. This
review validates the design before any code. A key clarification: **org-owned data
(Client/Partner portals) is account-scoped; content (CMS/Knowledge/Research) is
tier-scoped** — two distinct isolation mechanisms. Verdict: **SOUND — proceed to Wave
1 implementation after approval.**

---

## 1. ORGANISATION OWNERSHIP FRAMEWORK

Phase 1 has no database tenant scoping (TenantScope deferred to Phase 3, D-037). The
framework provides **application-layer isolation** in two defence-in-depth layers:

```
Layer 1 — AccountScope (global query scope)
   Org-owned models auto-filter WHERE account_id = <current org user's account_id>.
   → list/index endpoints never return another org's rows.

Layer 2 — Ownership Policies (per record)
   BasePolicy::sameAccount() gates view/update/delete of a single record.
   → direct-id access to another org's record is denied.

Bypass — ICS staff & Super Admin
   Not org-bound (account_id NULL); see across orgs per their permissions
   (Gate::before for Super Admin; permission-gated for staff).
```

Scope of application:
- **Account-scoped (org-owned):** client_projects, client_milestones, client_deliverables,
  client_tickets, partner_referrals/agreements (and any model carrying account_id).
- **NOT account-scoped:** CMS/Knowledge/Research content (tier-scoped via
  ContentAccessService), CRM internal data (ICS-staff-internal, D-012),
  community profiles (public), marketplace listings (published/public).

## 2. account_id AMENDMENT (D-050)

| Requirement | Design |
|---|---|
| Nullable FK initially | `account_id BIGINT UNSIGNED NULL`; NULL = not org-bound |
| References crm_accounts | FK → crm_accounts(id) ON DELETE SET NULL |
| Backward compatible | nullable; existing rows unaffected; inert until consumed |
| Supports TenantScope migration | nests under tenant_id (tenant > account > user) — §  Future |
| Supports BasePolicy | `sameAccount()` reads `user->account_id` |
| Supports AccountScope | scope filters by `account_id` |

**Sequencing (resolves the dependency):** the COLUMN + index are added in Wave 1a
(no FK — crm_accounts not built yet); the FK constraint is added in Wave 1d (CRM).
First full enforcement is Wave 2 (Client/Partner portals). Index: `idx_core_users_account`.

## 3. AccountScope DESIGN

A Laravel global scope applied via a `BelongsToAccount` trait on org-owned models.

```
AccountScope::apply(query):
  user = auth user (if any)
  IF no authenticated user            → no filter (system/jobs/seed)
  IF user is ICS staff or Super Admin → no filter (cross-org per permission)
  IF user.account_id is NULL          → no filter (not org-bound)  [conservative]
  ELSE                                → WHERE account_id = user.account_id
```

Notes:
- Scope is **additive to**, not a replacement for, the policy layer (defence in depth).
- "Org user" = a user whose role is org-bound (Client Admin, Partner Admin) AND has a
  non-NULL account_id. The check is explicit, not role-name-guessing where avoidable.
- Writes also stamp `account_id` from the actor on create (org-owned models).
- A `withoutAccountScope()` escape hatch exists for legitimate cross-org admin/reporting
  paths — permission-gated and audited.

## 4. OWNERSHIP POLICY ARCHITECTURE

- `BasePolicy` (Sprint 1) provides `owns()`, `sameAccount()`, `sameTenant()`.
- Each org-owned model gets a Policy extending BasePolicy: viewAny (permission),
  view/update/delete (permission AND `sameAccount`).
- Default-deny; Super Admin via Gate::before; staff via permissions.
- **Two layers together:** AccountScope prevents *enumeration* (lists); policies
  prevent *direct access* (single record). A test proves both.

## 5. UNIFIED CONTENT ENGINE ARCHITECTURE (D-038)

Shared, single-implementation components for CMS/Knowledge/Research:
- `HasContentLifecycle` — draft → under_review → published → archived; slug; SEO;
  published_at; human-approval publish (P-1).
- `HasFullTextSearch` — consistent FULLTEXT indexing + query (Phase 2 Meilisearch swap).
- `ContentAccessService` — ONE service for BOTH access patterns:
    hierarchical (Research, D-034: user_tier >= content_tier)
    lateral (Knowledge, D-036: role-switch tiers 3/4)
  selected by a strategy flag on the content/module.
- `content_engagement_events` — single polymorphic append-only table (views/downloads),
  replacing per-module duplicates.

**Content is tier-scoped, not account-scoped** — ContentAccessService (not AccountScope)
governs who reads what. This separation is deliberate and central.

## 6. CMS DEPENDENCY ANALYSIS

- Depends on: Core + Content Engine. NOT org-owned (public/tier content).
- Tables: content_pages, content_articles, content_media.
- Consumes HasContentLifecycle + HasFullTextSearch; publish workflow human-approved.
- Events: ArticlePublished. WCAG 2.1 AA (D-028); i18n via translator.
- First consumer of the content engine — validates the abstraction before Knowledge/Research.

## 7. CRM DEPENDENCY ANALYSIS

- Depends on: Core + Org Ownership framework. Internal-only (D-012) — ICS staff.
- Tables: crm_accounts/contacts/leads/opportunities/proposals/contracts/activities.
- **crm_accounts is the organisation anchor** — the FK target for core_users.account_id
  (Wave 1d). A client/partner USER links to a crm_account; that account's portal data
  (Wave 2) is account-scoped to them.
- CRM data itself is staff-internal (not account-scoped); optional assignment-scoping
  (EP-1) is a later refinement.
- AI hooks (lead qualification, proposal generation, digital maturity) are event/service
  seams now; AI logic in the AI sprint (D-029).
- Events: E-CRM-* (audited).

## 8. CROSS-ORGANISATION ISOLATION TEST STRATEGY

For EVERY org-owned model (primarily Wave 2 portals; harness built in Wave 1):
- **Enumeration denial:** Org A user's list/index returns ONLY Org A rows (AccountScope).
- **Direct-access denial:** Org A user requesting Org B record id → 403 (policy).
- **Write stamping:** records created by an org user carry that user's account_id.
- **Staff bypass:** ICS staff / Super Admin see across orgs per permission.
- **NULL safety:** ICS-owned (account_id NULL) data is not leaked to org users.
- **Escape hatch:** withoutAccountScope() only on permission-gated, audited paths.
- Negative tests are primary; cross-org access attempts (403) are audited.

---

## REVIEWS

### Security Review
- Isolation is the SOLE Phase 1 control → built FIRST, two-layer, test-enforced.
- Default-deny; Gate::before Super-Admin-only; staff permission-gated.
- Cross-org 403s audited (security_config); escape hatch audited.
- Content tier access unchanged (ContentAccessService) — no account leakage path.

### Isolation Review
- Two layers (scope + policy) close both enumeration and direct-access vectors.
- Edge cases addressed: unauthenticated/system (no filter), staff (bypass), NULL
  account (no filter, conservative), jobs/seeders (no auth → no filter).
- Risk: a model that is org-owned but forgets the trait/policy → exposure. Mitigation:
  a checklist + a larastan/review rule that org-owned tables (account_id present) MUST
  use BelongsToAccount + a Policy; isolation test required.

### Performance Review
- `account_id` indexed; AccountScope adds `WHERE account_id = ?` (indexed) — negligible.
- Content FULLTEXT on shared hosting: paginate + cache + Cloudflare; Meilisearch Phase 2.
- content_engagement_events append-only; prune via retention (SCAL-03).
- No N+1 introduced by scope; eager-load relations in services.

### Future TenantScope Migration Review (Phase 3)
- Hierarchy: **tenant > account > user**. `tenant_id` (present on all tables) is the
  tenant level; `account_id` is the organisation level within a tenant.
- In Phase 3, activating TenantScope adds `WHERE tenant_id = ?`; AccountScope continues
  to add `WHERE account_id = ?`. They **compose** — a user is filtered by both.
- account_id design is forward-compatible: no rework when TenantScope activates;
  Franchise Operations (D-019) operates at the tenant level above accounts.
- Migration path: set tenant_id on existing rows (ICS = default tenant) → enable
  TenantScope (config) → AccountScope unchanged. Config + data, no schema redesign (D-037).

---

## FINDINGS

| ID | Finding | Severity |
|---|---|---|
| W1-1 | Org-owned models MUST adopt BelongsToAccount + Policy + isolation test — enforce via checklist/lint | HIGH (process) |
| W1-2 | account_id FK depends on crm_accounts (Wave 1d) — column first (1a), FK later (1d) | MEDIUM (sequencing) |
| W1-3 | Content vs org isolation are DIFFERENT mechanisms — document to avoid mis-applying AccountScope to content | MEDIUM (clarity) |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| 8 scope areas reviewed | ✅ |
| Security / Isolation / Performance / TenantScope-migration reviews | ✅ |
| account_id amendment prepared (D-050) | ✅ |
| CMS/CRM NOT implemented | ✅ |

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security Officer | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do not implement CMS or CRM until approved.**
On approval, Wave 1 begins: 1a Org Ownership framework (account_id column + AccountScope
+ policy base + isolation harness) → 1b Content Engine → 1c CMS → 1d CRM (+ account_id FK).
