# ECOSYSTEM ROADMAP REVIEW
# ICS Enterprise Ecosystem Platform — Strategic Module Integration Validation

Version: 1.0
Date: 2026-06-03
Status: Architecture review — no code, no schema. Validates roadmap modules against approved architecture.
Author: Lead Architect
Governing decisions: D-004, D-019, D-031, D-037, D-038, D-050, D-053, D-055, D-057, D-060;
all access-control principles (six module mechanisms)
Scope: Startup Hub, Incubator Program, Accelerator Program, Investment Network,
Membership System, Franchise Operations

---

## PURPOSE & METHOD

D-019 reserves these six modules and states the architecture must not block them; Franchise
explicitly requires tenant-aware schema from day one (D-004/D-037). This review validates each
against the approved architecture and the **six existing access mechanisms**, answering the
mandatory questions: does it need a NEW access mechanism? can it reuse? any conflicts, schema
collisions, leakage, or governance risks?

**The six existing access mechanisms (families):**
| # | Mechanism | Family | Owner of |
|---|---|---|---|
| 1 | AccountScope | org isolation | org-owned rows (account_id) |
| 2 | ContentAccessService | content tiering | tiered content (role/tier) |
| 3 | HasAssignmentVisibility | assignment | internal CRM (assigned_to) |
| 4 | TrainingAccessService | membership / participation | enrollment-gated content |
| 5 | Community visibility | visibility + owner | identity profiles |
| 6 | Marketplace listing-status | workflow/status + owner/applicant | public listings + private applications |
| (reserved) | **TenantScope** | tenant isolation | per-tenant rows (tenant_id) — Phase 3, D-037 |

**Headline conclusion:** NONE of the six roadmap modules requires a brand-new access mechanism
*family*. Four reuse the membership/participation family; one reuses content-tiering + Billing;
**Franchise activates the already-reserved TenantScope** (planned since D-004/D-019, not a new
invention). Details below.

---

## MODULE A — STARTUP HUB  (Wave 5.1; schema already in blueprint)

| # | Dimension | Analysis |
|---|---|---|
| 1 | Purpose | Capacity development for startups: profiles, teams, milestones, mentorship, programs |
| 2 | Entities | startup_profiles (founder_id owner), startup_team_members, startup_milestones, startup_mentors, startup_programs, startup_program_enrollments (all in blueprint) |
| 3 | Interactions | Community (founder/startup profiles link → startup_profiles, public-only W4b-1); Training (startups consume courses); Marketplace (accelerator opportunities); Investment Network (discovery); AI (Startup Readiness, D-029 #8, ai_assessments) |
| 4 | Ownership | **Founder-owned** (startup_profiles.founder_id) + **team membership** (startup_team_members); mentors assigned (startup_mentors) |
| 5 | Access-control | **OWNER + TEAM-MEMBERSHIP** — same FAMILY as TrainingAccessService (participation via a join table). A thin `StartupAccessService` (isMember/isFounder) — NOT a new mechanism family. NOT AccountScope (founder-owned, not account-owned), NOT ContentAccessible |
| 6 | Audit | New category `STARTUP_MANAGEMENT`: program enrollment, graduation, mentor assignment, status changes. (proposed in Wave 5 plan) |
| 7 | Analytics | own aggregator (W4-9): startups by stage/program, milestone completion, graduation rate, mentor load |
| 8 | AI-readiness | Startup Readiness Assessment (ai_assessments, D-029 #8); mentor matching seam (Community skills) — deferred |
| 9 | TenantScope | tenant_id present on startup_profiles/programs; additive; nests under TenantScope (Phase 3) |
| 10 | Security/compliance | mentor notes + internal milestones are private (owner/team/staff); public startup page is curated |

**New mechanism?** NO — membership/participation family (reuse the pattern; thin service).
**Conflicts/collisions:** none — schema exists; Community already links to it (one-way, W4b-1).
**Leakage:** mentor notes / unfinished milestones must not surface on the public/community page.
**Governance:** moderate (program graduation, mentor assignment auditing).

## MODULE B — INCUBATOR PROGRAM  (Wave 5.2; extends Startup Hub, D-019)

| # | Dimension | Analysis |
|---|---|---|
| 1 | Purpose | Early-stage structured program (cohorts, curriculum, milestones, mentorship) for idea/MVP startups |
| 2 | Entities | REUSES startup_programs (type='incubator') + startup_program_enrollments; may add program_curriculum / program_sessions (extension tables) |
| 3 | Interactions | Startup Hub (base), Training (curriculum delivery), Community (mentors), Billing (program fees, D-031) |
| 4 | Ownership | Program is ICS-managed; a startup PARTICIPATES via enrollment (startup_program_enrollments) |
| 5 | Access-control | **PROGRAM PARTICIPATION** = membership family (enrolled startup sees program content). Reuse StartupAccessService/participation. NO new mechanism |
| 6 | Audit | STARTUP_MANAGEMENT (enrollment, graduation, withdrawal) |
| 7 | Analytics | cohort progress, completion/graduation, drop-off |
| 8 | AI-readiness | readiness scoring to gate admission; curriculum recommendation — deferred |
| 9 | TenantScope | inherits Startup Hub tenant_id |
| 10 | Security/compliance | cohort data scoped to participants + staff; fee handling via Billing |

**New mechanism?** NO — it is a *type* of Startup Hub program (the enum already exists).
**Conflicts/collisions:** none — reuses startup_programs.type='incubator'. Avoid a parallel
"incubator_*" table set (would duplicate; D-038 spirit) — extend, don't fork.

## MODULE C — ACCELERATOR PROGRAM  (Wave 5.3; extends Startup Hub, D-019)

| # | Dimension | Analysis |
|---|---|---|
| 1 | Purpose | Growth-stage cohort program: intensive mentorship, demo day, investor exposure |
| 2 | Entities | REUSES startup_programs (type='accelerator') + enrollments; may add demo_day / investor_showcase links to Investment Network |
| 3 | Interactions | Startup Hub, Investment Network (demo day → investor exposure), Marketplace (accelerator listings, D-011 type), Billing |
| 4 | Ownership | ICS-managed program; startup participates via enrollment |
| 5 | Access-control | PROGRAM PARTICIPATION = membership family. NO new mechanism |
| 6 | Audit | STARTUP_MANAGEMENT (cohort, graduation, investor-showcase opt-in) |
| 7 | Analytics | cohort outcomes, investor interest generated, graduation→funding |
| 8 | AI-readiness | investor-startup matching seam (shared with Investment Network) |
| 9 | TenantScope | inherits |
| 10 | Security/compliance | demo-day investor exposure is OPT-IN per startup; bridges into Investment Network's grant-gated data rooms (see Module D) |

**New mechanism?** NO — accelerator is a Startup Hub program type. **Conflict to flag:** the
accelerator↔investor bridge must route through the Investment Network's grant-gated, NDA
controls (Module D) — never expose startup financials by virtue of accelerator participation alone.

## MODULE D — INVESTMENT NETWORK  (Wave 5.4; new — investor↔startup connectivity, D-019)

| # | Dimension | Analysis |
|---|---|---|
| 1 | Purpose | Connect investors with startups: discovery, expressions of interest, NDA-gated **data rooms**, deal tracking |
| 2 | Entities | investor profiles (likely a NEW Community profile type 'investor' — D-035 extension), investment_opportunities, investment_interests, **investment_data_rooms** + **data_room_grants** (investor↔startup access), investment_deals |
| 3 | Interactions | Startup Hub (the startups), Accelerator (demo-day pipeline), Community (investor profiles), CRM (investor as lead, one-way D-053), Billing (success/deal fees) |
| 4 | Ownership | Startup owns its data room; investor owns its profile/interests; ICS curates/verifies |
| 5 | Access-control | **GRANT-BASED PARTICIPATION** = membership family on a grants join table (investor granted access to a startup's data room). Reuse the participation pattern. **NO new mechanism family** — but it carries a **SENSITIVITY/COMPLIANCE OVERLAY** (NDA acceptance gate + financial-PII redaction + heavy audit), which is a control layer, not a new access mechanism |
| 6 | Audit | New category `INVESTMENT_MANAGEMENT`; data-room grant/revoke + deal stage = HIGH (financial); NDA acceptance recorded |
| 7 | Analytics | pipeline (interest→data-room→deal), conversion, sector trends — aggregated, no per-investor PII in dashboards |
| 8 | AI-readiness | investor↔startup matching (ai.marketplace.match-style); deal-flow scoring — deferred |
| 9 | TenantScope | tenant_id on all; nests under TenantScope |
| 10 | Security/compliance | **HIGHEST after Franchise** — securities/financial data; KYC/accreditation (B-1), NDA before data-room, redaction, immutable audit. Legal/compliance review REQUIRED before build |

**New mechanism?** NO new *family* — grant-based participation (membership). **But** it needs a
data-room grant table + NDA/redaction/audit overlay. **Leakage (CRITICAL):** startup financials/
cap tables must NEVER leak to non-granted investors, to the public/Community profile (W4b-1
already restricts), or via accelerator participation. **Governance:** securities compliance is a
first-class risk → flag for legal review (proposed as a gating sub-review, like the certificate
governance review for Training).

## MODULE E — MEMBERSHIP SYSTEM  (extends Subscription/Billing, D-019/D-031)

| # | Dimension | Analysis |
|---|---|---|
| 1 | Purpose | Paid membership tiers granting elevated access/benefits across modules |
| 2 | Entities | membership_plans, memberships (user↔plan, active/expired) — extends billing_subscriptions (D-031); likely no new access table |
| 3 | Interactions | Billing (recurring payments, D-031), ContentAccessService (tier elevation), Knowledge/Research (gated content), Training (member pricing) |
| 4 | Ownership | user owns their membership |
| 5 | Access-control | **REUSE ContentAccessService** — an active membership elevates `userTier` (the hook ALREADY documented in ContentAccessService: "userTier may also be elevated by an active billing subscription — a hook, no schema change"). **NO new mechanism**; no new tier logic |
| 6 | Audit | reuse billing/subscription audit; membership grant/expiry events (category: reuse data_privacy/billing or a light membership entry) |
| 7 | Analytics | active members, churn, MRR (with Billing), tier distribution |
| 8 | AI-readiness | churn prediction, upsell — deferred |
| 9 | TenantScope | tenant_id; nests |
| 10 | Security/compliance | payment/refund/dunning governance (Billing); access elevation must drop immediately on expiry/refund |

**New mechanism?** NO — it is the realisation of the pre-existing ContentAccessService
billing-tier hook. **Conflict to avoid:** do NOT build a parallel membership access checker —
membership feeds `userTier`; the single ContentAccessService remains the gate (D-038/D-051).

## MODULE F — FRANCHISE OPERATIONS  (new — tenant-aware from day one, D-004/D-019)

| # | Dimension | Analysis |
|---|---|---|
| 1 | Purpose | Operate the platform as multiple isolated franchise instances (per-franchise data, branding, admin) |
| 2 | Entities | tenants (franchise registry), tenant_settings/branding; franchise admin roles. Crucially: it ACTIVATES the `tenant_id` already present on every table |
| 3 | Interactions | ALL modules (tenant nests above everything) |
| 4 | Ownership | a tenant (franchise) owns all its rows; tenant > account > user (D-050 #4) |
| 5 | Access-control | **ACTIVATE the RESERVED TenantScope** (D-037 Phase 3) — a global scope filtering by tenant_id, composing ABOVE AccountScope. This is the ONE module that introduces a (pre-planned, reserved) global mechanism. It is NOT a new invention — D-004/D-019/D-037 reserved it; tenant_id columns already exist platform-wide |
| 6 | Audit | tenant lifecycle (create/suspend); cross-tenant admin actions = HIGH; per-tenant audit partitioning |
| 7 | Analytics | per-tenant aggregates; the analytics layer (D-025) gains a tenant dimension |
| 8 | AI-readiness | per-tenant AI budgets/caps (config/ics.php already env-driven, D-037) |
| 9 | TenantScope | **this IS TenantScope** — Phase 3 activation |
| 10 | Security/compliance | **HIGHEST governance** — tenant isolation is load-bearing; a TenantScope gap = cross-tenant breach. Requires a dedicated tenancy security review + exhaustive isolation tests, and likely a VPS/infra step (D-037 Phase 2→3) |

**New mechanism?** TenantScope — RESERVED, not new. **Conflict:** none architecturally; it nests
above the six. **Risk:** highest — must be its own wave with a tenancy governance review (mirror
the certificate/trust reviews) before any code.

---

## MANDATORY VALIDATION SUMMARY

### Does any module introduce a NEW access mechanism?
| Module | New family? | Reuse | Notes |
|---|---|---|---|
| Startup Hub | NO | membership/participation (≈ TrainingAccessService) | thin StartupAccessService |
| Incubator | NO | program participation (Startup Hub) | startup_programs.type='incubator' |
| Accelerator | NO | program participation (Startup Hub) | type='accelerator'; investor bridge via Module D |
| Investment Network | NO family | grant-based participation + **compliance overlay** | data-room grants; NDA/redaction/audit |
| Membership | NO | ContentAccessService tier-elevation hook + Billing | no parallel checker |
| Franchise | TenantScope (RESERVED) | activate the pre-planned Phase-3 global scope | nests above the six (D-050 #4) |

### Comparison against the six (for the membership family modules)
The Startup/Incubator/Accelerator/Investment access needs are all "is X a participant in
relationship Y?" — the SAME shape as TrainingAccessService (is the user enrolled?). They are
NOT AccountScope (not org rows), NOT ContentAccessService (not tiered content), NOT
HasAssignmentVisibility (not internal CRM assignment), NOT Community visibility (not
public/authenticated identity), NOT Marketplace status (no review/publish workflow).
**Recommendation: reuse the membership/participation pattern as thin, per-module services**
(StartupAccessService, InvestmentAccessService) that share a documented contract — do NOT merge
them into one class (would couple unrelated modules; contradicts the D-057 "module-local" rule).

### Architectural conflicts
- **C-1:** Incubator/Accelerator must EXTEND startup_programs (enum types), not fork parallel
  table sets (D-038 no-duplication). 
- **C-2:** Accelerator→investor exposure must route through Investment Network grant gating —
  participation in an accelerator must NOT auto-expose startup financials.
- **C-3:** Membership must feed ContentAccessService `userTier`, not introduce a second gate.

### Schema collisions
- **S-1:** "investor" as a Community profile type — add as a new D-035 extension table
  (community_investor_profiles); no collision (clean extensibility).
- **S-2:** startup_profiles already exists; Community founder/startup extensions LINK to it
  (one-way) — confirmed no collision (W4b-1).
- **S-3:** Franchise `tenants` table is new; tenant_id columns already exist everywhere — no
  column collisions, only constraint/scope activation.

### Cross-module data leakage risks
- **L-1 (CRITICAL):** Investment Network financials/cap tables — grant-gated; never to public,
  Community, or via accelerator.
- **L-2:** Startup mentor notes + internal milestones — owner/team/staff only; not on public page.
- **L-3:** Franchise — cross-tenant leakage if TenantScope is incomplete (every query must be
  tenant-scoped); exhaustive isolation tests mandatory.
- **L-4:** Membership — access elevation must revoke instantly on expiry/refund (no stale tier).

### Governance risks
- **G-1 (HIGHEST):** Franchise tenant isolation (load-bearing; breach = cross-tenant exposure).
- **G-2 (HIGH):** Investment Network securities/financial compliance (KYC/accreditation, NDA,
  B-1) — requires legal review before build.
- **G-3 (MEDIUM):** Membership billing/refund/dunning + immediate access revocation.
- **G-4 (MEDIUM):** Startup program graduation/mentor-assignment integrity (audit).

---

## VERDICT

**The roadmap integrates cleanly with the approved architecture. No module violates D-037,
D-038, D-050, D-053, D-055, D-057, or D-060.** No new access-mechanism *family* is required:
four modules reuse membership/participation, one reuses the ContentAccessService billing hook,
and Franchise activates the long-reserved TenantScope. The two highest-risk modules (Franchise,
Investment Network) each warrant a dedicated gating governance review before implementation
(as Training got the certificate review). Proceed to the Access-Control Consolidation Review and
the Wave 5 Architecture Plan.
