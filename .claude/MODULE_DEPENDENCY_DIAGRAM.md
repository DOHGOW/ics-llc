# MODULE DEPENDENCY DIAGRAM
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: Awaiting Approval
Author: Chief Enterprise Architect

Decision References: D-027 (Event-Driven Architecture), D-003 (Phased Hosting)

---

## EXECUTIVE SUMMARY

This document maps all dependency relationships between platform modules.
Dependencies determine build order, define integration risk, and expose circular
dependency hazards. Understanding module dependencies is mandatory before
development begins.

A dependency exists when Module A requires Module B to function correctly.
All cross-module communication is mediated by Events (D-027). Direct database
cross-queries between modules are prohibited.

Dependency Types:
  HARD  — Module cannot function without the dependency
  SOFT  — Module has reduced functionality without the dependency
  EVENT — Dependency realized through Event dispatch only (loose coupling)
  DATA  — Module reads aggregated data from another module's analytics layer

Total Modules: 13 (Phase 1) + 7 reserved
Total Dependencies Mapped: 47

---

## DEPENDENCY LEVELS

Modules are organized into build levels. A module at Level N depends on
modules at Levels 0 through N-1.

```
LEVEL 0 — FOUNDATION (no dependencies)
  Core Platform

LEVEL 1 — DIRECT CORE CONSUMERS
  Corporate Website / CMS
  CRM

LEVEL 2 — MODULE LAYER
  Client Portal
  Training Institute
  Partner Portal
  Startup Hub
  Opportunity Marketplace

LEVEL 3 — CONTENT & INTELLIGENCE LAYER
  Knowledge Center
  Research Center
  Community Module
  Billing & Subscriptions

LEVEL 4 — CROSS-MODULE INTELLIGENCE
  AI Services
  Analytics Layer

LEVEL 5 — AGGREGATE REPORTING
  Data Warehouse
```

---

## LEVEL 0 — CORE PLATFORM

```
╔══════════════════════════════════════════════════════════════╗
║                    CORE PLATFORM                             ║
║                                                              ║
║  IAM / Auth         RBAC Engine       Tenant Manager         ║
║  Notification       Audit Logger      Job Queue              ║
║  File Storage       i18n Engine       Event Dispatcher       ║
║  Session/Cache      Push (PWA)        API Gateway            ║
╚══════════════════════════════════════════════════════════════╝

Dependencies: NONE
All other modules depend on Core Platform.
This is the single foundational dependency of the entire system.
```

**Build Requirement:** Core Platform must be fully operational and tested
before any domain module development begins.

**Tables:** core_users, core_tenants, core_audit_logs, core_consent_logs,
roles, permissions, personal_access_tokens, sys_jobs, sys_cache, sys_sessions,
sys_failed_jobs, notifications, i18n_translations, notify_preferences,
notify_push_subscriptions

---

## LEVEL 1 — DIRECT CORE CONSUMERS

### Corporate Website / CMS

```
Corporate Website / CMS
        │
        ▼ HARD
  Core Platform
  (auth, i18n, storage, notifications)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Authentication, media storage, i18n |

**No dependencies on other domain modules.**

**Build Requirement:** Core Platform only.
**Tables:** content_pages, content_articles, content_media, content_menus

---

### CRM

```
CRM (Internal)
        │
        ├── HARD → Core Platform
        └── EVENT → Community Module (receives ProfileCreated for consultant lead capture)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, RBAC, audit, notifications |
| Community Module | EVENT | Consultant ProfileCreated → CreateCRMLead (D-035) |

**Note:** CRM is CONSUMED by Client Portal and Billing — it does not depend on them.

**Build Requirement:** Core Platform only. Community Module integration is Event-based and can be added when Community Module is built.
**Tables:** crm_accounts, crm_contacts, crm_leads, crm_opportunities, crm_contracts, crm_activities, crm_proposals

---

## LEVEL 2 — MODULE LAYER

### Client Portal

```
Client Portal
        │
        ├── HARD → Core Platform
        ├── HARD → CRM (reads crm_accounts, crm_contracts for account scope)
        └── SOFT → Billing (invoice display)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, RBAC, file storage, notifications |
| CRM | HARD | Client Portal is scoped to CRM accounts; project records reference contracts |
| Billing | SOFT | Invoice display links to billing_invoices; portal works without billing |

**Build Requirement:** Core Platform + CRM must be complete.
**Tables:** client_projects, client_project_milestones, client_deliverables, client_tickets, client_ticket_replies

---

### Training Institute

```
Training Institute
        │
        ├── HARD → Core Platform
        ├── SOFT → Billing (course payments — required for paid courses)
        └── EVENT → Knowledge Center (Training Resources cross-link)
        └── EVENT → Community Module (updates trainer/student profiles)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, storage, notifications, i18n |
| Billing | SOFT | Required for paid course purchases; free courses work without billing |
| Knowledge Center | EVENT | Training Resources in Knowledge Center link to courses |
| Community Module | EVENT | CourseCompleted updates community profile stats |

**Build Requirement:** Core Platform required. Billing integration added in Phase 2.
**Tables:** training_courses, training_course_categories, training_lessons, training_course_sections, training_enrollments, training_lesson_progress, training_assessments, training_assessment_questions, training_assessment_submissions, training_certificates, training_instructors

---

### Partner Portal

```
Partner Portal
        │
        ├── HARD → Core Platform
        ├── HARD → CRM (referrals create CRM leads; partner linked to crm_accounts)
        └── EVENT → Community Module (partner profile creation on approval)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, RBAC, notifications |
| CRM | HARD | Referrals dispatch E-PART-003 which creates crm_leads |
| Community Module | EVENT | PartnerApproved → CreatePartnerCommunityProfile |

**Build Requirement:** Core Platform + CRM.
**Tables:** partner_profiles, partner_referrals, partner_agreements, partner_tiers

---

### Startup Hub

```
Startup Hub
        │
        ├── HARD → Core Platform
        └── EVENT → Community Module (startup/founder community profile on registration)
        └── EVENT → AI Services (StartupReadinessAssessed)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, RBAC, notifications |
| Community Module | EVENT | StartupRegistered → CreateStartupCommunityProfile |
| AI Services | EVENT | StartupReadinessAssessed dispatched to AI Services |

**Build Requirement:** Core Platform. AI Services integration is Event-based (added when AI module is built).
**Tables:** startup_profiles, startup_team_members, startup_milestones, startup_mentors, startup_programs, startup_program_enrollments

---

### Opportunity Marketplace

```
Opportunity Marketplace
        │
        ├── HARD → Core Platform
        ├── SOFT → Community Module (opportunity sharing on profiles)
        └── EVENT → AI Services (ListingApproved → TriggerAIOpportunityMatching)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, RBAC, notifications |
| Community Module | SOFT | Listing shared_by_profile_id FK; marketplace works without community |
| AI Services | EVENT | AI matching triggered on listing approval |

**Build Requirement:** Core Platform. Community Module and AI Services are additive.
**Tables:** marketplace_listings, marketplace_categories, marketplace_applications, marketplace_listing_reviews

---

## LEVEL 3 — CONTENT & INTELLIGENCE LAYER

### Knowledge Center

```
Knowledge Center
        │
        ├── HARD → Core Platform
        ├── SOFT → Training Institute (Training Resources cross-link)
        ├── SOFT → Billing (Phase 2: subscription tier elevation)
        └── EVENT → AI Services (knowledge search, content drafting)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, RBAC (tiered access), storage, i18n |
| Training Institute | SOFT | knowledge_articles.metadata links to courses; works without |
| Billing | SOFT | Phase 2 subscription grants Tier 2 access; base tiers work without billing |
| AI Services | EVENT | KnowledgeSearchService; ContentDraftingService |

**Build Requirement:** Core Platform. Training + Billing integrations are Phase 2 additions.
**Tables:** knowledge_articles, knowledge_categories, knowledge_tags, knowledge_article_tags, knowledge_bookmarks, knowledge_ratings, knowledge_views, knowledge_downloads, knowledge_related

---

### Research Center

```
Research Center
        │
        ├── HARD → Core Platform
        ├── SOFT → Community Module (researcher profiles link to research_authors)
        ├── SOFT → Billing (Phase 2: subscription tier elevation per D-034)
        └── EVENT → AI Services (research assistant)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, RBAC (tiered access), storage, i18n |
| Community Module | SOFT | research_authors may link to community_profiles; optional FK |
| Billing | SOFT | Phase 2 subscription grants Tier 2/3 access; base tiers work without |
| AI Services | EVENT | ResearchAssistantService queries publications |

**Build Requirement:** Core Platform.
**Tables:** research_publications, research_categories, research_authors, research_publication_authors, research_downloads, research_citations

---

### Community Module

```
Community Module
        │
        ├── HARD → Core Platform
        ├── EVENT → CRM (consultant ProfileCreated → CreateCRMLead)
        ├── SOFT → Training Institute (trainer profiles link to training_instructors)
        ├── SOFT → Partner Portal (partner profiles link to partner_profiles)
        ├── SOFT → Research Center (researcher profiles link to research_authors)
        └── SOFT → Startup Hub (startup/founder profiles link to startup_profiles)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, RBAC, notifications |
| CRM | EVENT | Creates CRM lead on consultant profile creation |
| Training Institute | SOFT | Optional FK community_trainer_profiles.instructor_id |
| Partner Portal | SOFT | Optional FK community_partner_profiles.partner_id |
| Research Center | SOFT | Optional FK community_researcher_profiles.author_id |
| Startup Hub | SOFT | Optional FK community_founder_profiles.startup_id |

**Build Requirement:** Core Platform. Module integrations are optional FKs — Community can be built before the linked modules exist.
**Tables:** community_profiles, community_founder_profiles, community_startup_profiles, community_consultant_profiles, community_trainer_profiles, community_partner_profiles, community_researcher_profiles, community_skills, community_profile_skills, community_endorsements

---

### Billing & Subscriptions

```
Billing & Subscriptions
        │
        ├── HARD → Core Platform
        ├── HARD → CRM (invoices linked to crm_accounts; consulting deposits reference contracts)
        ├── EVENT → Training Institute (CourseEnrolled → CreateCourseInvoice)
        ├── EVENT → Knowledge Center (subscription tier elevation)
        ├── EVENT → Research Center (subscription tier elevation)
        └── EVENT → Community Module (future: event registrations)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, notifications, audit |
| CRM | HARD | Invoices reference crm_accounts; consulting deposit workflow |
| Training Institute | EVENT | CourseEnrolled triggers invoice creation for paid courses |
| Knowledge Center | EVENT | SubscriptionActivated elevates user tier |
| Research Center | EVENT | SubscriptionActivated elevates user tier |

**Build Requirement:** Core Platform + CRM.
**Tables:** billing_plans, billing_subscriptions, billing_invoices, billing_invoice_items, billing_invoice_sequences, billing_payments, billing_webhooks

---

## LEVEL 4 — CROSS-MODULE INTELLIGENCE

### AI Services

```
AI Services
        │
        ├── HARD → Core Platform
        ├── SOFT → CRM (Lead Qualification, Proposal Generation, Digital Maturity)
        ├── SOFT → Training Institute (Training Recommendations)
        ├── SOFT → Knowledge Center (Knowledge Search, Content Drafting)
        ├── SOFT → Research Center (Research Assistant)
        ├── SOFT → Marketplace (Opportunity Matching)
        └── SOFT → Startup Hub (Startup Readiness Assessment)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Auth, RBAC, job queue, rate limiting |
| CRM | SOFT | Lead qualification reads crm_leads; proposal reads crm_opportunities |
| Training Institute | SOFT | Recommendations read training_courses |
| Knowledge Center | SOFT | Search reads knowledge_articles |
| Research Center | SOFT | Assistant reads research_publications |
| Marketplace | SOFT | Matching reads marketplace_listings |
| Startup Hub | SOFT | Assessment reads startup_profiles |

**Architecture Note:** AI Services never writes to module source tables directly.
Results are dispatched as Events (E-AI-*) and written by module-specific listeners.

**Build Requirement:** Core Platform. Each AI use case can be activated independently as the target module becomes available.
**Tables:** ai_requests, ai_assessments, ai_cache

---

### Analytics Layer (Tier 1)

```
Analytics Layer
        │
        ├── HARD → Core Platform
        ├── DATA → CRM
        ├── DATA → Training Institute
        ├── DATA → Marketplace
        ├── DATA → Partner Portal
        ├── DATA → Startup Hub
        ├── DATA → Knowledge Center
        ├── DATA → Research Center
        ├── DATA → Billing
        └── DATA → Community Module
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | Scheduled commands, job queue |
| All domain modules | DATA | Aggregation cron reads source tables; writes to analytics_ tables |

**Note:** Analytics Layer reads source tables via scheduled jobs, not via Events.
This is the ONLY approved exception to the no-cross-module-query rule because
analytics aggregation runs in isolated cron jobs, not in the HTTP request cycle.

**Build Requirement:** Core Platform. Analytics layer grows as modules are built.
**Tables:** analytics_snapshots, analytics_crm_pipeline, analytics_training_stats, analytics_marketplace_stats, analytics_partner_perf, analytics_startup_progress, analytics_user_activity, analytics_revenue_daily, analytics_mrr_snapshots, analytics_ar_aging

---

## LEVEL 5 — AGGREGATE REPORTING

### Data Warehouse (Tier 2)

```
Data Warehouse
        │
        ├── HARD → Core Platform
        └── DATA → Analytics Layer (reads analytics_ tables)
        └── DATA → All source modules (nightly ETL via dedicated commands)
```

| Dependency | Type | Reason |
|---|---|---|
| Core Platform | HARD | ETL commands run via Laravel Scheduler |
| Analytics Layer | DATA | Warehouse ETL supplements with analytics_ aggregations |
| All source modules | DATA | Direct ETL reads (isolated cron jobs only) |

**Build Requirement:** Core Platform + at least one source module per fact table.
Data Warehouse is a Phase 2 deliverable — database tables created in Phase 1 migrations, ETL commands built in Phase 2.
**Tables:** All dw_fact_*, dw_dim_*, dw_etl_runs

---

## FULL DEPENDENCY MAP (ASCII)

```
                    ┌──────────────────┐
                    │  CORE PLATFORM   │ ← Level 0
                    │  (No deps)       │
                    └────────┬─────────┘
                             │ ALL modules depend on Core
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────▼────┐         ┌────▼────┐         ┌────▼────┐
    │  Corp.  │         │   CRM   │         │ (future)│  ← Level 1
    │ Website │         │         │         │         │
    └─────────┘         └────┬────┘
                             │
         ┌───────────────────┼──────────────────────────┐
         │                   │                          │
    ┌────▼────┐         ┌────▼────┐               ┌────▼────┐
    │ Client  │         │ Partner │               │ Startup │  ← Level 2
    │ Portal  │         │ Portal  │               │  Hub    │
    └────┬────┘         └────┬────┘               └────┬────┘
         │                   │                          │
         │         ┌─────────▼─────────┐               │
         │         │  Training Inst.   │               │
         │         └─────────┬─────────┘               │
         │                   │                          │
         │         ┌─────────▼─────────┐               │
         │         │ Opp. Marketplace  │               │
         └─────────┴─────────┬─────────┴───────────────┘
                             │
         ┌───────────────────┼──────────────────────────┐
         │                   │                          │
    ┌────▼────┐         ┌────▼────┐               ┌────▼────┐
    │Knowledge│         │Research │               │Community│  ← Level 3
    │ Center  │         │ Center  │               │ Module  │
    └────┬────┘         └────┬────┘               └────┬────┘
         │                   │                          │
         └───────────────────┼──────────────────────────┘
                             │
                    ┌────────▼─────────┐
                    │    Billing &     │
                    │  Subscriptions   │
                    └────────┬─────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────▼────┐         ┌────▼────┐         ┌────▼────┐
    │   AI    │         │Analytics│         │  Data   │  ← Level 4/5
    │Services │         │ Layer   │         │Warehouse│
    └─────────┘         └─────────┘         └─────────┘
```

---

## CIRCULAR DEPENDENCY ANALYSIS

No circular dependencies exist in the current design. The following potential
circular paths were evaluated and resolved:

| Potential Circular Path | Resolution |
|---|---|
| CRM → Community → CRM | Community dispatches Event; CRM Listener handles it — no direct reference |
| Billing → Training → Billing | Training dispatches CourseEnrolled event; Billing Listener handles invoice creation |
| AI → CRM → AI | AI writes to ai_assessments only; CRM Listener reads and creates crm leads/opportunities |
| Analytics → all modules → Analytics | Analytics reads via cron ETL — no Event chain back to Analytics |

---

## BUILD ORDER (Phase 1)

Based on dependency levels and business priority (D-016 — Government first):

| Sprint | Module | Dependencies Complete |
|---|---|---|
| 1 | Core Platform — Auth, RBAC, Users, Audit, i18n | None |
| 1 | Core Platform — Notifications, Storage, Queue | Auth complete |
| 2 | Corporate Website / CMS | Core Platform |
| 2 | CRM | Core Platform |
| 3 | Client Portal | Core Platform + CRM |
| 3 | Partner Portal | Core Platform + CRM |
| 4 | Startup Hub | Core Platform |
| 4 | Opportunity Marketplace | Core Platform |
| 5 | Training Institute (free courses) | Core Platform |
| 5 | Knowledge Center | Core Platform |
| 6 | Research Center | Core Platform |
| 6 | Community Module | Core Platform |
| 7 | AI Services (use cases activated module-by-module) | Core Platform + target modules |
| 7 | Analytics Layer (Tier 1 — initial modules) | Core Platform + Modules 1–6 |
| 8 | PWA + Service Worker | All Phase 1 modules |

**Phase 2 Additions:**
Training Institute (paid courses) + Billing, Data Warehouse, French i18n, Redis queue

---

## SCALABILITY RISKS

| Risk | Module | Details |
|---|---|---|
| Analytics reads growing source tables | Analytics Layer | Cron query time increases with data volume. Mitigation: indexed queries + read-replica Phase 2 |
| AI fanout on ListingApproved | AI + Marketplace | Matching job for all users; must be queued and batched | 
| Community directory query complexity | Community | Multi-filter JOIN across profile types; requires compound indexes |
| DW ETL duration increases | Data Warehouse | Nightly window may exceed available time at scale. Mitigation: delta loads (only new/changed records) |

---

## FUTURE MODULE DEPENDENCIES (D-019)

| Future Module | Will Depend On |
|---|---|
| LMS | Training Institute, Core Platform |
| Vendor Marketplace | Marketplace, Core Platform, Billing |
| Membership System | Billing, Core Platform |
| Incubator Program | Startup Hub, Core Platform |
| Accelerator Program | Startup Hub, Core Platform |
| Investment Network | Community, Startup Hub, Core Platform (legal review required) |
| Franchise Operations | Core Platform (tenant-aware activation), all modules |

---

## APPROVAL SECTION

| Role | Name | Signature | Date |
|---|---|---|---|
| Platform Owner | | | |
| Lead Architect | | | |
| Technical Lead | | | |

**Status:** Awaiting Review and Approval
**Gate:** Build order must be approved before sprint planning begins.
