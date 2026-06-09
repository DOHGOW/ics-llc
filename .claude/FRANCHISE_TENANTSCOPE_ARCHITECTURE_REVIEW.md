# FRANCHISE OPERATIONS / TENANTSCOPE ACTIVATION — ARCHITECTURE REVIEW
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-04
Status: Architecture review — NO code/migrations/models/scopes/services. Design only.
Author: Lead Architect
Validates against: D-004, D-019, D-037, D-046, D-050, D-053, D-025; all six access mechanisms
Inputs: ACCESS_CONTROL_CONSOLIDATION_REVIEW, ECOSYSTEM_ROADMAP_REVIEW, DATABASE_BLUEPRINT, all wave reviews

> **This validates ACTIVATION of the reserved TenantScope — the load-bearing reservation present
> since D-004/D-019/D-037/D-050.** `core_tenants` has existed since the first migration; `tenant_id`
> is already on every owned parent table. TenantScope is an ADDITIVE global scope, not a redesign.

---

## EXECUTIVE SUMMARY

TenantScope activation is **architecturally low-risk and additive**: the registry (`core_tenants`)
and the `tenant_id` columns already exist (38 parent tables carry it); children inherit tenancy via
their parents (the proven W2-1 parent-isolation pattern); and the activation is a config-gated global
scope composing ABOVE AccountScope (tenant → account → user, D-050 #4). No module requires redesign.
The real work is operational: a default-tenant/backfill strategy, a reference-data tenancy decision,
exhaustive cross-tenant isolation tests, a controlled super-tenant bypass, and (for true multi-tenant
+ data residency) the VPS/region step. **Outcome: CONDITIONAL GO.**

---

## 1. TENANTSCOPE ACTIVATION STRATEGY

- A `TenantScope` global scope applied via a `BelongsToTenant` trait on tenant-scoped PARENT models
  (mirrors how `BelongsToAccount` applies `AccountScope`). It filters `WHERE tenant_id = <current
  tenant>`.
- **Config-gated (D-037):** `config('ics.tenancy.enabled')` from `.env`. Disabled (shared hosting) →
  single-tenant; the scope is a no-op / resolves the default tenant. Enabled (VPS/franchise) →
  multi-tenant. No schema change to flip — the columns already exist.
- **Tenant resolution:** by authenticated user's `tenant_id`, by domain/subdomain (`core_tenants.domain`),
  or by an explicit context — resolved once per request (middleware), like locale (Task 8).
- **Controlled bypass:** ICS platform/super admin (HQ) may operate cross-tenant (audited); console/
  system context bypasses (migrations/jobs), exactly as AccountScope does.

## 2. FRANCHISE HIERARCHY MODEL

- A **tenant = a franchise operator** (a `core_tenants` row). Phase-1 ICS data = the default/root tenant.
- **Optional regional hierarchy:** add `core_tenants.parent_tenant_id` (a self-FK) for regional
  franchises under a national franchise (multi-country, §14). Decision point (D-079).
- Tenant lifecycle: trial → active → suspended (status already on core_tenants).

## 3. TENANT OWNERSHIP MODEL

- A tenant is owned/operated by a **Franchise Admin** (a new role within the tenant) under ICS HQ
  oversight. Tenant provisioning + settings/branding (`core_tenants.settings` JSON) are HQ/Franchise-
  Admin actions, audited.
- ICS HQ (super-tenant) retains cross-tenant governance (controlled bypass, §8/§9).

## 4. TENANT ↔ ACCOUNT RELATIONSHIP (Test D)

- **tenant 1—N crm_accounts.** `account_id` (D-050) remains the ORG key WITHIN a tenant; `tenant_id`
  is the OUTER key. Composition: `tenant > account > user` (D-050 #4). **AccountScope semantics
  unchanged** — it still filters org-owned rows by `account_id`; TenantScope adds the tenant filter
  above it. (Test D: D-050 correct + intact.)

## 5. TENANT ↔ USER RELATIONSHIP

- **tenant 1—N users.** `core_users.tenant_id` already exists. A user belongs to exactly one tenant
  (a franchise's user base). Cross-tenant users (ICS HQ staff) are the controlled-bypass exception.

## 6. TENANT ↔ ORGANISATION RELATIONSHIP

- An "organisation" = a `crm_account` (client/partner org) and lives WITHIN a tenant
  (`crm_accounts.tenant_id`). Client/Partner portals (AccountScope) operate inside the tenant; a
  franchise's clients/partners are isolated from another franchise's by TenantScope above AccountScope.

## 7. CROSS-TENANT ISOLATION (the #1 risk)

- **TenantScope is the outer wall;** every tenant-scoped parent query is tenant-filtered. Children
  inherit via parent (never queried top-level cross-tenant — the W2-1 discipline, already enforced).
- **Exhaustive isolation tests are the release gate:** tenant A must never read/write tenant B's
  rows across EVERY module (CRM, portals, content, training, community, marketplace, startup,
  programs). Mirrors the per-model W1-1/W2-9 isolation-test mandate, raised to the tenant axis.
- Super-tenant bypass is the ONLY cross-tenant path and is permission-gated + audited.

## 8. TENANT ADMINISTRATION

- **Franchise Admin** role (tenant-scoped admin): manages the tenant's users, settings, branding,
  and franchise-local configuration — but only within their tenant.
- **ICS HQ (super-tenant):** provisions/suspends tenants, cross-tenant reporting, controlled bypass.
- Tenant provisioning, suspension, settings changes, and any cross-tenant action are audited (§9).

## 9. AUDIT ARCHITECTURE (D-046)

- `core_audit_logs.tenant_id` already exists; `AuditService.log()` already accepts `$tenantId`.
- Per-tenant audit trails; **cross-tenant (super-tenant) actions = HIGH-sensitivity**; tenant
  provisioning/suspension audited. Propose surfacing tenant context on every audit record (set the
  tenantId from the resolved tenant). No new category required (reuse existing categories + tenant
  dimension); a `TENANT_MANAGEMENT` category is optional for provisioning events (decision).

## 10. ANALYTICS ARCHITECTURE (D-025)

- All aggregators gain a **tenant dimension** (group/filter by tenant_id); per-tenant dashboards see
  ONLY their tenant; ICS HQ sees a cross-tenant (super-tenant) roll-up.
- Aggregation tables (analytics_snapshots, etc.) need a `tenant_id` dimension (they currently target a
  single platform) — a schema addition for franchise analytics (decision/G).

## 11. BILLING IMPLICATIONS (D-031)

- **Per-tenant billing:** each franchise is billed (franchise/licence fees, usage); billing rows are
  tenant-scoped. The Billing wave must be tenant-aware from the start. Franchise revenue-share +
  per-tenant invoicing is a Billing-wave design input (no conflict; additive).

## 12. MIGRATION STRATEGY (Test C — config-only)

- **Default-tenant + backfill (D-077 proposed):** create a default/root tenant; backfill existing
  rows' `tenant_id` to it (existing data = the ICS root tenant); existing single-tenant behaviour
  unchanged. Then add `BelongsToTenant` to scoped models + the TenantScope class; flip
  `ics.tenancy.enabled` via `.env`.
- **No schema redesign** — only (a) optional reference/analytics `tenant_id` additions (G) and (b)
  the scope/trait code. **Test C: D-037 config-only remains TRUE** (columns exist; enablement is .env;
  the migration is additive backfill + scope wiring, not a redesign).

## 13. PERFORMANCE IMPACT

- TenantScope adds a `tenant_id` predicate to every scoped query. **Indexing:** ensure `tenant_id`
  (and composite `(tenant_id, status)`, `(tenant_id, slug)` etc.) indexes on hot tables; several
  parents index tenant_id already, others need it (G). Query-plan review before enable.
- A `(tenant_id, account_id)` composite supports the nested scopes efficiently.
- Connection-per-tenant vs shared-schema: recommend **shared schema + row-level TenantScope** (Phase
  3 default); database-per-tenant is a later scale option (no app redesign — D-037).

## 14. FUTURE MULTI-COUNTRY EXPANSION

- A tenant per country/region (or the regional `parent_tenant_id` hierarchy, §2). **Data residency
  (B-2):** some jurisdictions require in-country hosting → region-pinned tenants / regional VPS
  (D-037 Phase 2→3); analytics gain a country/region dimension.
- Localization (D-014) is already per-locale; per-tenant default locale via `core_tenants.settings`.

## 15. DISASTER RECOVERY CONSIDERATIONS

- **Per-tenant backup/restore + export** (a franchise's data as a unit) — feasible because every
  scoped row carries tenant_id; supports tenant offboarding/portability (privacy/exit).
- Tenant-scoped point-in-time restore; cross-tenant blast-radius containment (a corrupted tenant
  doesn't affect others). DR runbook gains a tenant dimension.

---

## MANDATORY VALIDATION (A–G)

### A. TenantScope additive (tenant → account → user); does NOT replace AccountScope
✅ **CONFIRMED.** TenantScope is a NEW global scope composing ABOVE AccountScope (D-050 #4).
AccountScope continues to isolate org-owned rows by account_id WITHIN a tenant. Both scopes coexist.

### B. All existing modules compatible without redesign
✅ **CONFIRMED.** CRM, Client/Partner Portal, CMS, Knowledge, Research, Training, Community,
Marketplace, Startup Hub, Program Architecture all carry tenant_id on their parents and use access
mechanisms that operate WITHIN a tenant. Adding TenantScope above them requires the trait + scope only
— no module redesign. The six access mechanisms are unaffected (they run inside the tenant).

### C. D-037 config-only migration remains true
✅ **CONFIRMED.** Columns exist; enablement is `.env`; the migration is additive (default-tenant
backfill + scope wiring), not a redesign. Shared-hosting = single tenant; VPS/franchise = multi-tenant.

### D. D-050 account_id architecture remains correct
✅ **CONFIRMED.** account_id stays the org key within a tenant; tenant nests above. No change to
AccountScope/BelongsToAccount/OrgOwnedPolicy.

### E. D-053 CRM assignment visibility unaffected
✅ **CONFIRMED.** HasAssignmentVisibility filters by assigned_to within a tenant; CRM tables carry
tenant_id; TenantScope wraps above. CRM internality + assignment scoping intact.

### F. Tables that ALREADY contain tenant_id (38 — the tenant-scoped parents)
core_tenants, core_users, core_audit_logs, content_engagement_events, content_pages, content_articles,
content_media, crm_accounts, crm_contacts, crm_leads, crm_opportunities, crm_activities,
client_projects, client_tickets, partner_profiles, partner_referrals, partner_agreements,
knowledge_categories, knowledge_articles, knowledge_resources, research_categories, research_authors,
research_publications, training_instructors, training_courses, training_lessons, training_enrollments,
training_certificate_sequences, training_certificates, community_profiles, marketplace_listings,
marketplace_applications, marketplace_listing_reports, startup_profiles, startup_programs,
program_cohorts, program_events. **(Owned parents are covered.)**

### G. Tables that still require tenant_id propagation / a tenancy decision
| Category | Tables | Disposition |
|---|---|---|
| **Children (inherit via parent — NO own tenant_id needed)** | client_project_milestones, client_deliverables, client_ticket_replies, training_course_sections, training_lesson_progress, training_assessments, training_assessment_questions, training_assessment_submissions, research_publication_authors, community CTI extensions (6) + community_profile_skills + community_endorsements, marketplace_listing_reviews, startup_team_members, startup_team_invitations, startup_ownership_transfers, startup_milestones, startup_mentors, startup_program_enrollments, program_coordinators, program_event_judges, program_event_scores | reached via tenant-scoped parent (W2-1); NO change — confirm no top-level cross-tenant query path |
| **Reference/shared (DECISION: global vs per-tenant)** | partner_tiers, training_course_categories, marketplace_categories, community_skills | **D-078 decision** — global catalogue OR per-franchise; add tenant_id only if per-tenant |
| **Analytics aggregation (need a tenant dimension)** | analytics_snapshots, analytics_revenue_daily, (warehouse tables) | add tenant_id dimension for franchise analytics (G/§10) |
| **User-scoped (inherit via user.tenant_id)** | notifications, role_escalation_approvals, consent/retention | inherit via user; optional denormalised tenant_id for query speed |
| **System/global (MUST NOT be tenant-scoped)** | permission tables (RBAC), i18n_translations, sys_queue/cache/sessions, personal_access_tokens, password_reset_tokens | intentionally tenant-agnostic — leave alone |

---

## RISK ANALYSIS (classified)

| Class | Risk | Severity | Control |
|---|---|---|---|
| Cross-tenant leakage | A scoped query missing the tenant filter → tenant B sees tenant A | **CRITICAL** | TenantScope on ALL scoped parents; children via parent; exhaustive isolation tests (release gate) |
| Cross-tenant leakage | Super-tenant bypass over-broad / unaudited | HIGH | permission-gated + HIGH audit; minimal bypass surface |
| Migration | Backfill error / NULL tenant_id rows leaking across tenants | HIGH | default-tenant backfill verified; NOT NULL after backfill on scoped tables |
| Migration | Reference-data ambiguity (global vs per-tenant) | MEDIUM | D-078 decision before enable |
| Performance | tenant_id predicate without index → slow scans | MEDIUM | tenant_id + composite indexes; query-plan review |
| Governance | Franchise Admin over-reach beyond their tenant | MEDIUM | tenant-scoped role; HQ-only provisioning; audit |
| Franchise admin | Tenant provisioning/suspension errors | MEDIUM | audited tenant lifecycle; HQ control |
| Reporting/analytics | Cross-tenant data in a tenant's dashboard | HIGH | analytics tenant dimension; per-tenant scoping; HQ-only roll-up |
| Data residency | Cross-border tenant data violates residency (B-2) | HIGH | region-pinned tenants / regional VPS (multi-country) |
| DR | Tenant restore affects other tenants | MEDIUM | tenant-scoped backup/restore; blast-radius containment |

---

## FINDINGS & DECISION POINTS

| ID | Severity | Finding | Disposition |
|---|---|---|---|
| FT-1 | **CRITICAL** | Cross-tenant isolation must be total; exhaustive per-module tests are the release gate | gate before enable |
| FT-2 | HIGH | Default-tenant + backfill strategy for existing single-tenant data | **D-077** |
| FT-3 | HIGH | Reference-data tenancy (global vs per-tenant) | **D-078** |
| FT-4 | HIGH | Analytics aggregation tables need a tenant dimension | schema add (G/§10) |
| FT-5 | HIGH | Data residency for multi-country (B-2) → regional hosting/VPS | infra (D-037 P3) |
| FT-6 | MEDIUM | Super-tenant bypass hierarchy (super-admin > tenant; ICS_INTERNAL within tenant) | **D-076** |
| FT-7 | MEDIUM | core_tenants extension: parent_tenant_id (regional), country/region, residency | **D-079** |
| FT-8 | MEDIUM | tenant_id indexing/composite indexes on hot tables | impl |
| FT-9 | LOW | Per-tenant DR export/restore runbook | ops |

### Missing schema / governance
- `BelongsToTenant` trait + `TenantScope` class (the activation code — not yet built).
- `core_tenants` may need: `parent_tenant_id` (regional hierarchy), `country_code`/`region`,
  `data_residency` (D-079).
- Analytics aggregation tables need a `tenant_id` dimension.
- Reference tables (partner_tiers/training_course_categories/marketplace_categories/community_skills)
  need the global-vs-per-tenant decision (D-078).
- A **Franchise Admin** role + tenant-administration permissions (RBAC addition).

### Proposed decisions (NOT decided now)
- **D-076** — TenantScope activation model (global scope via BelongsToTenant; composes above
  AccountScope; config-gated; bypass hierarchy super-admin > tenant, ICS_INTERNAL within tenant).
- **D-077** — Default-tenant + backfill migration (config-only, D-037; existing rows → root tenant).
- **D-078** — Reference-data tenancy policy (which taxonomies global vs per-tenant).
- **D-079** — Tenant administration + core_tenants extension (Franchise Admin role; parent_tenant_id
  regional hierarchy; country/region/residency).
- (optional) `AuditCategory::TENANT_MANAGEMENT` for tenant lifecycle events.

---

## REQUIRED OUTCOME

### **CONDITIONAL GO** for TenantScope activation.

- **Not NO GO:** the architecture is sound and ADDITIVE — the reservation (D-004/D-019/D-037/D-050)
  was load-bearing and honoured; `core_tenants` + `tenant_id` parents + the parent-isolation pattern
  make this an additive global scope, config-only per D-037, with all six access mechanisms and
  AccountScope/D-050/D-053 intact (Tests A–E all ✅).
- **Not full GO:** activation must satisfy conditions first: (1) **exhaustive cross-tenant isolation
  tests** (FT-1, release gate); (2) **default-tenant + backfill** (D-077); (3) **reference-data
  tenancy decision** (D-078); (4) **super-tenant bypass hierarchy** (D-076); (5) **analytics tenant
  dimension** (FT-4); (6) for multi-country, **data-residency/VPS** (FT-5/D-037 P3); (7) tenant_id
  **indexing** (FT-8).

**Recommended sequencing:** ratify D-076..D-079 → default-tenant backfill (single tenant, scope a
no-op) → add TenantScope + isolation tests GREEN → enable for a pilot second tenant → multi-country
later with residency/VPS.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Infrastructure | | | | |

**Status:** Awaiting Approval. **Do NOT implement TenantScope/Franchise until approved and
D-076..D-079 + the seven conditions are decided. Stop after the architecture review.**
