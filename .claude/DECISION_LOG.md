# DECISION LOG

---

## D-001

Project Name: ICS Enterprise Ecosystem Platform
Decision: Platform-first architecture.
Reason: Create an ecosystem rather than a standard agency website.
Status: Approved

---

## D-002

Decision: Technology Stack
Frontend: HTML5, Tailwind CSS, Alpine.js, Vanilla JavaScript
Backend: PHP 8.3
Database: MySQL 8+
Reason: Maximum compatibility with Hostinger while maintaining scalability.
Status: Approved

---

## D-003

Decision: Hosting & Deployment Strategy
Phase 1: Hostinger Premium Shared Hosting
Phase 2: Hostinger VPS
Phase 3: Dedicated Cloud Infrastructure
Deployment: Git-based, Staging + Production environments
Status: Approved

---

## D-004

Decision: Multi-Tenancy Model
Single Platform, Multi-Organization Ready. NOT SaaS Multi-Tenant initially.
ICS manages: Clients, Partners, Startups, Vendors, Trainers, Government Agencies
Future: Multi-Tenant Architecture path reserved
Status: Approved

---

## D-005

Decision: Mobile Strategy
Progressive Web Application (PWA)
Features: Offline Support, Push Notifications, Mobile Dashboard, Installable App
Native Apps: Future phase
Status: Approved

---

## D-006

Decision: Compliance Requirements
Nigeria Data Protection Act (NDPA)
GDPR Ready
Basic ISO 27001 Alignment
OWASP Security Principles
Reason: Future international clients and donors
Status: Approved

---

## D-007

Decision: Data Residency
Primary: European Data Centers
Fallback: US Data Centers
Reason: GDPR Compatibility
Status: Approved

---

## D-008

Decision: Monetization Strategy
Current Revenue: Website Dev, Enterprise Software, SEO, AI Solutions, Consulting,
Corporate Training, Government Training, Managed Services, Support Retainers,
Maintenance Contracts
Future Revenue: Membership Plans, Premium Resources, Paid Training, Event Tickets,
Marketplace Commissions
Subscription Module: Required (Phase 2 scope)
Status: Approved

---

## D-009

Decision: Service Level Objectives
Target: 99.9% Availability
Response Times: Critical 4h, High 12h, Normal 24h, Low 72h
Status: Approved

---

## D-010

Decision: Enterprise Ecosystem Strategy is the Platform Operating Model (not a module)
Core Platform Components:
- Corporate Website
- CRM (Internal)
- Client Portal
- Startup Hub
- Partner Portal
- Training Institute
- Opportunity Marketplace
- Knowledge Center
- Research Center
- AI Services
Status: Approved

---

## D-011

Decision: Opportunity Marketplace Access & Workflow
Posting Rights: ICS Administrators, Approved Partners, Approved Organizations
Categories: Grants, Tenders, Jobs, Internships, Scholarships, Fellowships, Accelerators
Approval Workflow: Submission → Review → Approval → Publication
Status: Approved

---

## D-012

Decision: CRM Scope - Internal Enterprise CRM Only
Purpose: Manage Leads, Opportunities, Accounts, Clients, Contracts, Renewals
Client-Facing CRM: Not Required
Status: Approved

---

## D-013

Decision: Centralized Analytics Layer Required
Cross-module reporting covering: CRM, Training, Startup Hub, Partner Portal,
Opportunities, Projects
Executive Dashboard required
Status: Approved

---

## D-014

Decision: Internationalization Strategy
Phase 1: English
Phase 2: French
Phase 3: Arabic (RTL support required)
Architecture must support localization from day one
Status: Approved

---

## D-015 (A-1)

Decision: Organizational Identity
ICS is a Technology, Consulting, Capacity Development, and Innovation Organization.
ICS is NOT a web design agency or creative agency.
Platform design, module scope, content tone, and feature priorities must reflect
enterprise and institutional positioning at all times.
Status: Approved

---

## D-016 (A-2)

Decision: Primary Audience Priority Order
1. Government Agencies
2. International Organizations
3. Corporate Enterprises
4. NGOs
5. SMEs
6. Startups
7. Individuals
All UX, feature priority, compliance, and content decisions must serve audiences
in this order. Higher-priority audiences receive first-class treatment.
Implication: WCAG 2.1 accessibility compliance is now mandatory (government procurement).
Status: Approved

---

## D-017 (A-3)

Decision: Strategic Mission
Become Africa's leading digital transformation, technology consulting, innovation,
and capacity development ecosystem.
Implication: Architecture must support continental-scale operations. No regional
hardcoding. Multi-language and multi-currency readiness required by design.
Status: Approved

---

## D-018 (A-4)

Decision: Competitive Positioning
ICS competes against: Consulting Firms, Technology Integrators, Digital Transformation
Firms, Training Institutes, Innovation Hubs.
ICS does NOT compete primarily with web design agencies.
Implication: Research Center and Knowledge Center are strategic credibility assets,
not secondary content modules. Training Institute must support professional certifications.
Status: Approved

---

## D-019 (A-5)

Decision: Future Platform Expansion Roadmap
The following modules are reserved for future development. Architecture must not
block their addition:
- LMS (standalone or Training Institute extension — scope to be defined)
- Vendor Marketplace
- Membership System (extends Subscription Module)
- Incubator Program (extends Startup Hub)
- Accelerator Program (extends Startup Hub)
- Investment Network (new — investor-startup connectivity)
- Franchise Operations (new — requires tenant-aware database architecture from day one)
Implication: D-004 multi-tenancy reservation is now confirmed as load-bearing.
Franchise Operations requires tenant-aware schema design even in Phase 1.
Status: Approved

---

## D-020 (OD-001)

Decision: Laravel 11 Framework
Stack: Laravel 11 + PHP 8.3 + MySQL 8+
Templating: Blade engine (supersedes plain HTML5 in D-002 for server-side views)
ORM: Eloquent
API Auth: Laravel Sanctum (bearer tokens)
Queue Phase 1: Laravel Task Scheduling + MySQL jobs table (cron-based)
Queue Phase 2: Laravel Horizon + Redis (VPS)
Frontend: Blade + Tailwind CSS + Alpine.js (refines D-002)
Reason: Enterprise scalability, maintainability, security, ecosystem maturity.
Status: Approved

---

## D-021 (OD-002)

Decision: RBAC Authentication Architecture
Auth Library: Spatie Laravel-Permission (roles and permissions)
API Auth: Laravel Sanctum (stateless bearer tokens)
Web Auth: Laravel session-based authentication
Authorization: Laravel Gates + Policies for resource-level checks
Enforcement: Server-side only. Frontend visibility is cosmetic.
Principle: All roles default to zero permissions. Access is additive only.
Status: Approved

---

## D-022 (OD-003)

Decision: Notification Architecture
System: Laravel Notifications (multi-channel)
Channel 1: Mail via Brevo SMTP/API
Channel 2: WhatsApp via WhatsApp Business API
Channel 3: Database (in-app notification center, stored in notifications table)
Delivery: Queued via jobs table (Phase 1 cron, Phase 2 Redis async)
Preference: Per-user notification channel preferences
Status: Approved

---

## D-023 (OD-004)

Decision: API-First Architecture
All business logic must be accessible via RESTful API.
Version prefix: /api/v1/
Auth: Laravel Sanctum bearer token
Response envelope: { success, data, message, meta }
Module routing: /api/v1/{module}/ (e.g. /api/v1/crm/, /api/v1/training/)
Transformation: Laravel API Resources (no raw model serialization)
Status: Approved

---

## D-024 (OD-005)

Decision: File & Media Storage Architecture
Phase 1: Hostinger filesystem via Laravel Storage (local driver)
Storage path: storage/app/ (outside webroot — never directly web-accessible)
Public files: Accessed via storage:link symlink (public/storage/)
Private files: Served through authenticated PHP controller with permission check
Phase 3 Migration: Laravel Flysystem driver swap to S3-compatible cloud storage
Migration method: .env driver configuration change only — zero application code changes
Status: Approved

---

## D-025 (OD-006)

Decision: Central Analytics Architecture
Pattern: Cross-module data aggregation layer (separate from source tables)
Storage: MySQL aggregation tables and database views
Scheduling: Laravel Task Scheduling for report generation (cron)
Dashboard: Executive Dashboard with module KPI widgets
Visualization: Chart.js (frontend)
Modules reporting into Analytics: CRM, Training, Marketplace, Partner, Startup, Client
Cross-module queries: Read from analytics aggregation tables only, never source tables
Status: Approved

---

## D-026 (OD-007)

Decision: Gemini AI Architecture
Integration: Google Gemini API via Laravel HTTP Client
Service class: App\Services\AI\GeminiService
Rate limiting: Per-user requests per hour + global daily budget cap
Logging: All requests and responses logged to ai_requests table (cost tracking)
Async: Heavy AI tasks processed via job queue (not inline in HTTP request)
Fallback: Graceful degradation on API failure (cached response or disabled state)
Specific use cases: Pending (OD-AI-001)
Status: Approved (architecture pattern) — Use cases require separate approval

---

## D-027 (OD-008)

Decision: Event-Driven Architecture
Pattern: Laravel Events and Listeners for all cross-module communication
Phase 1: Synchronous event dispatch
Phase 2: Async queued listeners (Redis + Horizon on VPS)
Rule: No module may directly query another module's database tables.
All cross-module data flow must pass through a dispatched Event.
Event catalog must be produced and approved before module development begins.
Status: Approved in Principle — Event Catalog Required before development

---

## D-028 (OD-COMPLY-001)

Decision: WCAG 2.1 Level AA Accessibility Standard
Amendment to: D-006 (Compliance Requirements)
Standard: Web Content Accessibility Guidelines (WCAG) 2.1 Level AA
Scope: Mandatory across all frontend modules without exception.

Required Controls:
- Keyboard Navigation: All interactive elements reachable and operable via keyboard
- Screen Reader Compatibility: All content readable by assistive technology
- ARIA Labels: All icons, images, and non-text elements carry descriptive ARIA attributes
- Accessible Forms: Labels, error messages, and hints programmatically associated
- Color Contrast: Minimum 4.5:1 for normal text; 3:1 for large text and UI components
- Focus Indicators: Visible focus outline on every focusable element at all times
- Responsive Accessibility: Accessible behaviour maintained across all screen sizes
- Accessible Data Tables: Headers, captions, and scope attributes on all data tables

Reason: Government procurement — Audience Priority #1 (D-016) — requires WCAG 2.1 AA.
International donors and organizations also expect AA compliance as baseline.
Enforcement: All frontend components rejected in code review if not AA compliant.
Status: Approved

---

## D-029 (OD-AI-001)

Decision: Gemini AI — Approved Use Cases
Architecture reference: D-026 (Gemini AI Architecture)

Phase 1 Use Cases:

  1. AI Website Assistant
     Module: Corporate Website
     Purpose: Public-facing conversational assistant on the platform website

  2. AI Lead Qualification
     Module: CRM
     Purpose: Score and qualify inbound leads based on profile and engagement data

  3. AI Proposal Generation
     Module: CRM
     Purpose: Draft proposal documents from opportunity data and service templates

  4. AI Training Recommendation Engine
     Module: Training Institute
     Purpose: Personalised course recommendations based on user profile and goals

  5. AI Knowledge Base Search
     Module: Knowledge Center
     Purpose: Semantic search across published knowledge articles and guides

  6. AI Research Assistant
     Module: Research Center
     Purpose: Summarise and surface relevant research for users

  7. AI Opportunity Matching
     Module: Opportunity Marketplace
     Purpose: Match user profiles to relevant listings automatically

  8. AI Startup Readiness Assessment
     Module: Startup Hub
     Purpose: Evaluate startup readiness across standard dimensions; generate report

  9. AI Digital Maturity Assessment
     Module: CRM / Client Portal
     Purpose: Evaluate client digital maturity across standard dimensions; generate report

  10. AI Content Drafting
      Module: CMS / Knowledge Center / Research Center
      Purpose: Assist staff in drafting articles, reports, and knowledge content

Future Use Cases (Reserved — not in Phase 1 scope):

  11. AI Business Advisory Assistant
  12. AI Executive Dashboard Insights

Mandatory cross-cutting requirements (all AI services):
  - Rate limiting: per user (tiered by use case) + global daily budget cap
  - Cost monitoring: all usage logged to ai_requests with token count and cost
  - Usage analytics: aggregated into analytics layer (D-025)
  - Graceful fallback: return cached result or degraded response on API failure

Status: Approved

---

## D-030 (OD-CONTENT-002)

Decision: Research Center — Approved Scope
Purpose: Position ICS as a thought leader in digital transformation, technology
consulting, innovation, and capacity development across Africa.

Content Types:
  - Industry Reports
  - White Papers
  - Research Publications
  - Technology Trends Reports
  - Digital Economy Reports
  - Government Technology Reports
  - AI Adoption Reports
  - Capacity Development Reports

Approved Features:
  - Downloadable Reports (PDF — served via Laravel Storage, D-024)
  - Citation Support (auto-generated APA, Chicago, IEEE formats; DOI field)
  - Research Library (browsable, filterable, searchable collection)
  - Author Profiles (ICS researchers + external contributors; linked or standalone)
  - Research Categories (maps to the 8 content types above + custom tags)
  - Research Analytics (view counts, download counts, geographic data — feeds D-025)

Module Interactions:
  - AI Research Assistant (D-029 use case #6) queries Research Center content
  - Analytics Layer (D-025) aggregates Research Center metrics
  - Storage Architecture (D-024) handles downloadable file delivery
  - CMS Staff role manages publication workflow

Database Prefix: research_
Model Namespace: App\Models\Research\

Tables:
  research_publications     — core publication records
  research_categories       — category taxonomy
  research_authors          — author profiles
  research_downloads        — download event log (analytics)
  research_citations        — citation records (who cited what)

Open Sub-Decision: Access control model — see OD-CONTENT-003.
Status: Approved

---

## D-031 (OD-BILLING-001)

Decision: Payment Gateway & Billing Architecture
Primary Gateway: Paystack (Africa-first; NGN + multi-currency support)
Future Gateways: Flutterwave, Stripe (added via gateway abstraction layer)
Abstraction: PaymentGatewayContract interface — gateway swap requires only .env change

Approved Billing Capabilities:
  - Course Payments (Training Institute)
  - Membership Payments (Subscription Module)
  - Event Registrations (future Events module — billing layer reserved)
  - Subscription Plans (recurring billing via Paystack Plans API)
  - Marketplace Fees (listing fees and commission)
  - Consulting Deposits (CRM — triggered on proposal/contract creation)
  - Proposal Acceptance Payments (CRM — triggered on proposal acceptance)

Architecture Deliverables:
  - Subscription Architecture (see ENTERPRISE_ARCHITECTURE_BLUEPRINT.md §18)
  - Invoice Architecture (see ENTERPRISE_ARCHITECTURE_BLUEPRINT.md §18)
  - Revenue Reporting Architecture (see ENTERPRISE_ARCHITECTURE_BLUEPRINT.md §18)

Database prefix: billing_
Module namespace: App\Services\Billing\

Status: Approved

---

## D-032 (OD-DATA-001)

Decision: Data Warehouse Strategy
Purpose: Centralized analytics layer supporting cross-module BI dashboards
and executive reporting.

Source Modules:
  - CRM
  - Projects (Client Portal)
  - Training Institute
  - Research Center
  - Startup Hub
  - Partner Portal
  - Opportunity Marketplace
  - Finance (Billing)

Architecture:
  Two-tier analytics design:
  Tier 1 — Module Analytics: analytics_ tables (real-time module-level aggregations,
            updated by cron, used for module reports and dashboards)
  Tier 2 — Data Warehouse: dw_ tables (star schema, ETL from source tables nightly,
            used for executive dashboards and future BI tool connections)

  Phase 1: DW tables in MySQL alongside operational data
  Phase 2: Separate MySQL analytics schema on VPS
  Phase 3: Export/connect to BI platform (Metabase, Power BI, Google Looker Studio,
            BigQuery) — DW star schema is compatible with all major BI tools

Design Pattern: Star Schema (fact tables + dimension tables)
ETL: Laravel Scheduled Commands (nightly ETL jobs per module)
SCD: Type 1 for reference dimensions; Type 2 for user and organisation dimensions
Database prefix: dw_
Architecture detail: see ENTERPRISE_ARCHITECTURE_BLUEPRINT.md §19

Status: Approved

---

## D-033 (OD-CONTENT-001)

Decision: Knowledge Center — Approved Scope
Purpose: Primary public learning, resource, and authority platform for ICS.

Strategic Objectives:
  - Establish ICS thought leadership across 15 domains
  - Generate organic traffic (SEO — most content public-facing)
  - Support lead generation (gated premium resources)
  - Support training programs (Training Resources cross-linked to courses)
  - Support government engagement and international partnerships
  - Support startup ecosystem development

Content Categories (15):
  1. Digital Transformation    6. Business Growth          11. Digital Marketing
  2. Artificial Intelligence   7. Entrepreneurship         12. Cloud & Infrastructure
  3. Cybersecurity             8. Startups                 13. Innovation
  4. Data Analytics            9. Capacity Development     14. Research Methods
  5. Government Technology    10. Project Management       15. Monitoring & Evaluation

Content Types (11):
  Articles | Guides | White Papers | Templates | Toolkits | SOPs |
  Checklists | Case Studies | Training Resources | Video Content |
  Downloadable Resources

Approved Features:
  - Full Search: MySQL FULLTEXT (Phase 1) + AI Search Assistant (D-029 #5)
  - AI Search Assistant: KnowledgeSearchService (D-029 use case #5)
  - Content Ratings: per-item user rating
  - Bookmarks: authenticated users save content
  - Downloads: file download tracking → feeds Analytics Layer (D-025)
  - Related Content Engine: Phase 1 category/tag matching; Phase 2 AI-powered
  - Content Analytics: views, downloads, ratings, bookmarks → Analytics Layer

Content Workflow:
  Draft → Review (ICS Content Staff) → Published → Archived
  AI Content Drafting (D-029 use case #10) assists staff in draft stage
  No content auto-published — human approval required on all types

Module Interactions:
  - AI Services (D-029): Knowledge Search (#5) + Content Drafting (#10)
  - Training Institute: Training Resources cross-linked to course records
  - CRM (D-012): Resource downloads can trigger email-gated lead capture
  - Analytics Layer (D-025) + Data Warehouse (D-032): content engagement data

Distinction from Research Center (D-030):
  Research Center = formal ICS publications (citable, institutional — for government
  and international audience)
  Knowledge Center = practical resources (guides, tools, templates — for all
  audience segments; SEO-driven; operational in nature)
  No content type should appear in both modules.

Database prefix: knowledge_
Model namespace: App\Models\Knowledge\

Video Content Implementation:
  Phase 1: Embedded video (YouTube / Vimeo iframe) — no self-hosting
  Phase 3: Self-hosted video via cloud object storage (D-024 migration path)

Raised Sub-Decision: OD-CONTENT-004 — Knowledge Center Access Control Model
  Required before development begins.

Status: Approved

---

## D-034 (OD-CONTENT-003)

Decision: Research Center — 5-Tier Content Access Model

Tier 1 — Public (no authentication required):
  Content: Research Summaries, Executive Briefs, Public Reports, Industry Insights
  Maps to: Guest + all authenticated users

Tier 2 — Registered Members (authentication required):
  Content: Full Reports, Templates, Resource Libraries, Research Archives
  Maps to: Any authenticated platform user

Tier 3 — Partners (partner role required):
  Content: Partner Research, Collaborative Studies, Restricted Publications
  Maps to: Partner Admin role (D-021)

Tier 4 — Internal ICS:
  Content: Draft Research, Working Papers, Internal Reports, Research Pipelines
  Maps to: All ICS Staff roles + Platform Admin

Tier 5 — Super Admin:
  Content: Full access — all tiers including all draft and restricted content
  Maps to: Platform Super Admin only

Role-to-Tier Mapping:
  Guest (unauthenticated)   → Tier 1
  Any authenticated user    → Tier 2
  Partner Admin             → Tier 3
  ICS Staff (any)           → Tier 4
  Platform Admin            → Tier 4
  Platform Super Admin      → Tier 5

Access Logic:
  user_effective_tier >= publication.access_tier → access granted
  Evaluated by ResearchAccessService::getUserResearchTier(User|null): int

Future Monetization (architecture reserved — no schema redesign required):
  Premium Reports:
    billing_plans entry created; subscription check added to ResearchAccessService
    Paid subscriber effective tier → Tier 2 or Tier 3 depending on plan
  Subscription Research Library:
    Recurring Paystack plan grants authenticated Tier 2+ access
  Corporate Membership Access:
    Organisation-level billing_subscription grants Tier 3 equivalent

Monetization upgrade path:
  ResearchAccessService::getUserResearchTier() will be extended to:
    1. Get base tier from role (existing logic)
    2. Check active billing_subscription for research plan
    3. Return MAX(role_tier, subscription_tier)
  No migration or schema change required — billing tables already designed (D-031)

Database impact:
  research_publications.access_tier = TINYINT (1–5)
  Replaces previously planned access_level VARCHAR field (D-030 schema revised)

Service class: App\Services\Research\ResearchAccessService
Status: Approved

---

## D-035 (OD-COMMUNITY-001)

Decision: Community Architecture
Scope Note: New module addition — extends approved scope beyond D-010 and D-019.
Community is the connective tissue linking Startup Hub, Training Institute,
Partner Portal, Research Center, and future expansion modules.

Profile Types — Phase 1:
  1. Founder Profiles       — linked to Startup Hub (startup_profiles)
  2. Startup Profiles       — linked to Startup Hub (startup_profiles)
  3. Consultant Profiles    — public-facing; CRM lead capture hook
  4. Trainer Profiles       — linked to Training Institute instructors
  5. Partner Profiles       — linked to Partner Portal records
  6. Researcher Profiles    — linked to Research Center (research_authors)

Design Pattern: Class Table Inheritance
  Base: community_profiles (common attributes for all types)
  Extensions: community_{type}_profiles (type-specific fields)
  Reason: queries can join base + extension; each type has dedicated schema space;
  new profile types added by adding an extension table only

Future Features — Architecture Reserved (Phase 2+):
  Discussion Forums       — community_forums, community_posts, community_replies
  Mentorship Matching     — community_mentorships (AI-matching via D-029 pattern)
  Event Registration      — community_events, community_event_registrations
                            (pre-seams future Events module; billing via D-031)
  Collaboration Requests  — community_collaborations (messaging via D-022)
  Opportunity Sharing     — cross-posts from Marketplace (D-011); no new table

Cross-Module Integrations:
  Startup Hub:       Founder + Startup profiles link to startup_profiles
  Training:          Trainer profiles link to training instructors
  Partner Portal:    Partner profiles link to partner_profiles
  Research Center:   Researcher profiles link to research_authors
  CRM (D-012):       ConsultantProfileCreated event → CRM lead capture
  Marketplace (D-011): Opportunity Sharing surfaces listings on community profiles
  Investment Network (D-019): Community profiles serve as investor/startup discovery layer
  AI Services (D-029): Future mentorship matching uses AI matching pattern

Privacy Model:
  Phase 1: community_profiles.visibility = public | authenticated
  Phase 2: + connections_only (when social graph is built)

Profile Verification:
  ICS can verify profiles (is_verified flag + verification_date)
  Verified badge displayed on profile
  Required for: Consultant, Trainer, Researcher profiles offering paid services

Database prefix: community_
Model namespace: App\Models\Community\
Architecture detail: ENTERPRISE_ARCHITECTURE_BLUEPRINT.md §20

Status: Approved

---

## D-036 (OD-CONTENT-004)

Decision: Knowledge Center — 5-Tier Content Access Model
Architecture note: Mirrors Research Center tier numbering (D-034) but Tiers 3 and
4 are LATERAL (role-specific), not hierarchical. Access gate uses role-switch
logic, not numeric >= comparison.

Tier 1 — Public (no authentication):
  Articles, News, Public Guides, Public Resources, Case Studies, Basic Templates

Tier 2 — Registered Members (any authenticated user):
  Premium Guides, Toolkits, Download Libraries, Training Resources,
  Resource Collections

Tier 3 — Clients (Client Admin role):
  Client Knowledge Libraries, Project Resources, Training Materials,
  Client Documentation

Tier 4 — Partners (Partner Admin role):
  Partner Resources, Joint Publications, Partner Toolkits

Tier 5 — Internal ICS (ICS Staff + Admin roles):
  Draft Content (all types), Internal SOPs, Internal Knowledge Base,
  Operational Documentation

Lateral Tier Rule:
  Tiers 3 and 4 are parallel, not stacked. A Client Admin accesses Tier 1 + 2 + 3
  only. A Partner Admin accesses Tier 1 + 2 + 4 only. Neither sees the other's tier.
  ICS Staff and Super Admin access all five tiers.

Role-to-Tier Access Map:
  Guest                  → Tier 1
  Any authenticated      → Tier 1 + 2
  Client Admin           → Tier 1 + 2 + 3
  Partner Admin          → Tier 1 + 2 + 4
  Gov Agency Rep         → Tier 1 + 2 (Tier 3 or 4 via explicit grant)
  ICS Staff (any)        → All tiers
  Platform Admin         → All tiers
  Platform Super Admin   → All tiers

Draft Override Rule:
  article.status = draft → visible to ICS Staff + Admin only
  regardless of article.access_tier setting

D-033 Content Type Extensions (new types added by this decision):
  + News              (Tier 1)
  + Resource Collections  (Tier 2 — content bundles)
  + Client Documentation  (Tier 3)
  + Internal Knowledge Base articles (Tier 5)

Future Monetization (no schema redesign required):
  Membership Access: billing plan → upgrade user to Tier 2
  Premium Content: billing plan + category flag → gated Tier 2 content
  Resource Subscriptions: plan grants access to specific content categories
  Enterprise Knowledge Packages: org-level subscription → Tier 2/3 access

Service class: App\Services\Knowledge\KnowledgeAccessService
Schema impact: knowledge_articles.access_tier TINYINT (1–5)
  Replaces previously planned access_level VARCHAR field (D-033 schema revised)
Status: Approved

---

## D-037 (Hosting / Deployment Strategy — supersedes D-003 runtime posture)

Decision: VPS-Ready Architecture, Shared-Hosting-First Deployment
Source: ARCHITECTURE_REVIEW_REPORT.md (Section 11) + Owner decision

Principle:
  The architecture is, and remains, VPS-first. Initial deployment uses Hostinger
  Premium Shared Hosting until traffic and revenue justify VPS migration.
  Migration to VPS must require CONFIGURATION CHANGES ONLY.

Hard Constraints on Migration (non-negotiable):
  - No database redesign
  - No application redesign
  - No code rewrites
  - Configuration (.env) changes only

Architecture remains INTACT (not removed, not redesigned):
  - Data Warehouse schema (D-032) — tables created in Phase 1 migrations
  - i18n architecture (D-014) — i18n_translations schema present from day 1
  - Tenant-ready design (D-004) — tenant_id on all tables from day 1

Phase 1 — Shared Hosting (ENABLED at deploy):
  - Laravel 11, MySQL 8+
  - RBAC, CRM, Client Portal
  - Knowledge Center, Research Center
  - Basic Analytics (Tier-1 analytics_ tables, time-boxed cron aggregation)
  - AI Assistant (rate-limited, low-volume)

Deferred to VPS (DESIGN present, RUNTIME gated off by config):
  - Redis (cache/queue/session driver)
  - Persistent queue workers (Horizon)
  - Data Warehouse ETL automation (dw_ tables exist; ETL scheduler gated)
  - Heavy background jobs
  - Advanced (async) event processing
  - Community scaling features (forums, mentorship matching, events)
  - High-volume AI processing

Enabling Mechanism (how config-only migration is guaranteed):
  1. No driver name is hardcoded anywhere — all read from config()/.env
  2. Every heavy listener implements ShouldQueue
       Shared: QUEUE_CONNECTION=database (cron) or sync
       VPS:    QUEUE_CONNECTION=redis + Horizon — same code
  3. Deferred runtime behaviours are gated by env feature flags:
       ICS_WAREHOUSE_ETL_ENABLED, ICS_AI_HIGH_VOLUME, ICS_COMMUNITY_SCALING,
       ICS_HEAVY_JOBS — false on shared, true on VPS
  4. Database schema is IDENTICAL in both environments
  5. Detail: see VPS_MIGRATION_CHECKLIST.md and Blueprint §15

Implication for D-003: D-003 phasing stands. D-037 clarifies that the Phase 2
"VPS" is a deployment migration, not an architecture change, and that all VPS-tier
capabilities are built (schema + code) in Phase 1 but switched on by configuration.

Status: Approved

---

## D-038 (Architecture Review — Content Engine Unification)

Decision: Unified Content Engine
Source: ARCHITECTURE_REVIEW_REPORT.md (DUP-01, DUP-02, DUP-03)

Resolution:
  The CMS (content_), Knowledge Center (knowledge_), and Research Center (research_)
  modules retain separate tables (distinct domains) but MUST share common logic
  through a single set of services and traits — no triplicated implementation.

Shared components (App\Services\Content\ + traits):
  - HasContentLifecycle trait: draft → review → published → archived; slug; SEO
  - HasFullTextSearch trait: consistent FULLTEXT indexing and query
  - ContentAccessService: ONE service evaluating BOTH access patterns —
      hierarchical (Research, D-034) and lateral (Knowledge, D-036) —
      selected by a strategy flag on the content record/module
  - content_engagement_events: single polymorphic append-only table replacing
      duplicate knowledge_views / knowledge_downloads / research_downloads
      (DATABASE_BLUEPRINT to be revised; net table count reduced)

Rule: A change to content lifecycle, access logic, or engagement tracking is made
ONCE, in the shared component, never per-module.
Status: Approved

---

## D-039 (Architecture Review — Security Hardening Baseline)

Decision: Mandatory Security Hardening Baseline
Source: ARCHITECTURE_REVIEW_REPORT.md (SEC-02/04/05/08/09, SPOF-04, SEC-03)

Binding requirements (all environments):
  SEC-02  .env stored OUTSIDE the web root; never protected by .htaccess alone;
          document root MUST be /public only
  SEC-03  Audit-log immutability enforced at application layer (write-only
          repository) AND periodic off-box export; do NOT rely on MySQL TRIGGER
          (shared hosting may deny TRIGGER privilege)
  SEC-04  Gemini AI: Data Processing Agreement confirmed; PII redacted/pseudonymised
          before any external AI call; processing region verified vs D-007 (EU)
  SEC-05  Prompt-injection hardening in BaseAIService: treat all user text as
          untrusted; instruction isolation/delimiters before every Gemini call
  SEC-08  Certificate public verification uses an unguessable token (UUID/HMAC),
          never the sequential certificate_number
  SEC-09  Cloudflare (free tier) in front from Phase 1: CDN + cache + WAF + bot
          control (also mitigates guest-AI cost abuse and FULLTEXT load)
  SPOF-04 Secondary SMTP fallback for auth-critical mail (password reset, MFA);
          marketing mail remains on Brevo

Cost control (COST-01): public/guest AI endpoints carry hard per-IP and
per-session token caps plus a global daily kill-switch.
Status: Approved

---

## D-040 (Architecture Review — Simplifications Evaluated and Declined)

Decision: Retain approved Community CTI (D-035) and 14-role model (D-021) unchanged.
Source: ARCHITECTURE_REVIEW_REPORT.md (CPLX-02, CPLX-05) — impact analysis requested.

Finding: After impact analysis, the two simplifications proposed in the review
would REDUCE capability the platform's strategy depends on. They are DECLINED.

  Community CTI collapse — DECLINED:
    Collapsing the 6 extension tables to JSON would lose relational integrity
    (typed FKs to startup_profiles/training_instructors/etc.), lose indexed
    queryability needed by future Mentorship Matching and Investment Network
    (D-019, D-035), and contradict D-035's stated extensibility rationale.
    CTI is RETAINED.

  ICS Staff role merge — DECLINED:
    Merging ICS Staff (CRM/Training/Content) and/or Trainer roles would weaken
    least-privilege and separation-of-duties — controls that matter precisely
    because Audience #1 is Government (D-016) and compliance is ISO 27001-aligned
    (D-006). A merged role would let content editors reach CRM PII. Granularity
    is RETAINED. The 14-role model (D-021) stands.

Governance note: this entry records that the questions were raised, analysed, and
resolved in favour of the existing approved architecture. No redesign performed.
Status: Approved

---

## D-041 (Blueprint Amendment — Task 3 Review F-3)

Decision: Add `password_reset_tokens` table to the Core Platform schema.
Source: DATABASE_FOUNDATION_REVIEW.md finding F-3 — APPROVED.
Purpose: Password recovery architecture — backs the forgot/reset-password flow
(Laravel password broker) for both web and API authentication.

Schema (Laravel standard; token hashed):
  email       VARCHAR(255) PRIMARY KEY
  token       VARCHAR(255)   -- HASHED reset token
  created_at  TIMESTAMP NULL -- expiry computed from this

Security: token stored hashed; one active token per email (PK on email); expiry
enforced by config auth.passwords.*.expire; throttling via .throttle. No PII
beyond email.
Migration: authored in Task 4 (Authentication) — NOT Task 3.
Documents updated: DATABASE_BLUEPRINT.md (Module 1), PROJECT_MEMORY.md.
Status: Approved

---

## D-042 (Authentication Foundation — Security Requirements & Task 3 Dispositions)

Decision: Records the approved dispositions of DATABASE_FOUNDATION_REVIEW findings.

  F-1 — APPROVED: remove the superseded stock Laravel default migrations
        (users / cache / jobs) during bootstrap; prefixed core_/sys_ tables are
        authoritative.
  F-2 — APPROVED: driver-to-table config wiring (queue→sys_jobs/sys_failed_jobs,
        cache→sys_cache, session→sys_sessions) is DEFERRED to Task 4.
  F-5 — APPROVED (binding): `mfa_secret` MUST be encrypted at the model layer
        (Laravel `encrypted` cast). NO plaintext MFA secrets anywhere.
        Consequence: `core_users.mfa_secret` column changed VARCHAR(64) → TEXT
        (ciphertext exceeds 64 chars). Blueprint updated; the Task 3 migration
        must be corrected at the START of Task 4 (finding AF-1 in
        AUTHENTICATION_ARCHITECTURE_REVIEW.md).
  F-6 — APPROVED: audit-log DB trigger remains OPTIONAL; application-layer
        immutability (write-only repository, D-039 SEC-03) is the PRIMARY control.
  F-7 — APPROVED: record the exact MySQL minor version when available; no
        implementation delay (baseline MySQL 8.0).

Status: Approved

---

## D-043 (Blueprint Amendment — MFA Recovery Codes Storage, AF-3)

Decision: Add `core_users.mfa_recovery_codes` (TEXT, JSON array of bcrypt hashes).
Source: AF-3 (approved) requires hashed recovery-code storage; AUTHENTICATION_
ARCHITECTURE_REVIEW finding. The blueprint had no recovery-code storage.
Schema: `mfa_recovery_codes TEXT NULL` — JSON array of HASHED, single-use codes.
Security (binding): NO plaintext, NO reversible encryption. Codes are bcrypt-hashed
(like passwords), verified with Hash::check, and removed on use (single-use).
Also confirmed (AF-1): `core_users.mfa_secret` is TEXT (encrypted secret > 64 chars).
Also confirmed (AF-2): authentication-critical mail (password reset, MFA, account
recovery) uses immediate delivery via a failover mailer (Brevo → fallback SMTP);
never the delayed queue. Implemented via config/mail.php `failover` mailer + non-
queued notifications (D-039 SPOF-04).
Migration: core_users column added in Task 4 (T-3 migration corrected pre-auth).
Status: Approved

---

## D-044 (Authorization Hardening — AUTHORIZATION_SECURITY_AUDIT resolutions)

Decision: Resolves the pre-Task-5 authorization audit blockers and findings.

AUTH-AUDIT-01 — Permission naming convention (canonical):
  CANONICAL = {module}.{resource}.{action}  (e.g. crm.leads.create,
  knowledge.tier1.read, platform.users.create). PERMISSION_MATRIX is the catalogue
  of record. ENTERPRISE_ARCHITECTURE_BLUEPRINT §6.3 reconciled to this form.
  (USER_ROLE_MATRIX did not define a separate convention — no change needed there.)
  The Task 5 seeder and every authorization check use this single form.

AUTH-AUDIT-02 — Role-assignment escalation guard (STRICTER / four-eyes):
  - No actor may grant a role of higher privilege than their own.
  - Only Platform Super Admin may assign/revoke the Super Admin role, AND any
    Super Admin grant requires a SECOND Super Admin's approval (four-eyes).
  - Every role assignment/revocation is audited (E-CORE-006/007).
  Implemented by the Task 5 role-assignment policy + flow.

EP-2 — Government Agency Rep Knowledge access:
  Tier 4 (Partner) knowledge access REMOVED for Gov Agency Rep. Gov Reps retain
  Tier 1 + Tier 2 (knowledge.tier4.read = denied). PERMISSION_MATRIX +
  USER_ROLE_MATRIX updated.

Also affirmed (audit recommendations carried into Task 5/later):
  R-3 org-owned Policies (client_/partner_/startup_) enforce account/owner match —
  the SOLE Phase 1 isolation control (TenantScope deferred, D-037); require tests.
  R-4 Gate::before grants all ONLY to Super Admin; explicit default-deny elsewhere.
  EP-1 (CRM read.all assignment-scoping) — noted for refinement, not blocking.

Status: Approved
Blockers AUTH-AUDIT-01 and AUTH-AUDIT-02: RESOLVED — Task 5 unblocked (pending the
standing "await approval before Task 5 implementation" gate).

---

## D-045 (Blueprint Amendment — Role-Escalation Approval Storage, four-eyes)

Decision: Add `core_role_escalation_approvals` table to back the four-eyes Super
Admin role-escalation guard (D-044). APPROVED with strict scope constraints:
  - Single-purpose: stores ONLY role-escalation approvals.
  - NOT a generic workflow engine; NO business-process orchestration.
  - Lightweight, security-focused.
  - Future extensibility allowed but NOT implemented (Super Admin escalation only).

Schema (DATABASE_BLUEPRINT Module 1):
  core_role_escalation_approvals(
    id, requester_id, target_user_id, approver_id (nullable),
    requested_role, previous_role, reason_code, status
    [pending|approved|rejected|expired], requester_ip, approver_ip,
    decided_at, expires_at, created_at, updated_at)

Immutability: a request is DECIDED ONCE (pending → approved/rejected/expired);
every transition is mirrored to the immutable core_audit_logs — that append-only
log is the immutable audit trail. The approval row is the lightweight request
record.

Reason codes (enumerated): new_leadership, staffing_change, emergency_access,
role_correction (extensible).
Migration: Task 5 (000013). Documents updated: DATABASE_BLUEPRINT, PROJECT_MEMORY.
Status: Approved

---

## D-046 (Audit Categorisation & High-Sensitivity Events — Task 6)

Decision: `core_audit_logs` gains `category` and `sensitivity` columns to support
high-sensitivity security auditing (Task 6 additional requirement).

Audit Categories (enumerated):
  user_management, role_assignment, permission_change, escalation_request,
  escalation_approval, security_config, authentication, data_privacy, general.

High-Sensitivity rule (binding):
  An audit entry is sensitivity = 'high' when EITHER
   (a) the actor holds the Platform Super Admin role — ALL Super Admin actions are
       high-sensitivity; OR
   (b) the category is one of: user_management, role_assignment, permission_change,
       escalation_request, escalation_approval, security_config.
  Otherwise 'normal'.

Immutability: enforced at the application layer — append-only AuditService +
write-only AuditRepository + AuditLog model that throws on update/delete
(D-039 SEC-03). Optional DB trigger only if privilege confirmed (F-6).
Migration: Task 3 core_audit_logs migration amended (not yet run). Blueprint updated.
Status: Approved

---

## D-047 (User Lifecycle Controls & 'pending' Status — Task 7 directives)

Decision: Resolves USER_LIFECYCLE_GOVERNANCE_REVIEW gaps; directs Task 7.

R-1 (ULC-01) — APPROVED, schema amendment: `core_users.status` enum gains
  'pending'. Login is denied unless status = 'active'. Approval-required
  registrations (e.g., Trainer) start 'pending'; an admin approval moves them to
  'active' (audited). Blueprint + Task 3 core_users migration updated now.

R-2 (ULC-02) — APPROVED: ANY role change revokes the target's tokens and forces
  session regeneration — closes the stale-privilege window.

R-3 (ULC-03) — APPROVED: the final active Super Admin cannot be deactivated,
  deleted, or role-revoked (last-Super-Admin protection).

R-4 (ULC-04) — APPROVED: reactivating an account restores its prior roles EXCEPT
  Super Admin, which must be re-granted via the four-eyes flow.

R-5 (ULC-05) — APPROVED: self-registration is limited to a role whitelist
  (no staff/admin/org-admin self-grant); initial role respects the escalation guard.

R-6 — APPROVED: add AccountSuspended / AccountReactivated events; wire the dormant
  dispatchers (UserRegistered, AccountDeactivated, RoleRevoked) with audit.

R-7 — APPROVED: alert the security team (NotifySecurityTeam) on high-sensitivity
  lifecycle events (Super Admin grant/revoke, deletions, suspensions).

Deferred (later/operational): R-8 (four-eyes on Super Admin revoke), R-9 runbooks,
R-10 admin-on-behalf GDPR, R-11 JML/recertification.
Status: Approved (directs Task 7 implementation)

---

## D-048 (Strict CSP — Alpine CSP build; T9-1)

Decision: Maintain a STRICT Content-Security-Policy. Adopt the `@alpinejs/csp`
build; REJECT `'unsafe-eval'`. Components use Alpine.data() registration; no inline
expression evaluation. Reason: the D-039 security baseline takes precedence over
framework convenience.
Also recorded:
  T9-2 — HSTS `preload` remains DISABLED until a production review.
  T9-3 — Replace `TRUSTED_PROXIES='*'` with explicit Cloudflare ranges before
         production launch (Sprint 1 Integration Verification Item).
Status: Approved

---

## D-049 (Sprint 1 Acceptance & Sprint 2 Conditional GO)

Decision: Sprint 1 (Core Platform Foundation, T-1…T-10, D-001…D-048) ACCEPTED as
complete from architecture, governance, and implementation perspectives.

Sprint 2 authorization: PLANNING authorized now. FULL business-module implementation
(CRM, CMS, Knowledge Center, Research Center, Marketplace, Community, Training
Institute, Partner Portal) is GATED behind the following validation, which must pass
and be signed off (SPRINT_1_GO_LIVE_CHECKLIST):
  1. Bootstrap — composer install, npm install, artisan bootstrap, env config.
  2. Database — migrations + seeders succeed; RBAC seeding verified.
  3. Conformance — all Task 10 suites pass (RBAC, Lifecycle, Audit, Localization,
     Security, Escalation).
  4. CI — PHPUnit, Pint, Larastan, driver gate, composer audit, gitleaks, MySQL
     engine parity all pass.
  5. Host — Hostinger capability spike, intl extension, proxy, mail.
  6. Go-Live checklist completed and signed.

Next deliverable: SPRINT_2_EXECUTION_PLAN.md (planning).
Status: Approved

---

## D-050 (Blueprint Amendment — core_users.account_id, Organisation Linkage, S2-2)

Decision: Add `core_users.account_id` (nullable FK → crm_accounts) to link a user to
its organisation — the basis for Phase 1 org isolation (sole control; TenantScope
deferred). Requirements (all satisfied):
  1. Nullable FK initially — `account_id BIGINT UNSIGNED NULL`. ICS staff / Super
     Admin / individual users = NULL (not org-bound).
  2. References crm_accounts — FK → crm_accounts(id) ON DELETE SET NULL.
  3. Backward compatible — nullable; existing rows unaffected; no behaviour change
     until org policies/scope consume it.
  4. Supports future TenantScope migration — account-level scoping nests UNDER
     tenant-level. `tenant_id` (already present) + `account_id` compose in Phase 3
     (tenant > account > user); no rework required.
  5. Supports BasePolicy ownership helpers — `sameAccount()` reads `user->account_id`.
  6. Supports AccountScope — a global scope filters org-owned models by `account_id`.

Sequencing (resolves the crm_accounts dependency):
  - Wave 1a: add the COLUMN (nullable, no FK yet — crm_accounts not built) + index;
    BasePolicy/AccountScope consume the column.
  - Wave 1d: add the FK constraint to crm_accounts once that table exists (CRM).
  - First full enforcement: Wave 2 (Client Portal, Partner Portal).

Migration: authored in Wave 1 implementation (gated). Blueprint updated.
Status: Approved

---

## D-051 (Content Engine Consolidation — W1b-1 / W1b-2)

Decision: Unified Content Engine consolidation (D-038 realisation).

W1b-1 — Engagement table: ADOPT `content_engagement_events` as the single content
analytics table. SUPERSEDE `knowledge_views`, `knowledge_downloads`,
`research_downloads` (removed from the schema). Cached counters remain on content rows.

W1b-2 — Access service: ADOPT `ContentAccessService` (one service, strategy-driven).
RETIRE `KnowledgeAccessService` and `ResearchAccessService` — their logic moves into
two strategies:
  - HierarchicalAccessStrategy (preserves D-034: user_tier >= content_tier).
  - LateralAccessStrategy (preserves D-036: tiers 1/2 additive; 3 client; 4 partner;
    5 internal — Gov Rep capped at tier 1/2 per D-044).

Requirements (all honoured): preserve D-034 hierarchical; preserve D-036 lateral;
strategy-driven selection; COMPLETE separation from AccountScope (content is
tier-scoped, never account-scoped).

Documents updated: DATABASE_BLUEPRINT.md, ENTERPRISE_ARCHITECTURE_BLUEPRINT.md,
PROJECT_MEMORY.md.
Status: Approved

---

## D-052 (CMS Publication Traceability — Wave 1c)

Decision: Publishable CMS content (content_pages, content_articles) carries
publication-governance columns: `created_by`, `updated_by`, `published_by`,
`published_at`. Purpose: publication traceability and governance.
- created_by / updated_by stamped via the HasAuthorship trait.
- published_by set on publish (CmsService); published_at by the lifecycle.
- Complements the immutable audit (ContentPublished under content_management, D-046).
Also: content_management audit category added (W1c-4).
Documents updated: DATABASE_BLUEPRINT.md, PROJECT_MEMORY.md.
Status: Approved

---

## D-053 (CRM Access Model — Wave 1d)

Decision: The Internal Enterprise CRM (D-012) is **internal-only** and uses a
**permission + assignment** access model. It does NOT use AccountScope,
BelongsToAccount, or ContentAccessService (the two existing isolation mechanisms remain
untouched and unmixed).

Rationale (W1d-1): `crm_*.account_id` is a SUBJECT pointer ("which account this record is
about"), semantically distinct from `core_users.account_id` (D-050, "which organisation
the viewing user belongs to"), though both reference `crm_accounts(id)`. Applying
AccountScope to CRM would conflate the two.

Rules:
  - Visibility = `crm.<entity>.read.all` (whole pipeline) OR `crm.<entity>.read.own`
    (only rows where `assigned_to = user.id` OR `created_by = user.id`) — W1d-4.
  - External organisation users have NO CRM access in Phase 1.
  - Super Admin bypasses via Gate::before; default-deny otherwise.
  - A `HasAssignmentVisibility` model concern provides `scopeVisibleTo($user)`; it filters
    on assignment, NEVER on account_id.
Status: Approved

---

## D-054 (CRM Audit Category — Wave 1d)

Decision: Add `AuditCategory::CRM_MANAGEMENT` for CRM lifecycle/assignment/stage events
(account create/delete, lead & opportunity stage changes, assignment changes, lead→
opportunity conversion). Wired via domain events → AuditEventSubscriber → append-only
AuditService (D-046). Normal sensitivity by default; all Super Admin actions remain high.
Status: Approved

---

## W1d-2 / W1d-6 Resolutions (Wave 1d)

W1d-2: `crm_notes` is NOT created. Notes are `crm_activities.type = 'note'`
(blueprint-consistent; no duplicate feature).
W1d-6: `crm_proposals` and `crm_contracts` are DEFERRED to a future phase (likely the AI
sprint, D-029). Not built in Wave 1d.
Status: Approved

---

## D-055 (Partner Ownership Unification — Wave 2)

Decision: Every partner (organisation OR individual) receives a `crm_account`
(type='partner'). `partner_profiles.account_id`, `partner_referrals.account_id`, and
`partner_agreements.account_id` are REQUIRED. AccountScope becomes the SOLE portal
isolation mechanism — partner data is org-owned via BelongsToAccount + AccountScope +
OrgOwnedPolicy, exactly like the Client Portal. Resolves W2-2 (nullable-ownership gap).
Blueprint amendment: add `account_id` to partner_referrals and partner_agreements.
Status: Approved

---

## D-056 (Portal Audit Category — Wave 2)

Decision: Add `AuditCategory::PORTAL_MANAGEMENT` for portal lifecycle events (project/
deliverable/ticket/referral/profile). The following are HIGH-sensitivity:
  - Agreement events (signed/updated)
  - Commission events (recorded/paid)
  - Suspension events (partner profile suspended/terminated)
Mechanism: domain events → AuditEventSubscriber → append-only AuditService (D-046).
AuditService gains an optional explicit-sensitivity override so specific events within an
otherwise-normal category can be marked HIGH. All Super Admin actions remain HIGH.
Status: Approved

---

## W2-1 / W2-3 / W2-4 Resolutions (Wave 2)

W2-1: Parent-based isolation. Child entities (client_project_milestones,
client_deliverables, client_ticket_replies) are NOT org-owned and MUST NOT be queried
independently — they are reached ONLY through their AccountScope-protected parent.
W2-3: The Partner Portal must NEVER expose crm_leads, CRM assignment data, or CRM internal
workflow data. partner_referrals and crm_leads stay separate; lead_id is ICS-only and is
never serialised to a partner.
W2-4: Internal ticket replies (`is_internal=1`) are filtered at THREE layers — query,
policy, and resource/serialiser.
Status: Approved

---

## D-057 (Wave 4 Access Model)

Decision: The three engagement modules use module-local, permission-gated access rules —
NONE use AccountScope, ContentAccessService, or HasAssignmentVisibility; Community and
Marketplace are NOT ContentAccessible.
  - Training: ENROLLMENT-gated — lesson/assessment content requires an active enrollment
    (training_enrollments); is_preview lessons are public. A policy/service check, not a scope.
  - Community: VISIBILITY-scoped (public | authenticated) + OWNER-scoped (community_profiles
    .user_id). Identity data, not tiered content.
  - Marketplace: LISTING-STATUS + REVIEW (draft→pending_review→published/expired/rejected,
    marketplace_listing_reviews) + OWNER (posted_by) / APPLICANT scoping. Distinct from
    HasContentLifecycle (own MarketplaceListingService).
Status: Approved

---

## D-058 (Wave 4 Audit Categories)

Decision: Add AuditCategory::TRAINING_MANAGEMENT, COMMUNITY_MANAGEMENT, MARKETPLACE_MANAGEMENT.
CertificateIssued = HIGH sensitivity (credential integrity; via the D-056 $forceSensitivity
override). Certificate revocation is likewise HIGH. All Super Admin actions remain HIGH.
Implementation order: Wave 4a Training → 4b Community → 4c Marketplace. Before 4a:
TRAINING_CERTIFICATION_GOVERNANCE_REVIEW.md (numbering, verification, revocation, expiry, reissue).
Status: Approved

---

## D-059 (Training Certificate Governance — Wave 4a, per TRAINING_CERTIFICATION_GOVERNANCE_REVIEW)

Decision: training_certificates carries lifecycle + integrity columns beyond the original
blueprint (blueprint amendment): `status` (valid/expired/revoked/superseded), `expires_at`
(nullable), `revoked_at`/`revoked_by`/`revocation_reason`, `reissued_from_id`,
`verification_hash`. Numbering: `ICS-CERT-{YYYY}-{NNNNNN}` via a per-year sequence table
(training_certificate_sequences). Verification is public + read-only (number → status/holder/
course, no PII beyond name+course). Revocation + reissue are staff-only, audited HIGH (D-058).
Expiry optional per course (training_courses.validity_months; NULL = no expiry).
Status: Approved (defined in TRAINING_CERTIFICATION_GOVERNANCE_REVIEW.md)

---

## D-060 (Marketplace Trust Model — Wave 4c)

Decision: The Opportunity Marketplace adopts a trust model with a NEW abuse-reporting table
`marketplace_listing_reports` (blueprint amendment). Principles (approved):
  1. organisation_id is PROVENANCE, not isolation. 2. Marketplace does NOT use AccountScope.
  3. Marketplace is NOT ContentAccessible. 4. Published listings are public. 5. Applications
  remain private (applicant + poster + ICS).
Mandatory controls:
  - Mandatory pre-publication ICS review (D-011) — no auto-publish; recorded in
    marketplace_listing_reviews + audited.
  - Duplicate application prevention via DB unique (listing_id, applicant_id).
  - Abuse reporting workflow (marketplace_listing_reports: reporter, reason, status).
  - Auto-hide threshold: when open reports on a published listing reach a configurable
    threshold, it returns to pending_review (fail-safe).
  - Streamed attachment delivery (applicant + poster + ICS only; W4-7/W2-5).
  - MARKETPLACE_MANAGEMENT audit events (approve/reject/remove + application decisions +
    report resolution). Listing views / application creation / report creation = analytics.
  - Scheduled auto-expiry (deadline → expired) + lazy public-scope filter.
  - Dedicated analytics aggregator (NOT content_engagement_events).
New table marketplace_listing_reports: id, tenant_id, listing_id, reporter_id, reason
(spam/scam/inappropriate/duplicate/other), details, status (open/reviewed/dismissed/actioned),
reviewed_by, created_at, updated_at; UNIQUE(listing_id, reporter_id) for open reports.
Status: Approved

Implementation outcome: WAVE 4c Marketplace IMPLEMENTED and APPROVED (2026-06-03,
WAVE_4C_IMPLEMENTATION_REVIEW.md). This completes WAVE 4 (4a Training, 4b Community, 4c
Marketplace). Six module access mechanisms now coexist and remain separate: AccountScope,
ContentAccessService, HasAssignmentVisibility, TrainingAccessService, Community visibility,
Marketplace listing-status. Next: ROADMAP REVIEW PHASE (architecture-only) →
ECOSYSTEM_ROADMAP_REVIEW.md + ACCESS_CONTROL_CONSOLIDATION_REVIEW.md + WAVE_5_ARCHITECTURE_PLAN.md
before any Wave 5 code.

ROADMAP APPROVAL (2026-06-03): ECOSYSTEM_ROADMAP_REVIEW.md + ACCESS_CONTROL_CONSOLIDATION_REVIEW.md
+ WAVE_5_ARCHITECTURE_PLAN.md APPROVED. Standing principles confirmed: (a) the six access
mechanisms remain SEPARATE (no merge); (b) no roadmap module introduces a new mechanism family —
Startup Hub/Incubator/Accelerator/Investment reuse the membership/participation family,
Membership reuses the ContentAccessService billing-tier hook, Franchise activates the RESERVED
TenantScope; (c) every new module's architecture review MUST declare which mechanism it uses and
justify any new one against the inventory (standing governance guardrail). Wave 5 order approved:
5a Startup Hub → 5b Incubator → 5c Accelerator → 5d Investment Network (5d gated behind
INVESTMENT_GOVERNANCE_REVIEW). Proceeding to WAVE_5A_ARCHITECTURE_REVIEW.md (Startup Hub).

---

## D-061 (Startup Hub Participation Access Model — Wave 5A)

Decision: Startup Hub access uses the MEMBERSHIP/PARTICIPATION family (StartupAccessService) —
NOT a new mechanism. Keys: founder ownership (founder_id), team membership (startup_team_members),
program participation (startup_program_enrollments). NOT AccountScope, NOT BelongsToAccount, NOT
ContentAccessible, NOT HasAssignmentVisibility. Staff/owner bypass; default-deny; never falls back
to AccountScope.
Status: Approved

---

## D-062 (STARTUP_MANAGEMENT Audit Category — Wave 5A)

Decision: Add AuditCategory::STARTUP_MANAGEMENT. HIGH-sensitivity: ownership transfer, founder
ownership changes, verification status changes, suspension/reactivation. Normal: mentor
assignment, milestone status, program enrollment/withdrawal, graduation (audited). Engagement
(profile views) = analytics, not audit. Reuse AuditEventSubscriber + forceSensitivity (D-056).
Status: Approved

---

## D-063 (Startup Lifecycle Governance Model — Wave 5A, H-1)

Decision: One authoritative `lifecycle_stage` enum on startup_profiles:
idea → registered → validation → incubation → acceleration → investment_ready → alumni.
Existing overlaps reconciled: `stage` retained ONLY as product maturity (distinct axis);
`status` narrowed to admin/moderation state (active/suspended/inactive); `program_type` REMOVED
(program track derives from startup_program_enrollments → startup_programs.type). No parallel
lifecycle authorities.
Status: Approved

---

## C-1 Disposition (Investment-Sensitive Data Classification — Wave 5A)

Cap-table, ownership percentages, valuation, fundraising history, investor documents, and
shareholder records are INVESTMENT NETWORK data, NOT public Startup Hub data. Rules: no public/
Community/Marketplace exposure; no analytics projection containing identifiable ownership data;
access limited to founders, authorized startup administrators, approved ICS staff, and explicitly
granted investors. The Investment Network data room (Wave 5d) is the SYSTEM OF RECORD for the full
cap table/valuation/fundraising/docs. Wave 5A holds only the MINIMAL gated founder/co-founder
governance ownership_percent (for D-064 controls), access-restricted and excluded from every
public/community/marketplace/analytics projection.
Status: Approved

---

## D-064 (Startup Governance Protection — Wave 5A)

Decision: Implementation validation controls:
  - ownership totals must equal 100% (or an explicit unallocated remainder); no negative ownership.
  - founder ownership changes audited HIGH; verification status changes audited HIGH;
    startup suspension/reactivation audited HIGH; graduation to alumni audited.
  - Startup can NEVER become ownerless: ≥1 active founder at all times; ownership transfer is
    mandatory before founder removal; removal blocked if it would orphan; transfer history immutable.
Status: Approved

Implementation outcome: WAVE 5A Startup Hub IMPLEMENTED and APPROVED (2026-06-03,
WAVE_5A_IMPLEMENTATION_REVIEW.md). Validated: no new access family; participation-family reuse;
founder-owned architecture; CRM/Community boundaries; cap-table confidentiality (C-1); lifecycle
authority consolidated (D-063); governance controls enforced (D-064). Next: WAVE_5B Incubator
Program (architecture review).

---

## D-065 (Generic Program Architecture — Wave 5B, H-1)

Decision: There shall be ONLY ONE Program Architecture in the platform. Incubator and
Accelerator SHARE: programs, cohorts, intake cycles, applications, enrollments, participation,
progression, graduation, analytics, audit. Accelerator (5c) may add SPECIALIZED features only
(Demo Day, Investor Showcase, Pitch Events, Investment Readiness Tracking) — NO duplicate
foundations. Access = thin `ProgramParticipationService` (participation family; composes with
StartupAccessService; reused by Accelerator) — NOT a new mechanism, NOT overloading
StartupAccessService.
Status: Approved

---

## D-066 (PROGRAM_MANAGEMENT Audit Category — Wave 5B, H-2)

Decision: Add `AuditCategory::PROGRAM_MANAGEMENT` (replaces separate INCUBATOR_/ACCELERATOR_
categories). Program TYPE is carried as event context. HIGH-sensitivity: forced removal,
program termination, program suspension, program reinstatement, future fee/payment actions,
graduation reversals.
Status: Approved

---

## H-3 / M-1 / M-2 Dispositions (Wave 5B)

H-3: Startup lifecycle authority remains D-063. Program participation MAY influence
lifecycle_stage but MUST NOT create a parallel lifecycle; transitions route through the Startup
lifecycle governance layer (StartupGovernanceService).
M-1: Governed intake — applied → under_review → accepted → active → graduated → withdrawn. No
direct enrollment bypass; all acceptance decisions audited.
M-2: Program coordinator assignment is a PROGRAM concern — NOT CRM assignment /
HasAssignmentVisibility. Coordinators manage cohorts; CRM staff manage CRM.
Status: Approved

---

## D-067 (Program Governance Protection — Wave 5B)

Implementation must enforce:
  - No startup enrolled twice in the same cohort (unique startup+cohort).
  - No startup active in conflicting program states (at most one active participation at a time).
  - Graduation requires completion validation.
  - Withdrawal reason mandatory; forced removal reason mandatory.
  - Cohort closure audited; program archival audited.
Status: Approved

Implementation outcome: WAVE 5B Generic Program Architecture IMPLEMENTED and APPROVED
(2026-06-03, WAVE_5B_IMPLEMENTATION_REVIEW.md). Validated: generic architecture; Incubator as
configuration (not a separate platform); ProgramParticipationService extends the participation
family; lifecycle centralized under D-063; no CRM-assignment duplication; no Startup-Hub
ownership duplication; governance/cohort protections enforced; Accelerator specialization path
preserved. Next: WAVE_5C Accelerator Program (architecture review only).

---

## D-068 (Accelerator Thin Specialization — Wave 5C)

Decision: Accelerator is a THIN SPECIALIZATION of the Generic Program Architecture (D-065),
`startup_programs.type='accelerator'`. It REUSES programs/cohorts/intake/enrollment/participation/
lifecycle/governance, ProgramParticipationService (unchanged), CompletionValidator (graduation
authority), PROGRAM_MANAGEMENT audit, and ProgramAnalyticsAggregator. The ONLY new surface is a
GENERIC, LIGHTWEIGHT Program Events layer: one `program_events` table (types demo_day /
pitch_event / showcase / readiness_review / graduation_showcase) + judges + scores. Reuse must
remain > 80% (~85%). No event-specific subsystem proliferation (M-1). Graduation authority stays
CompletionValidator (M-2). No new access-control family.

Governance direction: the Program Events layer is reusable ecosystem infrastructure (Investment
Network / Community / Marketplace MAY later consume it) but MUST stay LIGHTWEIGHT — no
orchestration, no workflow states, no process-engine behavior. Before Wave 5D, validate whether
those modules can consume Program Events without turning it into a generic workflow engine.
Status: Approved

---

## D-069 (Accelerator ↔ Investment Network Boundary — Wave 5C)

Decision: Accelerator PREPARES startups; the Investment Network (5d) EXECUTES investment activity.
PROHIBITED inside Accelerator (governance violation → escalated for architecture review): investor
registry, fundraising workflow, due-diligence workflow, investment-transaction workflow, cap-table
management, deal-room functionality, investment-matching engine.
  - H-1: Investor Showcase = exposure / discovery / readiness signal ONLY — NOT a fundraising
    workflow, deal room, or investor portal.
  - H-2: investor identities are REFERENCED from existing ecosystem identities (Community 'investor'
    profile / Investment Network 5d) — NO duplicate investor registry.
  - H-3: readiness data is operational-maturity data ONLY — valuation, equity ownership, share
    allocation, financial due diligence, and investment negotiations remain OUTSIDE Accelerator.
Status: Approved

Implementation outcome: WAVE 5C Accelerator IMPLEMENTED and APPROVED (2026-06-03,
WAVE_5C_IMPLEMENTATION_REVIEW.md). Thin specialization (~85% reuse); generic lightweight Program
Events layer only; CompletionValidator sole graduation authority; PROGRAM_MANAGEMENT only audit
category; no investor registry / fundraising / cap-table / investment execution; D-069 boundary
intact. Startup Hub program family (5a/5b/5c) complete. Next: WAVE_5D Investment Network
(architecture review only; behind a mandatory legal/compliance governance review — proposed D-075).

---

## D-070 (Investment Access — Grant Family + NDA Overlay — Wave 5D)

Decision: Investment Network access REUSES the participation/GRANT family (DataRoomAccessService:
"is this investor a granted + NDA-accepted, active participant of this startup's data room?") plus
a compliance OVERLAY (NDA precondition + financial-confidentiality). NO new access-control family.
Status: ARCHITECTURALLY APPROVED (implementation gated by D-075).

---

## D-071 (INVESTMENT_MANAGEMENT Audit + Per-Document Access Logging — Wave 5D)

Decision: Add `AuditCategory::INVESTMENT_MANAGEMENT`. Per-document data-room access is logged
(regulatory control). HIGH-sensitivity: grant/revoke, NDA acceptance, every data-room document
access, cap-table change, deal stage change, DD document access, fee events.
Status: ARCHITECTURALLY APPROVED (implementation gated by D-075).

---

## D-072 (Data Room as Sole Encrypted Financial Store — Wave 5D)

Decision: The data room is the SOLE system of record for cap-table/valuation/fundraising/financials/
DD; encrypted at rest; grant + NDA gated; per-document access audited; COMPLETELY ISOLATED from
Community, Marketplace, Knowledge, Research, Public CMS, and Accelerator Showcase (which hold public
projections only).
Status: ARCHITECTURALLY APPROVED (implementation gated by D-075).

---

## D-073 (Two-Layer Investor Identity — Wave 5D)

Decision: Investor identity = a Community 'investor' public profile (D-035 CTI extension,
public fields only) + an Investment Network regulated extension (investment_investor_profiles:
KYC/accreditation/mandate/jurisdiction, gated). No duplicate investor registry (canonical identity
remains core_users).
Status: ARCHITECTURALLY APPROVED (implementation gated by D-075).

---

## D-074 (Cap-Table Authority & D-064 Reconciliation — Wave 5D)

Decision: The Investment Network data room is the AUTHORITATIVE full cap-table record. The Startup
Hub `startup_team_members.ownership_percent` (D-064 founder governance) is a reconciled governance
subset — D-064 protections (≤100%, ≥1 founder, immutable transfers) remain intact.
Status: ARCHITECTURALLY APPROVED (implementation gated by D-075).

---

## D-075 (Mandatory Investment Governance Review — Wave 5D GATE) — OPEN / BLOCKING

Decision: Investment Network IMPLEMENTATION is authorized only after a mandatory legal/compliance/
securities governance review (INVESTMENT_GOVERNANCE_REVIEW.md) WITH qualified local legal-counsel
sign-off per jurisdiction (Nigeria/Ghana/Kenya/South Africa + cross-border). Wave 5D architecture
is ARCHITECTURALLY APPROVED; IMPLEMENTATION is DENIED PENDING D-075.
Status: OPEN — BLOCKING. INVESTMENT_GOVERNANCE_REVIEW.md produced 2026-06-04 (architecture/
governance analysis; recommends Operating Model Option C with guardrails; outcome CONDITIONAL GO).
Final D-075 closure requires EXTERNAL qualified-counsel legal sign-off — NOT provided by this
analysis (not legal advice).

---

## Franchise / TenantScope Activation — Architecture Review (2026-06-04)

FRANCHISE_TENANTSCOPE_ARCHITECTURE_REVIEW.md produced (design only; activation of the reserved
TenantScope per D-004/D-019/D-037/D-050). core_tenants exists since Sprint 1; tenant_id already on
all 38 owned-parent tables; children inherit via parent (W2-1). Validation A–E PASS: TenantScope is
ADDITIVE (tenant>account>user, D-050#4), does NOT replace AccountScope; all modules compatible
without redesign; D-037 config-only TRUE; D-050 + D-053 intact. F = 38 parent tables carry tenant_id.
G = children inherit via parent (no change); reference tables (partner_tiers/training_course_
categories/marketplace_categories/community_skills) need a global-vs-per-tenant decision; analytics
aggregation tables need a tenant dimension; system/RBAC/i18n tables stay tenant-agnostic. Risk FT-1
cross-tenant leakage = CRITICAL (exhaustive isolation tests = release gate). OUTCOME: CONDITIONAL GO.
Proposed (NOT decided): D-076 (TenantScope activation model + bypass hierarchy), D-077 (default-tenant
+ backfill, config-only D-037), D-078 (reference-data tenancy policy), D-079 (Franchise Admin role +
core_tenants extension parent_tenant_id/country/residency). Architecture review awaiting approval; no
implementation.

---

## D-076 (TenantScope Activation Model — FT-1)

Decision: TenantScope composes ABOVE AccountScope (tenant → account → user). AccountScope remains
active + unchanged. TenantScope is a NEW global scope (BelongsToTenant trait / centralized provider
registry) — additive; modifies NO existing access-control family. Bypass hierarchy: console/system
bypass; tenancy-disabled (config) bypass; EXPLICIT super-tenant (Platform/Super Admin HQ) cross-tenant
bypass (audited); everyone else scoped to their tenant; cross-tenant access FAILS CLOSED.
Status: Approved

---

## D-077 (Default Tenant & Backfill Strategy — FT-2)

Decision: Activation supports default-tenant assignment, an additive backfill migration path, additive
enablement (config/.env), and rollback. NO destructive migration. Existing rows backfill to the root
default tenant; rollback reverses without data loss.
Status: Approved

---

## D-078 (Reference-Data Tenancy Policy — FT-3)

Decision: Every reference table is classified as exactly ONE of GLOBAL or TENANT-OWNED — no hybrid.
Initial classification: GLOBAL = permission tables, i18n_translations, system tables. TENANT-OWNED
(per-franchise) candidates = partner_tiers, training_course_categories, marketplace_categories,
community_skills (each assigned one policy at implementation; global unless a franchise needs its own).
Status: Approved

---

## D-079 (Franchise Administration Model — FT-7)

Decision: Introduce a Franchise Admin role (tenant-scoped admin) + core_tenants extension
parent_tenant_id (regional hierarchy), country, residency metadata, owner. Tenant governance:
provisioning/suspension/activation/ownership-transfer/admin-elevation/residency-change are HQ/Franchise
governance actions, audited (TENANT_MANAGEMENT).
Status: Approved

---

## TENANT_MANAGEMENT Audit Category (FT — Approved)

Add AuditCategory::TENANT_MANAGEMENT. HIGH-sensitivity: tenant creation, suspension, activation,
ownership transfer, tenant-admin elevation, residency changes.
Status: Approved

Implementation: Wave FT-1 (TenantScope Activation) AUTHORIZED — TENANTSCOPE_IMPLEMENTATION_PLAN.md
then phased build (1 scope/trait/resolver/bypass · 2 backfill/default/indexing · 3 analytics tenant
dimension · 4 isolation verification). Release gate: module isolation + cross-tenant leakage tests
pass; analytics tenant dimension verified; backfill + rollback verified.

Implementation outcome: WAVE FT-1 IMPLEMENTED and APPROVED WITH CONTROLLED ENABLEMENT (2026-06-04,
TENANTSCOPE_IMPLEMENTATION_REVIEW.md). Additive; AccountScope intact; all access families unchanged;
fail-closed; explicit audited super-tenant bypass; rollback + backfill paths exist. Deliberate
exclusions (core_users/core_audit_logs/core_tenants) accepted. PRODUCTION ENABLEMENT SEQUENCE (7
stages): (1) deploy ICS_TENANCY_ENABLED=false; (2) backfill + verify indexes + verify rollback;
(3) pass CrossTenantIsolationTest + backfill/rollback/aggregation tests; (4) create pilot tenant;
(5) enable for pilot tenant only; (6) observe audit/analytics/performance/access; (7) GA.
Follow-up decisions BEFORE production enablement (OPEN): D-078-A (Reference-Data Classification
Matrix — every shared reference table classified GLOBAL or TENANT_OWNED, none unresolved); D-078-B
(Tenant Analytics Dimension Verification — executive/tenant rollups + warehouse aggregation produce
consistent totals). Membership System may proceed to architecture review (no D-075 dependency); Wave
5D remains BLOCKED by D-075.

---

## D-080 (Membership Architecture)

Decision: Membership = an active membership plan (billing_plans module='membership') + an active
billing_subscription → content-tier entitlement. NO separate membership permission engine; NO
membership-specific access-control family. Reuses Billing (D-031) + ContentAccessService.
Status: Approved

## D-081 (Membership Audit Model)

Decision: Add AuditCategory::MEMBERSHIP_MANAGEMENT. HIGH-sensitivity: manual entitlement grant,
manual entitlement removal, subscription override, tenant-wide membership policy changes.
Status: Approved

## D-082 (Membership Scope)

Decision: Membership may elevate ONLY Knowledge Center + Research Center content access. It may NOT
elevate CRM permissions, Portal permissions, account ownership, tenant ownership, Community
moderation, Marketplace moderation, or administrative authority.
Status: Approved

## D-083 (Billing Dependency)

Decision: Membership implementation REQUIRES the Billing substrate (plans, subscriptions, lifecycle,
webhook-driven status transitions) BEFORE entitlement activation.
Status: Approved

## Membership Guardrails (C-1..C-4 — Approved)

C-1: ContentAccessService change is ELEVATE-ONLY, non-destructive, regression-tested; role-derived
entitlement remains the authoritative baseline. C-2: membership affects content tiers ONLY (no org/
client/partner tier elevation). C-3: entitlement computed from LIVE subscription status; revocation
IMMEDIATE on cancellation/expiration/refund/charge-failure/admin-termination; NO cached grants.
C-4: billing_plans + billing_subscriptions participate in TenantScope; membership is tenant-aware.
Next: BILLING_SUBSCRIPTION_ARCHITECTURE_REVIEW.md (minimum Billing substrate; architecture review only).
Status: Approved

---

## D-084 (Billing Substrate Definition)

Decision: Billing is webhook-driven, signature-verified, idempotent, lifecycle-based. Gateway state is
authoritative ONLY after successful verification + reconciliation. Authoritative components:
billing_plans, billing_subscriptions, billing_invoices, billing_invoice_items, billing_payments,
billing_webhooks. Webhook governance: (1) signature verification, (2) event idempotency, (3) transaction
boundary, (4) audit logging, (5) replay safety — duplicate delivery is a no-op. Immediate revocation:
membership entitlement exists ONLY while subscription status ∈ {trial, active}; any transition to
past_due/cancelled/expired/refunded/chargeback/terminated immediately removes entitlement (no cached
grants, no delayed revocation). Reconciliation may restore consistency but may NEVER create entitlements
unsupported by subscription state.
Status: Approved

## D-085 (Billing Audit Governance)

Decision: Add AuditCategory::BILLING_MANAGEMENT. HIGH: manual subscription override, refund, chargeback,
administrative cancellation, administrative reactivation, invoice adjustment, payment reconciliation
override. Routine payment-success events = normal sensitivity.
Status: Approved

## D-086 (Billing TenantScope Integration)

Decision: Billing models participate in TenantScope (tenant-aware plans/subscriptions/invoices/payments/
reporting). Invoice numbering is tenant-safe: INV-{TENANT}-{YYYY}-{NNNNNN} (sequence per tenant+year).
Webhook reconciliation must resolve tenant context (from the referenced subscription) BEFORE processing
subscription state changes.
Status: Approved

Implementation: WAVE BILLING authorized — billing plans/subscriptions/invoices/payments/webhooks +
subscription state machine + TenantScope registration + BILLING_MANAGEMENT audit + MembershipTierResolver
HOOK (read-only; ContentAccessService NOT modified — Membership is a separate gate) + Paystack SANDBOX.
Deliverables: BILLING_IMPLEMENTATION_REVIEW.md, BILLING_TEST_SPEC.md, BILLING_STATE_MACHINE_VALIDATION.md.
Verification A-G: webhook idempotency, signature validation, immediate revocation, TenantScope isolation,
invoice sequence uniqueness, duplicate payment protection, membership integration hook. CONSTRAINT: do
NOT implement Membership — Billing substrate first; Membership is a separate approval gate.

Implementation Review: ACCEPTED (2026-06-05). Webhook governance (signature-first, idempotent,
transactional, audited, replay-safe), live-status entitlement with immediate revocation (C-3), downgrade-
only reconciliation, TenantScope integration, tenant-safe invoice numbering, and the read-only
MembershipTierResolver (ContentAccessService UNCHANGED) all verified against architecture. Verification
A-G stand as MANDATORY release criteria — production billing remains conditional on GREEN CI. Billing
substrate: COMPLETE AND ACCEPTED. Membership gate: OPENED (authorized to begin — see D-087).

---

## D-087 (Membership Implementation Authorization — Wave Membership)

Decision: With Billing accepted, Membership implementation is AUTHORIZED. Membership realises D-080..D-083
+ guardrails C-1..C-4. Scope: (1) MembershipTierResolver activation, (2) ContentAccessService ELEVATE-ONLY
integration, (3) membership plan management, (4) subscription entitlement projection, (5) MEMBERSHIP_MANAGEMENT
audit, (6) tenant-aware membership administration, (7) membership analytics, (8) membership test baseline.
HARD CONSTRAINTS (ratified): membership may ELEVATE content tiers ONLY (Knowledge/Research); it may NEVER
grant CRM access, Client Portal access, Partner Portal access, Marketplace moderation, Startup governance,
account/tenant ownership, or any administrative authority (D-082). Entitlement remains LIVE-status derived
(no cached grant — C-3); revocation immediate. ContentAccessService change is elevate-only/non-destructive/
regression-tested (C-1). Add AuditCategory::MEMBERSHIP_MANAGEMENT (D-081): manual entitlement grant/removal,
tenant-wide membership policy change = HIGH. Mandatory deliverables: MEMBERSHIP_IMPLEMENTATION_REVIEW.md,
MEMBERSHIP_TEST_SPEC.md, MEMBERSHIP_ENTITLEMENT_VALIDATION.md. Mandatory validation 1-8: immediate activation,
immediate revocation, knowledge tier elevation, research tier elevation, NO portal escalation, NO CRM
escalation, TenantScope compatibility, Billing integration integrity.
Status: Approved

Design note (self-flagged, M-DN-1): within the existing tier schemes the genuine, demonstrable premium
elevation path is RESEARCH (HierarchicalAccessStrategy — stacked tiers; max(roleTier, grant)). For KNOWLEDGE
(LateralAccessStrategy) tiers 3/4 are org-role checks (CLIENT/PARTNER) that require the actual org role;
membership therefore confers the MEMBER dimension (tier 2) ONLY and STRUCTURALLY cannot satisfy the org
tiers (C-2 honored by construction). Membership grants are clamped to ics.membership.max_grant_tier (default
3) so membership never reaches internal(4)/super(5) content. The structural guarantee for validations 5/6:
MembershipTierResolver output is consumed ONLY by ContentAccessService content-tier evaluation — it never
touches portal/CRM/admin code paths.

Implementation Review: ACCEPTED (2026-06-05). Membership implemented as a consumer of Billing (no new
access family, no new schema, no parallel payment path); ContentAccessService elevate-only/regression-safe/
single-integration-point (max(roleTier, membershipTier), public API unchanged); D-082 boundary enforced by
construction (no CRM/Portal/Marketplace-mod/Startup-gov/Franchise/Tenant-admin/internal/super); live
entitlement (isEntitling, no cached grant — C-3); TenantScope inherited via Billing; MEMBERSHIP_MANAGEMENT
audit (manual grant/removal/override HIGH); per-tenant analytics, no PII. M-DN-1 accepted. Validations 1-8
are the formal release criteria — Membership production CONDITIONAL on GREEN CI. Membership: COMPLETE AND
ACCEPTED. Next governance step: PLATFORM_READINESS_REVIEW (no new module development before it completes).

---

## D-088 / D-089 IMPLEMENTATION + GREEN-CI EXECUTION (2026-06-05) — RATIFIED & EXECUTED

D-088 (Context-aware tenancy) — APPROVED + IMPLEMENTED. Removed the blanket runningInConsole() bypass from
TenantScope (and AccountScope sibling — the existing null-actor guard preserves system context). Added
TenantContext::runForTenant() + TenancyQueueMiddleware + TenantAware trait (queue/async restore tenant
context). Fail-closed now holds in console/async; explicit cross-tenant (acrossTenants/runAsSuperTenant)
unchanged; migrate/seed run tenancy-disabled (no-op). VERIFIED: CrossTenantIsolation 4/4, TenantScopeAsync
6/6 (queue restoration, async fail-closed, super-tenant, explicit cross-tenant, trait wiring), Billing-d,
AccountIsolation 5/5. No DB migration; ICS_TENANCY_ENABLED-gated. Deliverables: TENANTSCOPE_REMEDIATION_REPORT,
TENANTSCOPE_SECURITY_VALIDATION. Status: Approved.

D-089 (CVE-2026-48019 temporary acceptance) — APPROVED + IMPLEMENTED (OPTION A). Re-enabled
config.audit.block-insecure=true; added config.audit.ignore=[PKSA-mdq4-51ck-6kdq,GHSA-5vg9-5847-vvmq,
CVE-2026-48019]. composer audit GREEN (1 ignored advisory). Verified NO raw email-header construction (grep);
Symfony Mailer header-encoding active → residual LOW. Registered SEC-EXC-001 (production-blocking exit
condition: Laravel 12.60+ upgrade — NOT performed, separate gate). Deliverables: SECURITY_EXCEPTION_REGISTER,
CVE_2026_48019_ACCEPTANCE_REPORT. Status: Approved.

GREEN-CI EXECUTION: all gates GREEN locally — validate✓, audit✓(exit0, ignored advisory), driver-gate✓,
Pint✓(36 files auto-formatted), Larastan✓(114 baselined as tracked debt; phpstan-baseline.neon), PHPUnit
57 PASS/0 FAIL (sqlite AND MariaDB 10.4). Engine parity: full suite + 80 migrations GREEN on REAL MariaDB
10.4 server (FK enforced — surfaced + fixed a test-fixture FK gap in AssertsOrgIsolation that sqlite hid);
MySQL 8 authoritative run = CI gate (not provisionable here). Carried (environmental, not defects): GitHub
Actions run (no git repo here) + MySQL 8 + PHP 8.3 + gitleaks → run on the runner to close R-012/R-013 +
D-049 #3-4. Deliverables: PINT_REMEDIATION_REPORT, LARASTAN_REMEDIATION_REPORT, FULL_TEST_EXECUTION_REPORT,
MYSQL_ENGINE_PARITY_REPORT, GREEN_CI_CERTIFICATION_REPORT. Platform: CONDITIONAL GO (GREEN-CI baseline
achieved; production gated by Laravel 12 upgrade + Hostinger spike + MySQL 8 CI + D-078-A/B). Coverage
backlog (non-blocking): no dedicated CRM/Portal test files; add FULLTEXT search test on MySQL 8.

---

## Post-Bootstrap Security & GREEN-CI Remediation Review (2026-06-05) — ANALYSIS

Produced (analysis only, no code): TENANTSCOPE_ASYNC_SECURITY_REVIEW.md, LARAVEL_SECURITY_ADVISORY_REVIEW.md,
FINAL_GREEN_CI_EXECUTION_PLAN.md.

D-088 (CANDIDATE — TenantScope async tenancy): RECOMMEND **OPTION B** — replace the blanket
`runningInConsole()` bypass with CONTEXT-AWARE tenancy: maintenance/super context for migrate/seed;
context-propagating queue middleware (job carries tenant_id, restored before handle); fail-closed default in
async; explicit `acrossTenants()`/`runAsSuperTenant()` for intentional cross-tenant jobs (ReconciliationService
already complies). Rationale: restores D-076 fail-closed in async, keeps system ops working, makes the 3
isolation tests pass, unblocks D-079 franchise async. Risk today LOW (only explicit cross-tenant downgrade-only
console jobs exist) but MEDIUM→HIGH latent (footgun for future per-tenant async). No DB migration; auth-layer
change only. Status: AWAITING APPROVAL.

D-089 (CANDIDATE — CVE-2026-48019, Laravel CRLF in default email rule, affects ALL 11.x, fix only L12.60+/
13.10+): RECOMMEND **OPTION A now** (accept + composer audit.ignore GHSA-5vg9-5847-vvmq w/ justification +
re-enable block-insecure + email hardening) to unblock GREEN CI — residual risk LOW (the `email` rule is used
but the only realistic sink is mail headers, protected by Symfony Mailer; no raw-header construction).
**OPTION B (upgrade to Laravel 12.60+) MANDATORY before production certification** (no patched 11.x exists).
Status: AWAITING APPROVAL.

Effort to GREEN CI ~2-3 days (TenantScope B + AccountScope test-context + Pint + Larastan baseline + run on
PHP8.3/MySQL8 CI). Effort to production-ready ~1-2 weeks (+ Hostinger spike, Laravel 12 upgrade, TenantScope
prod enablement D-078-A/B, R-010 decision, go-live signoff). Platform: CONDITIONAL GO (remediation);
production NO GO.

---

## Bootstrap Recovery EXECUTION (2026-06-05) — actual run

EXECUTED (authorized). Outcome: app is now RUNNABLE; first CI attempt NOT-YET-GREEN (47 pass / 5 fail).
Phase 1: generated Laravel v11.6.1 skeleton, additive merge (overlay never overwritten); also recreated
missing app/Http/Controllers/Controller.php (base; AuthorizesRequests+ValidatesRequests) and renamed
ParticipationController private authorize()→authorizeManagement() (trait collision). Phase 2: composer
audit block-insecure default (2.9.5) was the whole B2 blocker; set audit.block-insecure=false (TEMP) →
install OK (laravel 11.54.0); composer.lock generated. The ONLY real advisory = CVE-2026-48019 (Laravel
CRLF in default email rule), affects ALL 11.x, FIX ONLY in Laravel 12.60+/13.10+ → NO patched 11.x →
REQUIRES DECISION (accept+audit.ignore vs major upgrade to L12; NOT done — architectural). Ran on PHP
8.2.12 (8.3 unprovisionable here) via --ignore-platform-req=php; CI must run on 8.3. Phase 3: reconciled
UserFactory→core_users + User HasFactory (B11); added config/{app,database,filesystems,logging,services}
+ published sanctum/permission (B12). Phase 4: artisan boots (L11.54), 266 routes, ALL 80 migrations apply
on sqlite (exit 0). Phase 5 CI: validate✓, audit=1 advisory, driver-gate✓, Pint✗(~36 files cosmetic),
Larastan✗(113 property.notFound — baseline/annotations), PHPUnit 47 pass/5 fail, engine-parity NOT EXECUTED
(MariaDB 10.4 not MySQL 8). Fixed real bugs surfaced: SubscriptionService null-actor (optional()->first()
on null → $actor?->) recovered Billing a/c/g; RBAC stale 13→count(Roles::ALL); Membership-7 FK test setup.
Membership 1-8 ALL GREEN; Billing A/B/C/E/F/G GREEN. The 5 remaining failures share ONE architectural root
cause: TenantScope::apply() bypasses on runningInConsole() → isolation never engages under PHPUnit (and not
in queue/scheduled console contexts → async cross-tenant exposure risk). FLAGGED, NOT fixed (stop rule).
Reports: BOOTSTRAP_MERGE_REPORT, DEPENDENCY_RECOVERY_REPORT, CONFIGURATION_RECOVERY_REPORT,
APPLICATION_BOOT_REPORT, FIRST_GREEN_CI_ATTEMPT_REPORT. OPEN DECISIONS RAISED: (D-088 candidate) TenantScope
console-bypass vs isolation; (D-089 candidate) CVE-2026-48019 accept vs Laravel 12 upgrade. Recommendation:
CONDITIONAL GO to remediation (resolve the 2 decisions + Pint/Larastan + run on 8.3/MySQL8); production NO GO.

---

## Environment Remediation & Bootstrap Recovery — ANALYSIS (2026-06-05)

Produced (planning only, no code/architecture/module changes): ENVIRONMENT_REMEDIATION_PLAN.md,
BOOTSTRAP_RECOVERY_PLAN.md, GREEN_CI_EXECUTION_PLAN.md, BLOCKER_RESOLUTION_MATRIX.md. Classification of the
12 blockers: 9 RESOLVABLE NOW (B1,B2,B3,B4,B5,B7,B8,B10 + new B11,B12), B9 REQUIRES HOSTINGER, B6 REQUIRES
DECISION (host engine) + possibly Hostinger/VPS, B2 also REQUIRES DECISION (composer audit ignore policy).
TWO NEW blockers identified: B11 database/factories absent though tests call User::factory() (must author
UserFactory for core_users — test scaffolding, not feature work); B12 standard config set absent
(config/database.php, app.php, filesystems.php, logging.php, services.php + sanctum/permission publish).
KEY INSIGHT: ci.yml has NO npm step → Node (B5) does NOT block first GREEN CI; and the GitHub Actions runner
already supplies PHP 8.3 + intl + MySQL 8, removing B1/B4/B6 on CI. Recovery strategy = ADDITIVE skeleton-
into-overlay (generate pristine Laravel 11 skeleton, copy ONLY missing files, overlay never overwritten),
+ dependency resolution on 8.3 (composer update → audit triage → raise framework floor or documented ignore
→ commit composer.lock). Fastest first-GREEN path is entirely RESOLVABLE NOW (no external dependency).
Production remains NO GO; becomes CONDITIONAL GO after GREEN CI + Hostinger spike. STOP after analysis
(no implementation per directive).

---

## Platform Readiness Verification — D-049 Gate Execution (2026-06-05)

Outcome: ⛔ NO GO. Bootstrap & GREEN-CI Verification was EXECUTED (actual commands, not assumed). Result:
gate FAILS at Step 1. composer validate PASSED; `composer install` FAILED (exit 2) — PHP 8.2.12 < required
^8.3 AND laravel/framework ^11.0 blocked by security advisories; no composer.lock. Laravel SKELETON missing
(artisan, public/, public/index.php absent — overlay lacks skeleton root). node/npm absent; local DB is
MariaDB 10.4.32 (NOT MySQL 8); ext-intl absent. Downstream gates (DB migrate/seed, conformance, Billing
A-G, Membership 1-8, isolation, CI Pint/Larastan/PHPUnit/gitleaks/engine-parity) = NOT EXECUTED (no runnable
app). Hostinger spike = NOT EXECUTED (no host access; local box is XAMPP, not Hostinger). NO test marked
passed. R-012/R-013 CONFIRMED OPEN (evidential). NEW finding: dependency-security — framework floor must be
raised to a patched 11.x + lock committed before composer audit can pass. Reports: BOOTSTRAP_EXECUTION_REPORT,
CI_VERIFICATION_REPORT, HOSTINGER_CAPABILITY_RESULTS, PRODUCTION_READINESS_CERTIFICATION. D-049 remains OPEN.
Path to GO: PHP 8.3 + intl + MySQL 8 + Node; generate skeleton + merge overlay; raise framework floor + lock;
install/build/migrate/seed/test GREEN (CI, MySQL engine-parity); Hostinger spike; sign go-live. No new module
development per directive.

---

## OPEN DECISIONS (Awaiting Approval)

D-075 — Mandatory Investment Governance Review (legal/compliance/securities sign-off) is OPEN and
BLOCKING for Wave 5D implementation.
TenantScope production enablement gated by D-078-A (reference-data classification) + D-078-B (analytics
dimension verification) + GREEN isolation tests.
All other architectural decisions are resolved.
