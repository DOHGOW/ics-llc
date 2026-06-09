# ENTERPRISE ARCHITECTURE BLUEPRINT
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-29
Status: Approved — Governing Technical Reference
Author: Chief Enterprise Architect

---

## 1. PLATFORM OVERVIEW

The ICS Enterprise Ecosystem Platform is a Modular Monolith web application
built on Laravel 11 + PHP 8.3 + MySQL 8+. It serves as the digital operating
infrastructure for ICS — a Technology, Consulting, Capacity Development, and
Innovation Organization targeting Government Agencies, International Organizations,
and Corporate Enterprises across Africa.

The platform consolidates 12 discrete business modules under a single codebase,
shared authentication system, unified analytics layer, and common notification
infrastructure. All modules share a single MySQL database with strict table
prefixing and tenant-aware schema, enabling future microservices extraction and
multi-tenancy activation without schema rewrites.

---

## 2. FINALIZED TECHNOLOGY STACK

| Layer | Technology | Version | Purpose |
|---|---|---|---|
| Framework | Laravel | 11.x | Backend, routing, ORM, events, queue |
| Language | PHP | 8.3 | Server-side processing |
| Database | MySQL | 8.0+ | Primary data store |
| Templating | Laravel Blade | (Laravel 11) | Server-side HTML rendering |
| CSS | Tailwind CSS | 3.x | Utility-first styling |
| JS (UI) | Alpine.js | 3.x | Reactive UI components |
| JS (Custom) | Vanilla JS | ES2022+ | Custom interactions, PWA |
| API Auth | Laravel Sanctum | (Laravel 11) | Stateless bearer token auth |
| RBAC | Spatie Laravel-Permission | 6.x | Roles and fine-grained permissions |
| Storage | Laravel Flysystem | (Laravel 11) | Abstracted file storage |
| Notifications | Laravel Notifications | (Laravel 11) | Multi-channel notification system |
| Queue (P1) | Laravel Scheduler + MySQL | (Laravel 11) | Cron-based job processing |
| Queue (P2) | Laravel Horizon + Redis | (Laravel 11) | Async queue workers on VPS |
| Email | Brevo | API/SMTP | Transactional + marketing email |
| Messaging | WhatsApp Business API | Cloud API | WhatsApp notifications |
| AI | Google Gemini API | v1 | AI-powered features |
| Analytics UI | Chart.js | 4.x | Dashboard data visualization |
| Mobile | PWA (Service Worker) | - | Offline, push, installable |
| Hosting (P1) | Hostinger Shared | - | Initial deployment |
| Hosting (P2) | Hostinger VPS | - | Scaling deployment |
| Hosting (P3) | Cloud Infrastructure | - | Enterprise deployment |
| Deployment | Git-based | - | Staging + Production |

---

## 3. LARAVEL APPLICATION ARCHITECTURE

### 3.1 Directory Structure

```
/ics-platform
├── /app
│   ├── /Console
│   │   └── /Commands              — Scheduled commands (cron tasks)
│   ├── /Events                    — Domain events (one file per event)
│   ├── /Exceptions                — Custom exception handlers
│   ├── /Http
│   │   ├── /Controllers
│   │   │   ├── /API
│   │   │   │   └── /V1            — API controllers by module
│   │   │   │       ├── /Auth
│   │   │   │       ├── /CRM
│   │   │   │       ├── /Training
│   │   │   │       ├── /Marketplace
│   │   │   │       ├── /Partner
│   │   │   │       ├── /Startup
│   │   │   │       ├── /Client
│   │   │   │       ├── /Content
│   │   │   │       ├── /Analytics
│   │   │   │       ├── /AI
│   │   │   │       ├── /Notifications
│   │   │   │       └── /Admin
│   │   │   └── /Web               — Blade view controllers
│   │   │       ├── /Admin
│   │   │       ├── /Client
│   │   │       ├── /Partner
│   │   │       ├── /Startup
│   │   │       ├── /Training
│   │   │       └── /Public
│   │   ├── /Middleware             — Auth, RBAC, i18n, CSRF, Rate Limit
│   │   ├── /Requests               — Form request validation classes
│   │   └── /Resources              — API Resource transformers
│   │       ├── /CRM
│   │       ├── /Training
│   │       └── /...
│   ├── /Listeners                  — Event listener classes
│   ├── /Models
│   │   ├── /Core                  — User, Tenant, AuditLog, Notification
│   │   ├── /CRM                   — Lead, Contact, Account, Opportunity, Contract
│   │   ├── /Training              — Course, Lesson, Enrollment, Certificate
│   │   ├── /Marketplace           — Listing, Category, Application
│   │   ├── /Partner               — Partner, Tier, Referral, Agreement
│   │   ├── /Startup               — Startup, Milestone, Mentor
│   │   ├── /Client                — Project, Deliverable, Invoice
│   │   ├── /Content               — Page, Article, Media
│   │   ├── /Knowledge             — Article, Category, Tag, Bookmark, Rating, View
│   │   ├── /Community             — Profile, FounderProfile, StartupProfile,
│   │   │                            ConsultantProfile, TrainerProfile,
│   │   │                            PartnerProfile, ResearcherProfile, Skill
│   │   ├── /Research              — Publication, Category, Author, Download, Citation
│   │   ├── /Analytics             — Snapshot, Report, Metric
│   │   └── /AI                    — AIRequest, AIResponse
│   ├── /Notifications             — Notification classes (multi-channel)
│   ├── /Policies                  — Resource authorization policies
│   └── /Services
│       ├── /AI                    — GeminiService
│       ├── /Analytics             — AnalyticsService, ReportService
│       ├── /Auth                  — AuthService
│       ├── /CRM                   — LeadService, ContractService
│       ├── /Notifications         — NotificationService
│       ├── /Storage               — StorageService
│       └── /Training              — EnrollmentService, CertificateService
│
├── /config                        — Laravel + custom configuration files
├── /database
│   ├── /migrations                — Named: YYYY_MM_DD_{module}_{table}.php
│   ├── /seeders                   — Module-organized seeders
│   └── /factories                 — Test factories
│
├── /resources
│   ├── /views                     — Blade templates
│   │   ├── /layouts               — Base layouts (app, admin, public, auth)
│   │   ├── /components            — Shared Blade components
│   │   └── /modules               — Module-specific views
│   ├── /lang
│   │   ├── /en                    — English (Phase 1)
│   │   ├── /fr                    — French (Phase 2)
│   │   └── /ar                    — Arabic/RTL (Phase 3)
│   ├── /css                       — Tailwind source
│   └── /js                        — Alpine.js, Vanilla JS, Service Worker
│
├── /routes
│   ├── api.php                    — All /api/v1/ routes
│   └── web.php                    — All web/Blade routes
│
├── /public
│   ├── /storage                   — Symlink → storage/app/public/
│   ├── manifest.json              — PWA manifest
│   └── sw.js                      — Service worker
│
├── /storage
│   ├── /app
│   │   ├── /public                — Public uploaded files (via symlink)
│   │   └── /private               — Private files (auth-gated delivery)
│   └── /logs                      — Application logs
│
├── .env                           — Environment config (NOT in git)
├── .env.example                   — Template (in git)
└── .gitignore                     — Excludes .env, /storage, /vendor
```

### 3.2 Module Organization Principle

Each module owns:
- Its own Controller namespace (/API/V1/{Module}/, /Web/{Module}/)
- Its own Model namespace (/Models/{Module}/)
- Its own database table prefix ({module}_)
- Its own Service classes (/Services/{Module}/)
- Its own Blade views (/views/modules/{module}/)
- Its own API Resource transformers (/Resources/{Module}/)
- Its own Form Requests (/Requests/{Module}/)

No module may directly reference another module's Model or table.
Cross-module operations go through a Service class or via dispatched Events.

### 3.3 Naming Conventions

| Entity | Convention | Example |
|---|---|---|
| Database tables | snake_case, module prefix | crm_leads, training_courses |
| Models | PascalCase | Lead, CourseEnrollment |
| Controllers | PascalCase + Controller | LeadController, CourseController |
| Services | PascalCase + Service | LeadService, GeminiService |
| Events | PascalCase, past tense | LeadCreated, CourseEnrolled |
| Listeners | PascalCase, present action | SendLeadAlert, IssueCertificate |
| Notifications | PascalCase + Notification | WelcomeNotification, CourseCompletedNotification |
| Migrations | date_module_description | 2026_06_01_crm_create_leads_table |
| Routes (API) | kebab-case, plural nouns | /api/v1/crm/leads |
| Routes (Web) | kebab-case | /admin/crm/leads |
| Blade views | kebab-case | crm/leads/index.blade.php |
| Config keys | snake_case | config('ics.ai.daily_budget') |
| .env keys | SCREAMING_SNAKE_CASE | GEMINI_API_KEY |

---

## 4. DATABASE ARCHITECTURE

### 4.1 MySQL 8+ Strategy

- Engine: InnoDB exclusively (ACID compliance, foreign key support)
- Charset: utf8mb4 (Unicode, emoji-safe, Arabic support)
- Collation: utf8mb4_unicode_ci
- Strict mode: ON (no silent data truncation)
- Timezone: UTC stored; display timezone per user preference

### 4.2 Table Naming Conventions (Module Prefixes)

| Prefix | Module | Example Tables |
|---|---|---|
| core_ | Platform Core | core_users, core_tenants, core_audit_logs |
| crm_ | CRM | crm_leads, crm_contacts, crm_accounts, crm_contracts |
| training_ | Training Institute | training_courses, training_enrollments, training_certificates |
| marketplace_ | Opportunity Marketplace | marketplace_listings, marketplace_applications |
| partner_ | Partner Portal | partner_profiles, partner_referrals, partner_agreements |
| startup_ | Startup Hub | startup_profiles, startup_milestones, startup_mentors |
| client_ | Client Portal | client_projects, client_deliverables, client_invoices |
| content_ | Corporate Website / CMS | content_pages, content_articles, content_media |
| knowledge_ | Knowledge Center | knowledge_articles, knowledge_categories, knowledge_tags |
| community_ | Community Module | community_profiles, community_{type}_profiles, community_skills |
| research_ | Research Center | research_publications, research_authors, research_downloads |
| analytics_ | Analytics Layer | analytics_snapshots, analytics_metrics |
| ai_ | AI Services | ai_requests, ai_usage_logs |
| notify_ | Notifications | notify_preferences (core notifications table = Laravel default) |
| i18n_ | Translations | i18n_translations |
| sys_ | System | sys_jobs, sys_cache, sys_sessions, sys_failed_jobs |

### 4.3 Universal Columns (Every Business Table)

Every business table must carry these columns:

```
id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
tenant_id     BIGINT UNSIGNED NULL    FK → core_tenants.id  (NULL = ICS-owned)
created_at    TIMESTAMP NULL DEFAULT NULL
updated_at    TIMESTAMP NULL DEFAULT NULL
deleted_at    TIMESTAMP NULL DEFAULT NULL  (soft delete — Laravel SoftDeletes)
```

Index rules:
- tenant_id is indexed on every table that carries it
- All foreign keys have a corresponding index
- All filter/search columns used in WHERE clauses are indexed
- Composite index on (tenant_id, deleted_at) for all scoped queries

### 4.4 Tenant-Aware Design

Phase 1: tenant_id is nullable. All records owned by ICS have tenant_id = NULL.
Phase 3+: Franchise and multi-tenant clients get their own tenant_id.
Application-level scope automatically applies tenant filter when tenant context is active.
Global scope: TenantScope added to all tenantable models.

```
core_tenants
  id
  name
  slug
  domain
  status           — active | suspended | trial
  settings         — JSON (branding, config overrides)
  created_at
  updated_at
  deleted_at
```

### 4.5 Translation Architecture (i18n Database Layer)

For dynamic content (CMS pages, course descriptions, listing titles, etc.):

```
i18n_translations
  id
  translatable_type   — Model class (e.g. App\Models\Content\Article)
  translatable_id     — Record ID
  locale              — en | fr | ar
  field               — Column being translated (e.g. title, body, excerpt)
  value               — TEXT — translated content
  created_at
  updated_at
  UNIQUE KEY (translatable_type, translatable_id, locale, field)
```

PHP UI strings: stored in /resources/lang/{locale}/*.php arrays.
Translation helper: __('module.key') throughout all Blade templates.

### 4.6 Audit Log Design (Append-Only)

```
core_audit_logs
  id
  tenant_id
  actor_id            — core_users.id (NULL = system action)
  actor_role          — role name at time of action
  action              — CREATE | UPDATE | DELETE | LOGIN | LOGOUT | EXPORT | ...
  module              — crm | training | marketplace | ...
  record_type         — Model class
  record_id
  before_hash         — SHA-256 of serialized before-state
  after_hash          — SHA-256 of serialized after-state
  ip_address
  user_agent
  created_at          — NO updated_at. NO deleted_at. Append-only.
```

MySQL trigger prevents UPDATE and DELETE on this table.
Application layer also enforces append-only via a write-only repository.

### 4.6b Knowledge Center Schema (D-033)

```
knowledge_articles          — all content types (articles, guides, white papers, etc.)
  id
  tenant_id
  category_id               FK → knowledge_categories.id
  type                      — article | news | guide | white_paper | template | toolkit |
                              sop | checklist | case_study | training_resource |
                              video | download | resource_collection |
                              client_doc | internal_kb
  title                     — translatable (i18n_translations)
  slug
  excerpt                   — translatable short summary (always public — SEO)
  body                      — translatable full content (access-gated by access_tier)
  featured_image_path
  file_path                 — for downloadable types (template, toolkit, SOP, etc.)
  file_size_kb
  video_embed_url           — for video type (YouTube/Vimeo embed, Phase 1)
  access_tier               TINYINT  — 1=public | 2=member | 3=client | 4=partner | 5=internal
                            — Tiers 3+4 are LATERAL (role-specific, not hierarchical)
                            — Evaluated by KnowledgeAccessService (D-036)
  status                    — draft | under_review | published | archived
                            — draft always overrides access_tier → Tier 5 only
  read_time_minutes         — calculated estimate
  view_count                — cached counter (real counts in knowledge_views)
  download_count            — cached counter (real counts in knowledge_downloads)
  average_rating            — DECIMAL(3,2) cached (real ratings in knowledge_ratings)
  bookmark_count            — cached counter
  published_at              TIMESTAMP NULL
  seo_title                 VARCHAR NULL
  seo_description           VARCHAR NULL
  created_by                FK → core_users.id
  created_at
  updated_at
  deleted_at

knowledge_categories
  id
  tenant_id
  name                      — translatable (maps to 15 approved categories)
  slug
  description               — translatable
  icon                      — icon identifier for UI
  parent_id                 FK → knowledge_categories.id (subcategories)
  sort_order
  article_count             — cached counter
  created_at
  updated_at

knowledge_tags
  id
  name
  slug
  created_at

knowledge_article_tags      — pivot
  article_id                FK → knowledge_articles.id
  tag_id                    FK → knowledge_tags.id
  PRIMARY KEY (article_id, tag_id)

knowledge_bookmarks
  id
  user_id                   FK → core_users.id
  article_id                FK → knowledge_articles.id
  created_at
  UNIQUE KEY (user_id, article_id)

knowledge_ratings
  id
  user_id                   FK → core_users.id
  article_id                FK → knowledge_articles.id
  rating                    TINYINT  (1–5)
  created_at
  updated_at
  UNIQUE KEY (user_id, article_id)

knowledge_views             — append-only event log
  id
  article_id                FK → knowledge_articles.id
  user_id                   FK → core_users.id NULL (NULL for guest views)
  session_id                VARCHAR (for guest deduplication)
  ip_address
  country_code
  referrer_url
  created_at

knowledge_downloads         — append-only event log
  id
  article_id                FK → knowledge_articles.id
  user_id                   FK → core_users.id NULL
  ip_address
  country_code
  created_at

knowledge_related           — curated + auto-generated related content
  id
  article_id                FK → knowledge_articles.id
  related_article_id        FK → knowledge_articles.id
  relation_type             — manual | auto_category | auto_tag | ai_suggested
  score                     DECIMAL(5,4) NULL (relevance score for ranked display)
  created_at
```

Content Creation Workflow:
  ICS Content Staff (or AI draft) → Draft → Review → Published → Analytics tracking

Training Resource cross-link:
  knowledge_articles WHERE type = training_resource may reference training_courses.id
  via knowledge_articles.metadata JSON field (loose coupling, Event-based sync)

KnowledgeAccessService — Lateral Tier Access Logic (D-036)

> RETIRED (D-051): this module-specific service is replaced by the unified
> App\Services\Content\ContentAccessService using LateralAccessStrategy. The lateral
> logic below is preserved verbatim inside that strategy. Kept here for reference.

```
App\Services\Knowledge\KnowledgeAccessService

canAccess(User|null $user, Article $article): bool
  — Draft override: if $article->status === 'draft', return isICSStaff($user)

  switch ($article->access_tier):
    case 1: return true                                          — public
    case 2: return $user !== null                               — authenticated
    case 3: return $user?->hasAnyRole([                        — clients
              'Client Admin', 'ICS Staff — CRM',
              'ICS Staff — Training', 'ICS Staff — Content',
              'Platform Admin', 'Platform Super Admin'
            ])
    case 4: return $user?->hasAnyRole([                        — partners
              'Partner Admin', 'Government Agency Rep',
              'ICS Staff — CRM', 'ICS Staff — Content',
              'Platform Admin', 'Platform Super Admin'
            ])
    case 5: return isICSStaff($user)                           — internal
  return false

isICSStaff(User|null $user): bool
  return $user?->hasAnyRole([
    'ICS Staff — CRM', 'ICS Staff — Training', 'ICS Staff — Content',
    'Platform Admin', 'Platform Super Admin'
  ])

canDownload(User|null $user, Article $article): bool
  return canAccess($user, $article) AND ($article->file_path !== null)
```

Tier Access Summary:

  | Role                    | T1 | T2 | T3 | T4 | T5 |
  |---|---|---|---|---|---|
  | Guest                   | ✓  |    |    |    |    |
  | Authenticated (base)    | ✓  | ✓  |    |    |    |
  | Client Admin            | ✓  | ✓  | ✓  |    |    |
  | Partner Admin           | ✓  | ✓  |    | ✓  |    |
  | Gov Agency Rep          | ✓  | ✓  |    | ✓  |    |
  | ICS Staff / Admin       | ✓  | ✓  | ✓  | ✓  | ✓  |
  | Super Admin             | ✓  | ✓  | ✓  | ✓  | ✓  |

Monetization Upgrade Path (Phase 2+):
  canAccess() extended to check billing_subscriptions WHERE module=knowledge
  billing plan specifies tier_grant (2, 3, or 4)
  Effective access = role access OR subscription access
  No schema change required — billing tables already exist (D-031)

---

### 4.6c Research Center Schema (D-030)

```
research_publications
  id
  tenant_id
  category_id           FK → research_categories.id
  content_group         — summary | brief | public_report | insight     (Tier 1 types)
                        | full_report | template | archive              (Tier 2 types)
                        | partner_research | collaborative | restricted  (Tier 3 types)
                        | draft | working_paper | internal | pipeline   (Tier 4 types)
  title                 — translatable (i18n_translations)
  slug
  abstract              — translatable short summary (always public — SEO)
  body                  — full content (access-gated by access_tier)
  file_path             — storage path to downloadable PDF
  file_size_kb
  doi                   — Digital Object Identifier (optional)
  publish_date          — DATE
  access_tier           TINYINT  — 1=public | 2=member | 3=partner | 4=internal | 5=admin
                        — Evaluated by ResearchAccessService (D-034)
  status                — draft | under_review | published | archived
  view_count            — cached counter
  download_count        — cached counter
  created_by            FK → core_users.id
  created_at
  updated_at
  deleted_at

research_categories
  id
  tenant_id
  name                  — translatable
  slug
  description           — translatable
  parent_id             FK → research_categories.id (for subcategories)
  sort_order
  created_at
  updated_at

research_authors
  id
  tenant_id
  user_id               FK → core_users.id (NULL for external authors)
  name
  title                 — e.g. "Senior Research Analyst"
  bio                   — translatable
  avatar_path
  email                 — for external authors
  organisation          — for external authors
  created_at
  updated_at

research_publication_authors  — pivot: many publications ↔ many authors
  publication_id        FK → research_publications.id
  author_id             FK → research_authors.id
  author_order          — display order
  PRIMARY KEY (publication_id, author_id)

research_downloads
  id
  publication_id        FK → research_publications.id
  user_id               FK → core_users.id (NULL for public downloads)
  ip_address
  country_code          — derived from IP for geographic analytics
  user_agent
  created_at
  (append-only — no update/delete)

research_citations
  id
  publication_id        FK → research_publications.id
  cited_by_type         — research_publication | external_url | manual_entry
  cited_by_id           — record ID if internal
  cited_by_url          — URL if external
  cited_by_title        — free text if manual
  created_at
```

Citation auto-generation formats (PHP helper):
  - APA:     Author, A. A. (Year). Title. ICS Research Center. https://...
  - Chicago: Author. "Title." ICS Research Center, Year. https://...
  - IEEE:    A. Author, "Title," ICS Research Center, Year. [Online]. https://...

ResearchAccessService — Access Tier Logic (D-034)

> RETIRED (D-051): replaced by the unified App\Services\Content\ContentAccessService
> using HierarchicalAccessStrategy. The hierarchical logic below is preserved inside
> that strategy. Kept here for reference.

```
App\Services\Research\ResearchAccessService

getUserResearchTier(User|null $user): int
  Null (guest)                      → return 1
  Any authenticated user            → base = 2
  User has Partner Admin role       → base = 3
  User has any ICS Staff role       → base = 4
  User has Platform Admin role      → base = 4
  User has Platform Super Admin     → return 5 (ceiling, no subscription check)
  Check active research subscription (Phase 2 monetization):
    billing_subscriptions WHERE user_id = $user->id
      AND plan.module = research
      AND status = active
    If found → subscription_tier = plan.research_tier_grant
  return MAX(base, subscription_tier ?? 0)

canAccess(User|null $user, Publication $publication): bool
  return getUserResearchTier($user) >= $publication->access_tier

canDownload(User|null $user, Publication $publication): bool
  return canAccess($user, $publication)
    AND ($publication->file_path !== null)
```

Role-to-Tier Reference Map:

  | User State                    | Effective Tier | Content Access           |
  |---|---|---|
  | Guest (unauthenticated)       | 1              | Summaries, Briefs        |
  | Any authenticated user        | 2              | Full Reports, Archives   |
  | Partner Admin role            | 3              | Partner + Tier 1–2       |
  | ICS Staff / Platform Admin    | 4              | Internal + Tier 1–3      |
  | Platform Super Admin          | 5              | Everything               |
  | Future: Research Subscriber   | 2 or 3         | Paid tier (D-031 plan)   |

Monetization Upgrade Path (Phase 2+):
  1. Create billing_plans entry: module=research, research_tier_grant=2 (or 3)
  2. User subscribes via Paystack → billing_subscriptions created
  3. ResearchAccessService reads subscription tier automatically
  4. Zero schema changes — all tables already exist (D-031 + D-034)

---

### 4.7 System Tables (Queue, Cache, Sessions)

```
sys_jobs            — Laravel queue jobs table (Phase 1 cron processing)
sys_failed_jobs     — Failed job archive
sys_cache           — Laravel cache (MySQL driver Phase 1)
sys_sessions        — PHP session storage (MySQL driver)
```

---

## 5. API ARCHITECTURE

### 5.1 Design Principles

- RESTful resource-based endpoints
- Versioned from day one (/api/v1/)
- Stateless — bearer token authentication only
- Consistent request and response shape
- No business logic in routes — all logic in Service classes

### 5.2 URL Structure

```
Base:    https://platform.ics.org/api/v1/

Format:  /api/v1/{module}/{resource}/{id?}/{sub-resource?}

Examples:
  GET     /api/v1/crm/leads
  POST    /api/v1/crm/leads
  GET     /api/v1/crm/leads/{id}
  PUT     /api/v1/crm/leads/{id}
  DELETE  /api/v1/crm/leads/{id}
  GET     /api/v1/training/courses/{id}/lessons
  POST    /api/v1/training/courses/{id}/enroll
  GET     /api/v1/analytics/executive-dashboard
```

### 5.3 Module API Route Prefixes

| Module | Prefix |
|---|---|
| Authentication | /api/v1/auth/ |
| CRM | /api/v1/crm/ |
| Training | /api/v1/training/ |
| Marketplace | /api/v1/marketplace/ |
| Partner Portal | /api/v1/partners/ |
| Startup Hub | /api/v1/startups/ |
| Client Portal | /api/v1/clients/ |
| Content / CMS | /api/v1/content/ |
| Analytics | /api/v1/analytics/ |
| AI Services | /api/v1/ai/ |
| Notifications | /api/v1/notifications/ |
| Admin | /api/v1/admin/ |
| User Profile | /api/v1/profile/ |

### 5.4 Standard Response Envelopes

Success:
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 25,
      "total": 142,
      "last_page": 6
    }
  }
}
```

Error:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."]
    }
  }
}
```

HTTP status codes:
- 200 OK — success
- 201 Created — resource created
- 204 No Content — deleted
- 400 Bad Request — malformed request
- 401 Unauthorized — not authenticated
- 403 Forbidden — authenticated but not permitted
- 404 Not Found
- 422 Unprocessable Entity — validation failure
- 429 Too Many Requests — rate limited
- 500 Internal Server Error

### 5.5 Authentication Flow (API)

```
Client → POST /api/v1/auth/login { email, password }
Server → validates credentials
       → checks MFA if required
       → issues Sanctum token
       → returns { token, user, permissions }

Client → All subsequent requests include:
         Authorization: Bearer {token}

Token management:
  - Tokens stored in personal_access_tokens (Sanctum default)
  - Token expiry: 24 hours (web), 30 days (remember me)
  - Token revocation: POST /api/v1/auth/logout
  - Token refresh: POST /api/v1/auth/refresh
```

---

## 6. AUTHENTICATION & RBAC ARCHITECTURE

### 6.1 Authentication Stack

| Component | Technology | Purpose |
|---|---|---|
| Web sessions | Laravel Session | Blade-served authenticated pages |
| API tokens | Laravel Sanctum | Stateless API authentication |
| Password hashing | Bcrypt (cost 12) | Credential storage |
| MFA | TOTP (Google Auth compatible) | Admin + Staff MFA |
| Role management | Spatie Laravel-Permission | Role + permission assignment |
| Authorization | Laravel Gates + Policies | Resource-level access control |

### 6.2 Role Definitions

Spatie roles registered in database. Roles are hierarchical in permission coverage
but not in inheritance — permissions are explicit and additive.

```
Platform Super Admin     — Global unrestricted access
Platform Admin           — Full operational access, cannot modify core config
ICS Staff — CRM          — CRM module access only
ICS Staff — Training     — Training module access only
ICS Staff — Content      — CMS, Knowledge Center, Research Center
Client Admin             — Client Portal (own organization only)
Partner Admin            — Partner Portal (own organization only)
Government Agency Rep    — Marketplace + Training access
Vendor                   — Marketplace listings
Startup Founder          — Startup Hub (own startup only)
Startup Member           — Startup Hub (read + contribute)
Trainer                  — Training Institute (course creation)
Student                  — Training enrollment and completion
Guest                    — Public content only (unauthenticated)
```

### 6.3 Permission Naming Convention

CANONICAL Format: {module}.{resource}.{action}  (D-044 — supersedes the earlier
{action}.{module}.{scope} form; reconciled with PERMISSION_MATRIX, the catalogue
of record). Groups permissions by module and scales to future modules.

Examples:
```
crm.leads.create
crm.leads.read.all
crm.leads.update.own
crm.contracts.delete
training.courses.create
training.enrollments.create
marketplace.listings.post
marketplace.listings.approve
knowledge.tier1.read
analytics.executive.dashboard
platform.users.create
```

### 6.4 Policy Architecture

Every Model with user-owned records has a corresponding Policy class.

Policy naming: {Model}Policy (e.g. LeadPolicy, CoursePolicy, StartupPolicy)

Policy methods: viewAny, view, create, update, delete, restore, forceDelete

Ownership checks in Policy:
- Staff roles: permission check only
- Organization roles: permission check + tenant match
- Individual roles: permission check + owner match (user_id)

```
Every protected controller action:
  1. Auth check (middleware)
  2. Role permission check (middleware or Gate::authorize)
  3. Resource ownership check (Policy)
  4. PROCEED
```

---

## 7. NOTIFICATION ARCHITECTURE

### 7.1 Channels

| Channel | Provider | Use Case |
|---|---|---|
| Mail | Brevo SMTP/API | All email: transactional, alerts, digests |
| WhatsApp | WhatsApp Business API | High-priority alerts, onboarding |
| Database | Laravel notifications table | In-app notification center |

### 7.2 Notification Class Structure

All notifications extend Laravel's Notification base class.
Each notification implements the channels it supports.

```
App\Notifications\
  Core\WelcomeNotification            — channels: mail, whatsapp, database
  Core\PasswordChangedNotification    — channels: mail, database
  Core\RoleAssignedNotification       — channels: mail, database
  CRM\LeadAssignedNotification        — channels: mail, database
  CRM\ContractSignedNotification      — channels: mail, whatsapp, database
  Training\EnrollmentConfirmedNotif.  — channels: mail, database
  Training\CourseCompletedNotif.      — channels: mail, whatsapp, database
  Training\CertificateIssuedNotif.    — channels: mail, database
  Marketplace\ListingApprovedNotif.   — channels: mail, database
  Marketplace\ApplicationReceivedN.   — channels: mail, database
  Partner\PartnerApprovedNotif.       — channels: mail, whatsapp, database
  Startup\MilestoneCompletedNotif.    — channels: mail, database
```

### 7.3 In-App Notification Center

Laravel's built-in notifications table stores database channel notifications.
Notification center UI component polls /api/v1/notifications/ for unread count
and notification list.

```
User-facing:
  GET  /api/v1/notifications           — paginated list
  GET  /api/v1/notifications/unread    — unread count
  POST /api/v1/notifications/{id}/read — mark as read
  POST /api/v1/notifications/read-all  — mark all read
```

### 7.4 Delivery Flow (Phase 1 — Cron Queue)

```
Event dispatched (e.g. CourseCompleted)
  → Listener fires: IssueCertificate
  → Certificate generated
  → Notification created: CourseCompletedNotification
  → Dispatched to queue (sys_jobs table)
  → Cron job runs every 5 minutes
  → Processes queued notifications
  → Brevo API call for email
  → WhatsApp API call for WhatsApp
  → Database insert for in-app
```

### 7.5 User Notification Preferences

```
notify_preferences
  id
  user_id
  notification_type    — class name
  mail_enabled         — boolean
  whatsapp_enabled     — boolean
  database_enabled     — boolean
  updated_at
```

---

## 8. STORAGE ARCHITECTURE

### 8.1 Phase 1 — Hostinger Filesystem

```
Driver: local (Laravel Flysystem)

Disk: public
  Path: storage/app/public/
  URL: https://platform.ics.org/storage/
  Use: Profile photos, course thumbnails, public downloads

Disk: private
  Path: storage/app/private/
  URL: Served via PHP controller (authenticated delivery)
  Use: Client deliverables, contracts, certificates, invoices
```

### 8.2 File Security Controls

- All uploads processed through StorageService (never direct move_uploaded_file)
- Extension whitelist enforced before storage
- MIME type validated from file content (not extension)
- Filename: UUID-generated (never preserve original filename)
- Private files: served via GET /api/v1/storage/{uuid} with permission check
- Scan: file size limits enforced per file type

### 8.3 Allowed File Types

| Category | Extensions | Max Size |
|---|---|---|
| Images | jpg, jpeg, png, webp, gif | 5 MB |
| Documents | pdf, docx, xlsx, pptx | 25 MB |
| Videos | mp4, webm | 500 MB |
| Archives | zip | 50 MB |

### 8.4 Phase 3 Cloud Migration Path

Laravel Flysystem allows disk driver change in config/filesystems.php and .env only.
Application code does not change at migration.

```
.env change:
  FILESYSTEM_DISK=s3  (was: local)
  AWS_BUCKET=ics-platform
  AWS_DEFAULT_REGION=eu-west-1

Zero code changes required in the application.
```

---

## 9. EVENT-DRIVEN ARCHITECTURE

### 9.1 Pattern

Laravel Events and Listeners are the approved mechanism for all cross-module
communication. No module may directly query another module's Model or table.

```
Module A dispatches Event → Module B Listener handles it
```

Phase 1: Synchronous dispatch (ShouldQueue not implemented in most listeners)
Phase 2: Async queued dispatch (listeners implement ShouldQueue + Redis)

### 9.2 Core Event Catalog

#### Platform Core
| Event | Triggers | Listeners |
|---|---|---|
| UserRegistered | User account creation | SendWelcomeNotification, CreateDefaultProfile, LogAuditEvent |
| UserLoggedIn | Successful login | UpdateLastSeen, LogAuditEvent |
| UserLoggedOut | Logout | LogAuditEvent |
| PasswordChanged | Password update | SendPasswordChangedAlert, LogAuditEvent |
| RoleAssigned | Role change | SendRoleAssignedNotification, LogAuditEvent |
| AccountLocked | 5 failed logins | NotifySecurityTeam, LogAuditEvent |

#### CRM Module
| Event | Triggers | Listeners |
|---|---|---|
| LeadCreated | New lead | AssignToRepresentative, SendLeadAlert, LogAuditEvent, UpdateAnalytics |
| LeadUpdated | Lead status change | LogAuditEvent, UpdateAnalytics |
| LeadConverted | Lead → Client | CreateClientAccount, SendConversionNotification, UpdateAnalytics |
| ContractCreated | New contract | NotifySignatories, LogAuditEvent |
| ContractSigned | Contract executed | TriggerClientOnboarding, SendContractConfirmation, LogAuditEvent |
| RenewalDue | Renewal date approached | SendRenewalAlert, LogAuditEvent |

#### Training Module
| Event | Triggers | Listeners |
|---|---|---|
| CoursePublished | Course goes live | NotifyEligibleStudents, UpdateAnalytics |
| CourseEnrolled | Enrollment created | SendEnrollmentConfirmation, UpdateCourseStats, LogAuditEvent |
| LessonCompleted | Lesson marked complete | UpdateProgress, UnlockNextLesson |
| AssessmentSubmitted | Assessment handed in | TriggerAutoGrade, NotifyInstructor |
| AssessmentPassed | Pass threshold met | UnlockNextLesson, SendPassNotification |
| CourseCompleted | All lessons done + passed | IssueCertificate, SendCompletionNotification, UpdateAnalytics |
| CertificateIssued | Certificate generated | SendCertificateEmail, UpdateAnalytics |

#### Opportunity Marketplace
| Event | Triggers | Listeners |
|---|---|---|
| ListingSubmitted | New listing | NotifyReviewers, LogAuditEvent |
| ListingApproved | Admin approves | PublishListing, NotifySubmitter, UpdateAnalytics |
| ListingRejected | Admin rejects | NotifySubmitterWithReason, LogAuditEvent |
| ListingExpired | Deadline passed | ArchiveListing, NotifyOwner |
| ApplicationSubmitted | User applies | NotifyListingOwner, SendApplicationConfirmation, LogAuditEvent |

#### Partner Module
| Event | Triggers | Listeners |
|---|---|---|
| PartnerApplicationSubmitted | Application created | NotifyAdmins, LogAuditEvent |
| PartnerApproved | Approval granted | GrantPortalAccess, SendWelcomeKit, LogAuditEvent |
| ReferralConverted | Referral → Client | CalculateCommission, NotifyPartner, UpdateAnalytics |
| AgreementSigned | Partner agreement | NotifyBothParties, LogAuditEvent |

#### Startup Module
| Event | Triggers | Listeners |
|---|---|---|
| StartupRegistered | New startup profile | AssignDefaultMentor, SendWelcomeKit, LogAuditEvent |
| MilestoneCompleted | Milestone marked done | UpdateProgress, SendCongratulations, NotifyMentor, LogAuditEvent |
| MentorAssigned | Mentor linked to startup | NotifyMentor, NotifyStartup |

### 9.3 Event Catalog Completion Requirement

This catalog covers the initial events. A full event catalog covering all
cross-module interactions must be produced and approved before each module
enters active development. The event catalog is a blocking deliverable.

---

## 10. ANALYTICS ARCHITECTURE

### 10.1 Design

The Analytics Layer is a read-only aggregation layer. No analytics query
targets source module tables directly. Data flows:

```
Source Tables (crm_*, training_*, etc.)
    → Aggregation Jobs (scheduled via Laravel cron)
    → analytics_snapshots / analytics_metrics tables
    → Analytics API (/api/v1/analytics/)
    → Executive Dashboard (Chart.js)
```

### 10.2 Executive Dashboard KPIs

| Category | KPI |
|---|---|
| Business | Total leads, conversion rate, active contracts, revenue pipeline |
| Training | Enrollments this month, completion rate, certificates issued |
| Marketplace | Active listings, applications received, approval rate |
| Partners | Active partners, referrals converted, commission pipeline |
| Startups | Registered startups, milestones completed, mentors active |
| Platform | Active users, sessions today, top modules used |

### 10.3 Aggregation Schedule

| Report | Frequency | Table |
|---|---|---|
| Daily KPI snapshot | Daily at 00:30 UTC | analytics_snapshots |
| CRM pipeline report | Daily at 01:00 UTC | analytics_crm_pipeline |
| Training stats | Daily at 01:30 UTC | analytics_training_stats |
| Marketplace report | Daily at 02:00 UTC | analytics_marketplace_stats |
| Partner performance | Weekly Sunday 03:00 UTC | analytics_partner_perf |
| Executive summary | Weekly Monday 06:00 UTC | analytics_exec_summary |

### 10.4 Module Analytics Tables

```
analytics_snapshots           — Daily cross-module KPI snapshot
analytics_crm_pipeline        — Lead funnel and pipeline values
analytics_training_stats      — Enrollment, completion, cert rates
analytics_marketplace_stats   — Listing and application metrics
analytics_partner_perf        — Partner referral and conversion metrics
analytics_startup_progress    — Startup milestone completion rates
analytics_user_activity       — Module usage and session counts
```

---

## 11. AI SERVICES ARCHITECTURE

### 11.1 Integration Pattern

```
Request → Controller → {UseCase}Service → GeminiService → HTTP Client → Gemini API
                                ↓                    ↓                      ↓
                         Rate limit check      Budget check         Response received
                                ↓                    ↓                      ↓
                         Tier enforcement      Queue if heavy    Log to ai_requests
                                                                           ↓
                                                               Return to caller
                                                                           ↓
                                                          Assessment? → ai_assessments
                                                          Proposal?   → crm_proposals
                                                          Search?     → return results
```

### 11.2 Service Class Map

Each approved use case has a dedicated service class. All extend a base
`BaseAIService` which handles rate limiting, logging, and graceful fallback.

```
App\Services\AI\
  BaseAIService               — rate limiting, cost logging, fallback
  GeminiService               — raw Gemini API wrapper (used by all)
  WebsiteAssistantService     — conversational assistant for public site
  LeadQualificationService    — CRM lead scoring and qualification
  ProposalGenerationService   — proposal document drafting
  TrainingRecommendationService — personalised course recommendations
  KnowledgeSearchService      — semantic knowledge base search
  ResearchAssistantService    — research summarisation
  OpportunityMatchingService  — marketplace opportunity-to-profile matching
  StartupReadinessService     — startup readiness evaluation
  DigitalMaturityService      — digital maturity evaluation
  ContentDraftingService      — CMS content creation assistant
```

### 11.3 API Endpoint Map

| Use Case | Method | Endpoint | Rate Tier |
|---|---|---|---|
| Website Assistant | POST | /api/v1/ai/website/chat | Tier 1 |
| Lead Qualification | POST | /api/v1/ai/crm/leads/{id}/qualify | Tier 2 |
| Proposal Generation | POST | /api/v1/ai/crm/opportunities/{id}/proposal | Tier 3 |
| Training Recommendations | GET | /api/v1/ai/training/recommendations | Tier 1 |
| Knowledge Search | POST | /api/v1/ai/knowledge/search | Tier 1 |
| Research Assistant | POST | /api/v1/ai/research/assist | Tier 2 |
| Opportunity Matching | GET | /api/v1/ai/marketplace/matches | Tier 1 |
| Startup Readiness | POST | /api/v1/ai/startups/{id}/readiness | Tier 2 |
| Digital Maturity | POST | /api/v1/ai/assessments/digital-maturity | Tier 2 |
| Content Drafting | POST | /api/v1/ai/content/draft | Tier 2 |

### 11.4 Rate Limiting Tiers

Tiers reflect token cost and response complexity.
All limits configurable in config/ics.php.

| Tier | Use Cases | Per User/Hour | Global Daily (Phase 1) |
|---|---|---|---|
| Tier 1 — Light | Website Assistant, Knowledge Search, Training Recs, Opportunity Matching | 30 req/hr | Shared budget |
| Tier 2 — Medium | Lead Qualification, Research Assistant, Startup Readiness, Digital Maturity, Content Drafting | 15 req/hr | Shared budget |
| Tier 3 — Heavy | Proposal Generation | 5 req/hr | Shared budget |
| Global budget | All tiers combined | — | 1,000 req/day |

### 11.5 Usage & Cost Database Schema

```
ai_requests
  id
  tenant_id
  user_id
  module              — website | crm | training | marketplace | startup | content
  use_case            — slug matching service class (e.g. lead_qualification)
  rate_tier           — 1 | 2 | 3
  prompt_tokens
  response_tokens
  total_tokens
  model_version       — gemini-2.0-flash | gemini-2.0-pro | etc.
  cost_usd            — calculated from token counts
  status              — success | failed | timeout | budget_exceeded
  cached              — boolean (was this served from cache?)
  created_at

ai_assessments          — stores Startup Readiness + Digital Maturity results
  id
  tenant_id
  user_id
  subject_type          — startup | client_organization | individual
  subject_id
  assessment_type       — startup_readiness | digital_maturity
  overall_score         — DECIMAL(5,2)
  dimensions            — JSON (dimension name → score breakdown)
  recommendations       — JSON (AI-generated recommendations per dimension)
  model_version
  ai_request_id         — FK → ai_requests.id
  created_at
  updated_at
  deleted_at
```

Assessment results are stored permanently for progress tracking over time.
A client may retake a Digital Maturity Assessment annually — history is preserved.

### 11.6 Use Case Descriptions

**1. AI Website Assistant**
Public-facing chatbot embedded on the Corporate Website.
Answers questions about ICS services, directs users to relevant modules,
captures leads for the CRM. Context: ICS service catalogue + FAQ content.
Guest users permitted (no auth required). Session-scoped conversation.

**2. AI Lead Qualification**
Analyzes CRM lead record (industry, size, engagement, source, notes).
Outputs: qualification score (0–100), recommended tier (hot/warm/cold),
suggested next action, and qualification rationale.
Writes result back to crm_leads.ai_qualification_score.

**3. AI Proposal Generation**
Takes CRM Opportunity data (client, scope, service line, budget, notes).
Generates a structured proposal draft: executive summary, scope of work,
methodology, timeline, investment summary.
Output: stored as crm_proposals record with status = draft.
Staff reviews, edits, and approves before sending.

**4. AI Training Recommendation Engine**
Reads user profile (role, industry, past enrollments, stated goals).
Queries published course catalogue.
Returns: ordered list of recommended courses with relevance rationale.
Displayed on Training Institute dashboard.

**5. AI Knowledge Base Search**
Semantic search using Gemini embeddings over knowledge articles.
User types natural language query → returns ranked articles with excerpts.
Replaces keyword search for Knowledge Center.
Falls back to MySQL FULLTEXT search if AI unavailable.

**6. AI Research Assistant**
User provides a topic or question.
Gemini searches and summarises relevant Research Center publications.
Provides answer with source citations (linked to research records).
Research Center module only.

**7. AI Opportunity Matching**
Reads authenticated user's profile (skills, sector, location, interests).
Matches against active Marketplace listings.
Returns personalised ranked listing feed on Marketplace dashboard.
Re-runs on login and when profile is updated.

**8. AI Startup Readiness Assessment**
Structured assessment: 6 dimensions (Product, Market, Team, Finance,
Operations, Technology). User answers guided questions.
Gemini scores each dimension and generates recommendations.
Result stored in ai_assessments. Progress tracked over time.

**9. AI Digital Maturity Assessment**
Structured assessment: 5 dimensions (Strategy, People, Process, Technology,
Data). Designed for ICS clients (B2B).
Outcome: Digital Maturity Score + ICS service recommendations aligned to gaps.
Key CRM tool — assessment results visible to CRM staff on client record.
Result stored in ai_assessments. Feeds into CRM opportunity pipeline.

**10. AI Content Drafting**
Available to ICS Content Staff and Trainers only.
Accepts: brief, target audience, key points.
Outputs: draft article, guide, course description, or research abstract.
Draft stored in CMS as status = ai_draft for staff review and edit.
Never published automatically — human review and approval required.

### 11.7 Future Use Cases (Reserved)

**11. AI Business Advisory Assistant** (Future)
Authenticated users ask complex business questions.
Gemini provides advisory responses grounded in ICS knowledge base.
Requires advanced context management — not Phase 1.

**12. AI Executive Dashboard Insights** (Future)
Analyzes platform analytics data.
Generates natural language insights and anomaly alerts for the dashboard.
Requires stable Analytics Layer data history — not Phase 1.

### 11.8 Graceful Degradation Policy

Every AI endpoint must implement all four mandatory controls:

| Control | Implementation |
|---|---|
| Rate limiting | Check before call; return 429 with retry-after header if exceeded |
| Cost monitoring | Log every call to ai_requests regardless of outcome |
| Usage analytics | Aggregated daily into analytics layer (D-025) |
| Graceful fallback | Return cached last result (24hr TTL in ai_cache) or degrade cleanly |

On failure: the core business feature continues to function without the AI layer.
Example: Knowledge Search falls back to MySQL FULLTEXT. Opportunity Matching
falls back to chronological listing. Training Recommendations fall back to
most popular courses. No core workflow is blocked by AI unavailability.

---

## 12. PWA ARCHITECTURE

### 12.1 Components

| Component | File | Purpose |
|---|---|---|
| Web App Manifest | public/manifest.json | App name, icons, theme, display mode |
| Service Worker | public/sw.js | Offline cache, background sync |
| Install Prompt | resources/js/pwa.js | Install-to-homescreen prompt logic |
| Push Subscription | resources/js/push.js | VAPID push notification subscription |

### 12.2 Caching Strategy

```
Service Worker Cache Strategies:

Static assets (CSS, JS, fonts, images):
  Strategy: Cache First → Stale While Revalidate

API responses (GET only):
  Strategy: Network First → Cache Fallback (offline)

Offline fallback page:
  Strategy: Cache Only (pre-cached on install)
```

### 12.3 Push Notification Architecture (Web Push Protocol)

```
User subscribes → POST /api/v1/notifications/push/subscribe
  → Subscription stored in notify_push_subscriptions table

Server wants to push → NotificationService::pushWeb(userId, payload)
  → Loads subscription from table
  → PHP Web Push library sends via VAPID
  → Browser receives push
  → Service worker displays notification
```

### 12.4 Offline Capability

| Content | Offline Behavior |
|---|---|
| Previously viewed pages | Served from cache |
| Dashboard | Cached last snapshot |
| Forms | Queued for sync on reconnect |
| New API requests | Show offline banner |

---

## 13. INTERNATIONALIZATION ARCHITECTURE

### 13.1 Translation Layers

Two distinct layers:

**Layer 1 — UI Strings (static)**
PHP array files in /resources/lang/{locale}/
Accessed via __('module.key')

**Layer 2 — Content Strings (dynamic)**
Stored in i18n_translations table
Accessed via HasTranslations trait on Model
Model method: $article->getTranslation('title', 'fr')

### 13.2 Language Phases

| Phase | Language | Notes |
|---|---|---|
| Phase 1 | English (en) | Complete coverage required |
| Phase 2 | French (fr) | Strategic for Francophone Africa government market |
| Phase 3 | Arabic (ar) | North Africa + RTL layout activation |

### 13.3 RTL Architecture

When locale = ar:
- <html lang="ar" dir="rtl"> set at layout level
- Tailwind RTL plugin provides rtl: variant
- All spacing uses logical CSS properties: ms-4 (not ml-4), ps-6 (not pl-6)
- All alignment uses logical properties: start/end (not left/right)
- Text alignment: text-start / text-end (not text-left / text-right)

Rule: No physical directional CSS properties (left, right, margin-left, padding-right)
in any component. All directional CSS uses logical properties from day one.

### 13.4 Locale Detection

Priority order:
1. User account preference (stored in core_users.locale)
2. Session variable (set on language switch)
3. Accept-Language browser header
4. Default: en

---

## 14. SECURITY ARCHITECTURE

### 14.1 Authentication Security

| Control | Implementation |
|---|---|
| Password hashing | Bcrypt cost factor 12 (Laravel Hash::make) |
| Breach detection | HaveIBeenPwned API check on registration and password change |
| Password minimum | 12 characters, mixed case, number, special char |
| MFA | TOTP (Admin + Staff required; others optional) |
| Session fixation | Session regenerated on login and privilege change |
| Session cookie | httpOnly, Secure, SameSite=Strict |
| Account lockout | 5 failed attempts → 15 min lockout (exponential backoff) |
| Lockout notification | Email alert to account owner on lockout |

### 14.2 Request Security (OWASP)

| Threat | Control |
|---|---|
| SQL Injection | Eloquent ORM + PDO prepared statements. Raw queries forbidden. |
| XSS | Blade auto-escapes {{ }} output. {!! !!} only for sanitized HTML. |
| CSRF | Laravel CSRF tokens on all state-changing requests |
| Mass Assignment | $fillable defined on every Model. $guarded forbidden. |
| File Upload Attacks | Extension whitelist, MIME validation, UUID filename, webroot exclusion |
| Rate Limiting | Laravel ThrottleRequests middleware: 60/min public, 120/min auth |
| Clickjacking | X-Frame-Options: SAMEORIGIN header |
| Content Sniffing | X-Content-Type-Options: nosniff header |
| HTTPS Enforcement | HSTS header: max-age=31536000; includeSubDomains |
| CSP | Content-Security-Policy header — script-src, style-src, img-src defined |
| Sensitive headers | Server, X-Powered-By headers removed in production |

### 14.3 Data Protection (NDPA / GDPR)

| Requirement | Implementation |
|---|---|
| Consent | Recorded at registration (core_consent_logs) with timestamp and policy version |
| Data access | GET /api/v1/profile/data-export — JSON download of own records |
| Right to erasure | POST /api/v1/profile/delete — soft delete + PII nullification |
| Data minimisation | Only collect what each module requires; documented in schema |
| Retention | core_retention_policies table; automated purge via scheduled command |
| Breach response | Incident runbook required; 72-hour NDPA notification obligation |
| DPO designation | Internal Data Protection Officer to be assigned before Phase 1 launch |

### 14.4 WCAG 2.1 Level AA — Approved (D-028)

Required controls — mandatory across all frontend modules:
- Colour contrast: minimum 4.5:1 for normal text, 3:1 for large text
- Keyboard navigation: all interactive elements reachable via Tab
- Focus indicators: visible focus outline on all focusable elements
- Screen reader: ARIA labels on all icons, images, and form elements
- Form errors: programmatically associated error messages
- Skip navigation: "Skip to main content" link at page top
- Heading hierarchy: logical H1 → H2 → H3 structure on every page
- Alternative text: all non-decorative images have descriptive alt text
- Video captions: all video content has captions (Training module)

### 14.5 Hostinger-Specific Controls (Phase 1)

```
.htaccess rules:
  Deny access to /config/
  Deny access to /storage/ (except public symlink)
  Deny access to /vendor/
  Deny access to /database/
  Deny access to .env (belt-and-suspenders)
  Force HTTPS redirect

php.ini (if editable):
  display_errors = Off
  log_errors = On
  expose_php = Off

Composer: install with --no-dev in production
Error reporting: log to /storage/logs only, never rendered to browser
```

---

## 15. DEPLOYMENT ARCHITECTURE

### 15.1 Environments

| Environment | Purpose | Server |
|---|---|---|
| Local | Developer machines | PHP built-in server / Laravel Sail |
| Staging | Pre-production testing | Hostinger subdomain |
| Production | Live platform | Hostinger primary domain |

### 15.2 Git Workflow (GitHub Flow)

```
main branch        — production-ready code only
staging branch     — staging environment
feature/* branches — new development (branch from main)

Flow:
  1. Branch: git checkout -b feature/module-name
  2. Develop and commit locally
  3. PR → staging for testing
  4. Merge staging → test on staging environment
  5. PR → main for production
  6. Merge main → deploy to production
```

### 15.3 Phase 1 Deployment Process (Hostinger Shared)

```
1. git push to main
2. Hostinger Git deployment (or manual pull via SSH)
3. composer install --no-dev --optimize-autoloader
4. php artisan migrate --force
5. php artisan config:cache
6. php artisan route:cache
7. php artisan view:cache
8. php artisan storage:link
```

### 15.4 Environment Configuration

```
.env (NOT in git — from .env.example)

APP_ENV=production
APP_DEBUG=false
APP_URL=https://platform.ics.org

DB_CONNECTION=mysql
DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

MAIL_MAILER=smtp (Brevo)
BREVO_API_KEY=

WHATSAPP_API_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=

GEMINI_API_KEY=

CACHE_STORE=database          # shared: database|file ; VPS: redis
SESSION_DRIVER=database        # shared: database|file ; VPS: redis
QUEUE_CONNECTION=database      # shared: database (cron) ; VPS: redis + Horizon
FILESYSTEM_DISK=local          # shared: local ; cloud(P3): s3

# Feature flags — deferred-to-VPS runtime behaviours (D-037)
ICS_WAREHOUSE_ETL_ENABLED=false   # shared: false ; VPS: true
ICS_HEAVY_JOBS=false              # shared: false ; VPS: true
ICS_AI_HIGH_VOLUME=false          # shared: false ; VPS: true
ICS_COMMUNITY_SCALING=false       # shared: false ; VPS: true

VAPID_PUBLIC_KEY=  (PWA push)
VAPID_PRIVATE_KEY=

CLOUDFLARE_ENABLED=true        # D-039 SEC-09 — CDN/WAF/bot in front from Phase 1
MAIL_FALLBACK_MAILER=          # D-039 SPOF-04 — secondary SMTP for auth-critical mail
```

---

### 15.5 Dual-Target Deployment Model (D-037)

The architecture is VPS-first; deployment is shared-hosting-first. The SAME
codebase and SAME database schema run in both environments. The environment is
selected entirely by `.env`. Migration to VPS is a configuration change, never a
code or schema change.

```
                  ONE CODEBASE  ·  ONE SCHEMA  ·  TWO RUNTIME PROFILES

   ┌─────────────────────────────┐        ┌─────────────────────────────┐
   │  SHARED HOSTING (deploy now)│        │  VPS (migrate when justified)│
   ├─────────────────────────────┤        ├─────────────────────────────┤
   │ QUEUE_CONNECTION=database    │  .env  │ QUEUE_CONNECTION=redis       │
   │ CACHE_STORE=file/database    │ ─────► │ CACHE_STORE=redis            │
   │ SESSION_DRIVER=file/database │  only  │ SESSION_DRIVER=redis         │
   │ ETL flag = false             │        │ ETL flag = true             │
   │ AI high-volume = false       │        │ AI high-volume = true       │
   │ workers = cron every 5 min   │        │ workers = Supervisor/Horizon│
   └─────────────────────────────┘        └─────────────────────────────┘
        Same migrations run in both.  dw_*, i18n_*, tenant_id present in both.
```

#### Capability State by Environment

| Capability | Schema | Shared Runtime | VPS Runtime |
|---|---|---|---|
| RBAC, CRM, Client Portal | built | ON | ON |
| Knowledge / Research Center | built | ON | ON |
| Tier-1 Analytics | built | ON (chunked cron) | ON (workers) |
| AI Assistant | built | ON (low-volume, capped) | ON (high-volume) |
| Cache / Session / Queue | built | MySQL/file (cron) | Redis (Horizon) |
| Data Warehouse tables | built | present, no ETL | present, ETL runs |
| Data Warehouse ETL automation | built | OFF (flag) | ON (flag) |
| i18n translation table | built | present (EN only) | present (EN/FR/AR) |
| TenantScope global logic | built | column only | activatable (P3) |
| Community scaling (forums, matching) | built | OFF (flag) | ON (flag) |
| Heavy/async event processing | built | sync/cron | async (Redis) |

#### The Three Guarantees That Make Migration Config-Only

1. **No hardcoded drivers.** Every queue/cache/session/filesystem/mail reference
   resolves through `config()`. Grep gate in CI: fail build if a driver literal
   (`'redis'`, `'database'` as a connection) appears outside config files.
2. **ShouldQueue everywhere heavy.** Every non-instant listener implements
   `ShouldQueue`. `QUEUE_CONNECTION=sync|database` runs them inline/cron on shared;
   `redis` runs them async on VPS. Identical code.
3. **Feature-flag gates.** Deferred runtime behaviours are wrapped in
   `if (config('ics.<flag>'))`. The code ships; the flag decides. Flipping a flag
   is a `.env` edit, not a deploy of new code.

#### 15.5.1 Auth-Critical Mail Exception (D-039 SPOF-04)
Password-reset, email-verification, and MFA mail MUST NOT depend on the 5-minute
cron queue on shared hosting. These are sent synchronously (or via a dedicated
high-priority path) and use `MAIL_FALLBACK_MAILER` if the primary (Brevo) fails.
All other mail is queued.

---

## 16. MODULE DEVELOPMENT SEQUENCE

### Phase 1 — Foundation (Months 1–3)

Priority: Core platform stability before any domain modules.

| # | Deliverable | Dependency |
|---|---|---|
| 1 | Core Platform: Auth, RBAC, User Management | None |
| 2 | Core Platform: Audit Log, i18n Engine | Auth |
| 3 | Core Platform: Notification Infrastructure | Auth |
| 4 | Core Platform: Job Queue + Cron | None |
| 5 | Corporate Website + CMS | i18n |
| 6 | Analytics: Database structure + aggregation | Core Platform |
| 7 | CRM Module | Core Platform |
| 8 | Client Portal | CRM, Auth |

### Phase 1 — Modules (Months 4–6)

| # | Deliverable | Dependency |
|---|---|---|
| 9 | Opportunity Marketplace | Core Platform, Auth |
| 10 | Partner Portal | Core Platform, Auth |
| 11 | Training Institute — Basic | Core Platform, Notifications |
| 12 | Startup Hub | Core Platform, Auth |
| 13 | Training Institute — Assessments + Certificates | Training Basic |
| 14 | PWA + Service Worker | All Phase 1 modules |

### Phase 2 — Enhancement (Months 7–12)

| # | Deliverable | Dependency |
|---|---|---|
| 15 | Knowledge Center | CMS, i18n |
| 16 | Research Center | CMS, i18n |
| 17 | AI Services (Gemini integration) | Core Platform, use cases approved |
| 18 | Analytics Executive Dashboard | All Phase 1 modules |
| 19 | Subscription Module | Billing provider approved |
| 20 | Redis Queue + Horizon | VPS migration |
| 21 | French language (i18n Layer 2) | All Phase 1 + 2 content |

### Phase 3 — Scale

| # | Deliverable | Dependency |
|---|---|---|
| 22 | Cloud Storage Migration | Phase 2 stable |
| 23 | Arabic + RTL | Phase 2 i18n |
| 24 | Incubator / Accelerator Programs | Startup Hub |
| 25 | Membership System | Subscription Module |
| 26 | Vendor Marketplace | Opportunity Marketplace |
| 27 | Investment Network | Legal review complete |
| 28 | Franchise / Multi-Tenancy Activation | All modules stable |

---

## 17. FUTURE MODULE SEAMS

How current architecture supports the 7 future modules from D-019:

### LMS Extension
Training Institute uses training_ table prefix and Training namespace.
LMS module uses same namespace and extends existing Course and Enrollment models.
No schema migration of existing data required.

### Vendor Marketplace
Opportunity Marketplace uses marketplace_ prefix.
A listing_type column distinguishes Opportunities from Vendor Listings.
Vendor Marketplace extends the Listing model with a vendor-specific subtype.

### Membership System
Subscription Module (Phase 2) is designed as the billing core.
Membership System extends subscription with membership-specific access rules.
RBAC system receives a new Membership role tier automatically.

### Incubator & Accelerator Programs
Startup Hub uses startup_ prefix.
A program_type column on startup_profiles differentiates General, Incubator, Accelerator.
Program management tables (startup_programs) added without touching existing schema.

### Investment Network
Consumes data from Startup Hub and Partner Portal via Events.
New invest_ table prefix added.
Does not modify startup_ or partner_ tables.
Investment Network reads Startup data via a published internal API / Event pattern.

### Franchise Operations
tenant_id column present on all tables from day one (nullable Phase 1).
TenantScope model scope present but inactive Phase 1.
Multi-tenancy activation in Phase 3 requires:
  1. Set tenant_id = tenant record for ICS's own data
  2. Activate TenantScope globally
  3. New tenant onboarding flow
Zero schema migration required — the column is already there.

---

## 18. BILLING & PAYMENT ARCHITECTURE (D-031)

---

### 18.1 Payment Gateway Architecture

#### Abstraction Layer

All payment processing is routed through a gateway abstraction contract.
Adding a new gateway (Flutterwave, Stripe) requires a new implementation class
and a `.env` config change — no business logic changes.

```
App\Services\Billing\
  Contracts\
    PaymentGatewayContract     — interface all gateways implement
  Gateways\
    PaystackGateway            — Phase 1-2 (primary)
    FlutterwaveGateway         — Future
    StripeGateway              — Future
  BillingService               — coordinates invoicing, payments, subscriptions
  InvoiceService               — invoice generation, numbering, PDF
  SubscriptionService          — lifecycle management
  RevenueReportService         — aggregation and reporting
```

#### PaymentGatewayContract Methods

```
initializeTransaction(array $data): TransactionResponse
verifyTransaction(string $reference): TransactionVerification
createCustomer(array $data): CustomerResponse
createPlan(array $data): PlanResponse
createSubscription(array $data): SubscriptionResponse
cancelSubscription(string $code): bool
listTransactions(array $filters): Collection
refundTransaction(string $reference, int $amountKobo): RefundResponse
verifyWebhookSignature(string $payload, string $signature): bool
```

#### Environment Configuration

```
.env
  PAYMENT_GATEWAY=paystack
  PAYSTACK_PUBLIC_KEY=pk_live_...
  PAYSTACK_SECRET_KEY=sk_live_...
  PAYSTACK_WEBHOOK_SECRET=...
```

Gateway selection is resolved at runtime from `config/billing.php` → `PAYMENT_GATEWAY`.
No code changes needed to switch gateways.

---

### 18.2 Subscription Architecture

#### Subscription Plans

Seven billing capabilities map to plan types:

| Billing Capability | Plan Type | Module |
|---|---|---|
| Course Payments | one_time | Training Institute |
| Membership Payments | subscription | Subscription Module |
| Event Registrations | one_time | Future Events Module |
| Subscription Plans | subscription | Subscription Module |
| Marketplace Fees | one_time | Opportunity Marketplace |
| Consulting Deposits | one_time | CRM |
| Proposal Acceptance Payments | one_time | CRM |

#### Subscription Lifecycle

```
                     ┌──────────┐
                     │  TRIAL   │ ← (only if plan.trial_days > 0)
                     └────┬─────┘
                          │ trial_ends_at reached → auto-charge
                     ┌────▼─────┐
               ┌────►│  ACTIVE  │◄──────────────┐
               │     └────┬─────┘               │
               │          │                     │
               │   payment fails          payment recovered
               │          │                     │ (within grace period)
               │     ┌────▼─────┐               │
               │     │ PAST_DUE │───────────────┘
               │     └────┬─────┘
               │          │ max retries (3 attempts over 7 days)
               │     ┌────▼──────────┐    ┌──────────────┐
               └─────│   CANCELLED   │    │   EXPIRED    │
                     └───────────────┘    └──────────────┘
                     (user-initiated)      (period ended,
                                           no auto-renew)
```

#### Subscription Schema

```
billing_plans
  id
  tenant_id
  name                    — e.g. "Individual Professional", "Organisation"
  slug                    — unique identifier
  description             — translatable (i18n)
  type                    — subscription | one_time
  module                  — training | membership | marketplace | consulting | event
  billing_period          — monthly | quarterly | annual | one_time
  price                   — DECIMAL(12,2)
  currency                — NGN | USD | GBP | EUR
  trial_days              — INT DEFAULT 0
  features                — JSON (feature list for plan comparison display)
  gateway_plan_id         — Paystack plan code (for recurring plans)
  is_active               — boolean
  sort_order              — INT (for display ordering)
  created_at
  updated_at
  deleted_at

billing_subscriptions
  id
  tenant_id
  user_id                 FK → core_users.id
  plan_id                 FK → billing_plans.id
  status                  — trial | active | past_due | cancelled | expired
  quantity                — INT DEFAULT 1 (seats for org plans)
  trial_ends_at           — TIMESTAMP NULL
  current_period_start    — TIMESTAMP
  current_period_end      — TIMESTAMP
  cancelled_at            — TIMESTAMP NULL
  cancellation_reason     — TEXT NULL
  ends_at                 — TIMESTAMP NULL (scheduled cancellation end date)
  gateway_subscription_id — Paystack subscription code
  gateway_customer_id     — Paystack customer code
  gateway_email_token     — Paystack email token (manage subscription link)
  metadata                — JSON (context: course_id, event_id, org_id)
  created_at
  updated_at
```

#### Paystack Webhook Events Handled

| Paystack Event | Platform Action |
|---|---|
| `charge.success` | Create payment record; fulfil purchase; issue invoice |
| `subscription.create` | Activate subscription record |
| `subscription.disable` | Cancel subscription record |
| `invoice.create` | Log Paystack-generated invoice reference |
| `invoice.payment_failed` | Move subscription to past_due; trigger retry notifications |
| `invoice.update` | Sync payment status on renewal invoices |
| `transfer.success` | Record payout to partner/vendor (future) |

All webhooks logged to `billing_webhooks` (append-only) before processing.
Signature verified against `PAYSTACK_WEBHOOK_SECRET` before any action is taken.
Idempotency check: `gateway_transaction_id` unique constraint prevents duplicate fulfillment.

---

### 18.3 Invoice Architecture

#### Invoice Numbering

Format: `INV-{YEAR}-{6-digit-zero-padded-sequence}`
Examples: INV-2026-000001, INV-2026-000042

Sequence is per-year, per-tenant. Counter stored in `billing_invoice_sequences`.

#### Invoice Lifecycle

```
    ┌───────┐
    │ DRAFT │ ← manually created or auto-generated pre-charge
    └───┬───┘
        │ issued (sent to client)
    ┌───▼───┐
    │ISSUED │
    └───┬───┘
        ├──────────────────────────────────┐
        │ payment received                 │ due_date passed without payment
    ┌───▼───┐                          ┌───▼───────┐
    │  PAID │                          │  OVERDUE  │
    └───┬───┘                          └───┬───────┘
        │ refund initiated                 │ payment received (late)
    ┌───▼──────┐              ┌────────────▼┐    ┌────────────┐
    │ REFUNDED │              │    PAID     │    │ CANCELLED  │
    └──────────┘              └─────────────┘    └────────────┘
                                                 (written off)
```

#### Invoice Schema

```
billing_invoices
  id
  tenant_id
  invoice_number          — INV-2026-000001 (unique per tenant)
  user_id                 FK → core_users.id
  subscription_id         FK → billing_subscriptions.id NULL (NULL for one-time)
  status                  — draft | issued | paid | overdue | cancelled | refunded
  issue_date              — DATE
  due_date                — DATE (default: issue_date + 7 days)
  paid_at                 — TIMESTAMP NULL
  subtotal                — DECIMAL(12,2)
  discount_amount         — DECIMAL(12,2) DEFAULT 0.00
  tax_amount              — DECIMAL(12,2) DEFAULT 0.00
  total                   — DECIMAL(12,2)
  currency                — NGN | USD | GBP | EUR
  exchange_rate           — DECIMAL(10,6) NULL (if multi-currency)
  notes                   — TEXT NULL
  pdf_path                — storage path to generated PDF
  sent_at                 — TIMESTAMP NULL
  reminder_sent_at        — TIMESTAMP NULL
  created_at
  updated_at
  deleted_at

billing_invoice_items
  id
  invoice_id              FK → billing_invoices.id
  description             — line item label (displayed on invoice)
  quantity                — DECIMAL(8,2) DEFAULT 1
  unit_price              — DECIMAL(12,2)
  subtotal                — DECIMAL(12,2)  (quantity × unit_price)
  discount_percent        — DECIMAL(5,2) DEFAULT 0
  module                  — training | membership | marketplace | consulting | event
  billable_type           — polymorphic model reference
  billable_id             — polymorphic ID
  created_at
  updated_at

billing_payments
  id
  tenant_id
  invoice_id              FK → billing_invoices.id NULL
  user_id                 FK → core_users.id
  gateway                 — paystack | flutterwave | stripe
  gateway_transaction_id  — Paystack reference (unique)
  gateway_transaction_ref — Paystack verification reference
  amount                  — DECIMAL(12,2)
  currency                — NGN | USD | GBP | EUR
  status                  — pending | success | failed | refunded | chargeback
  payment_method          — card | bank_transfer | ussd | mobile_money | qr
  channel                 — online | pos | bank
  paid_at                 — TIMESTAMP NULL
  gateway_response        — JSON (raw gateway payload)
  created_at
  updated_at

billing_invoice_sequences
  id
  tenant_id
  year                    — INT (e.g. 2026)
  last_sequence           — INT (auto-incremented on each invoice)
  UNIQUE KEY (tenant_id, year)

billing_webhooks
  id
  gateway                 — paystack | flutterwave | stripe
  event_type              — charge.success | subscription.create | etc.
  gateway_event_id        — gateway's own event ID (idempotency check)
  payload                 — JSON (raw body)
  signature_valid         — boolean
  processed               — boolean DEFAULT false
  processed_at            — TIMESTAMP NULL
  error_message           — TEXT NULL
  created_at
  (append-only — no update/delete)
```

#### Invoice Generation Triggers

| Trigger | Type | Invoice Generator |
|---|---|---|
| Course purchased | Automatic | BillingService::createCourseInvoice() |
| Subscription period renews | Automatic | BillingService::createSubscriptionInvoice() |
| Consulting deposit requested | Manual (CRM staff) | InvoiceService::createConsultingDeposit() |
| Proposal accepted | Automatic | BillingService::createProposalInvoice() |
| Marketplace fee due | Automatic | BillingService::createMarketplaceFeeInvoice() |
| Event registration | Automatic | BillingService::createEventInvoice() |

#### PDF Invoice Generation

Library: DOMPDF (Laravel wrapper)
Template: Blade template `/resources/views/billing/invoice-pdf.blade.php`
Storage: `storage/app/private/invoices/{year}/{invoice_number}.pdf`
Delivery: Email attachment via Brevo on issue

---

### 18.4 Revenue Reporting Architecture

#### Revenue Categories

| Category | Source | Billing Module |
|---|---|---|
| Training Revenue | Course purchases | billing_invoice_items.module = training |
| Membership Revenue | Subscription plans | billing_invoice_items.module = membership |
| Marketplace Revenue | Listing fees + commission | billing_invoice_items.module = marketplace |
| Consulting Revenue | Deposits + retainers | billing_invoice_items.module = consulting |
| Event Revenue | Registration fees | billing_invoice_items.module = event |

#### Key Revenue Metrics

| Metric | Definition | Calculation |
|---|---|---|
| Gross Revenue | Total invoiced amount | SUM(billing_invoices.total) WHERE status IN (issued, paid, overdue) |
| Net Revenue | Collected after refunds | SUM(billing_payments.amount) WHERE status = success — SUM(refunds) |
| MRR | Monthly Recurring Revenue | SUM(plan.price / billing_period_months) WHERE subscription.status = active |
| ARR | Annual Recurring Revenue | MRR × 12 |
| Outstanding AR | Unpaid issued invoices | SUM(billing_invoices.total) WHERE status IN (issued, overdue) |
| Churn Rate | Cancelled subscriptions | cancelled_this_month / active_start_of_month |
| ARPU | Avg Revenue Per User | Net Revenue / Active Users |

#### Revenue Analytics Schema

```
analytics_revenue_daily
  id
  date                    — DATE
  currency                — NGN | USD | etc.
  category                — training | membership | marketplace | consulting | event
  gross_revenue           — DECIMAL(14,2)
  net_revenue             — DECIMAL(14,2)
  refunds                 — DECIMAL(14,2)
  invoice_count           — INT
  payment_count           — INT
  created_at

analytics_mrr_snapshots
  id
  snapshot_date           — DATE (taken on 1st of each month)
  currency                — NGN | USD | etc.
  mrr                     — DECIMAL(14,2)
  arr                     — DECIMAL(14,2)
  active_subscriptions    — INT
  new_subscriptions       — INT
  cancelled_subscriptions — INT
  trial_subscriptions     — INT
  created_at

analytics_ar_aging
  id
  snapshot_date           — DATE
  currency
  current_amount          — DECIMAL(14,2)  (0–30 days)
  overdue_30_amount       — DECIMAL(14,2)  (31–60 days)
  overdue_60_amount       — DECIMAL(14,2)  (61–90 days)
  overdue_90_plus_amount  — DECIMAL(14,2)  (90+ days)
  created_at
```

#### Revenue Report Aggregation Schedule

| Report | Frequency | Source |
|---|---|---|
| Daily revenue snapshot | Daily 02:30 UTC | billing_invoices + billing_payments |
| MRR snapshot | 1st of month 03:00 UTC | billing_subscriptions + billing_plans |
| AR aging report | Weekly Monday 04:00 UTC | billing_invoices WHERE status != paid |
| Annual revenue summary | 1 Jan 04:00 UTC | analytics_revenue_daily (full year) |

#### Executive Dashboard — Billing Widgets

| Widget | Data Source |
|---|---|
| MRR (current month) | analytics_mrr_snapshots (latest) |
| MRR trend (12 months) | analytics_mrr_snapshots (12 rows, Chart.js line) |
| Revenue by category (this month) | analytics_revenue_daily (Chart.js doughnut) |
| Outstanding invoices | billing_invoices WHERE status = overdue |
| New subscriptions this month | billing_subscriptions (created_at this month) |
| Churn this month | cancelled_subscriptions / active start of month |

---

### 18.5 Billing Module — Database Prefix Summary

```
billing_plans                  — product and subscription plans
billing_subscriptions          — user subscription records
billing_invoices               — invoices (all types)
billing_invoice_items          — line items per invoice
billing_invoice_sequences      — invoice numbering per year per tenant
billing_payments               — payment transaction records
billing_webhooks               — gateway webhook log (append-only)
```

---

### 18.6 Cross-Module Billing Events

The Billing module communicates with source modules via Events (D-027).

| Event | Fired By | Billing Listener |
|---|---|---|
| CourseEnrolled | Training module | CreateCourseInvoice (if paid course) |
| ProposalAccepted | CRM module | CreateProposalInvoice |
| MarketplaceApplicationApproved | Marketplace | CreateMarketplaceFee (if applicable) |
| InvoicePaid | Billing module | ActivateSubscription, UnlockCourse, ConfirmRegistration |
| SubscriptionCancelled | Billing module | RevokeSubscriptionAccess, SendCancellationEmail |
| InvoiceOverdue | Billing module | SendOverdueAlert, SuspendSubscriptionAccess |

---

## 19. DATA WAREHOUSE ARCHITECTURE (D-032)

---

### 19.1 Two-Tier Analytics Design

The platform operates two distinct analytics tiers that serve different purposes.

```
OPERATIONAL DATA (Source Modules)
  crm_* | training_* | billing_* | research_* | startup_* | partner_*
  marketplace_* | client_*
         │
         │  Real-time writes
         ▼
TIER 1 — MODULE ANALYTICS (analytics_ tables)
  Module-level aggregations
  Updated: Cron every 15–60 minutes
  Serves: Module dashboards, manager reports
         │
         │  Nightly ETL (Laravel Scheduled Commands)
         ▼
TIER 2 — DATA WAREHOUSE (dw_ tables)
  Star schema: fact tables + dimension tables
  Updated: Nightly 03:00–07:00 UTC
  Serves: Executive Dashboard, cross-module BI, future BI tools
         │
         │  Phase 3: Direct BI connector
         ▼
FUTURE BI LAYER
  Metabase | Power BI | Google Looker Studio | BigQuery
```

**Rule:** No operational module queries `dw_` tables. No BI tool queries `crm_*`
or other operational tables directly. The warehouse is the single source of
truth for cross-module executive reporting.

---

### 19.2 Fact Tables

Fact tables record measurable business events. Each row is one event.

```
dw_fact_revenue
  id
  date_key              FK → dw_dim_date.date_key
  user_key              FK → dw_dim_user.user_key
  organisation_key      FK → dw_dim_organisation.org_key
  module_key            FK → dw_dim_module.module_key
  currency_key          FK → dw_dim_currency.currency_key
  source_type           — course | membership | consulting | marketplace | event
  source_id             — originating record ID
  gross_amount_ngn      DECIMAL(14,2) — normalised to NGN for aggregation
  net_amount_ngn        DECIMAL(14,2)
  tax_amount_ngn        DECIMAL(14,2)
  discount_amount_ngn   DECIMAL(14,2)
  loaded_at             TIMESTAMP

dw_fact_crm
  id
  date_key
  user_key              — assigned sales rep
  organisation_key      — client/lead organisation
  event_type            — lead_created | qualified | proposal_sent | won | lost
  pipeline_value_ngn    DECIMAL(14,2)
  deal_count            INT DEFAULT 1
  loaded_at

dw_fact_training
  id
  date_key
  user_key              — student
  course_key            FK → dw_dim_course.course_key
  event_type            — enrollment | lesson_completed | assessment_passed | course_completed | certified
  event_count           INT DEFAULT 1
  revenue_ngn           DECIMAL(14,2)
  loaded_at

dw_fact_projects
  id
  date_key
  organisation_key      — client
  project_key           FK → dw_dim_project.project_key
  event_type            — created | milestone_completed | delivered | closed
  on_time               TINYINT(1) NULL
  loaded_at

dw_fact_marketplace
  id
  date_key
  user_key
  organisation_key
  category_key          FK → dw_dim_category.category_key
  event_type            — listing_submitted | listing_published | application_submitted | application_accepted
  listing_count         INT DEFAULT 0
  application_count     INT DEFAULT 0
  loaded_at

dw_fact_startups
  id
  date_key
  startup_key           FK → dw_dim_startup.startup_key
  event_type            — registered | milestone_completed | program_completed
  event_count           INT DEFAULT 1
  loaded_at

dw_fact_partners
  id
  date_key
  partner_key           FK → dw_dim_partner.partner_key
  event_type            — referral_submitted | referral_converted | agreement_signed
  referral_count        INT DEFAULT 0
  conversion_count      INT DEFAULT 0
  revenue_ngn           DECIMAL(14,2) DEFAULT 0
  loaded_at

dw_fact_research
  id
  date_key
  publication_key       FK → dw_dim_publication.pub_key
  geography_key         FK → dw_dim_geography.geo_key
  event_type            — view | download | citation
  event_count           INT DEFAULT 1
  loaded_at
```

---

### 19.3 Dimension Tables

Dimension tables provide the descriptive context for fact table events.

```
dw_dim_date             — calendar dimension (pre-populated 2024–2035)
  date_key              INT PRIMARY KEY  (YYYYMMDD format, e.g. 20260601)
  full_date             DATE
  day_of_week           TINYINT
  day_name              VARCHAR(10)
  week_number           TINYINT
  month_number          TINYINT
  month_name            VARCHAR(10)
  quarter               TINYINT
  year                  SMALLINT
  is_weekend            TINYINT(1)
  fiscal_quarter        TINYINT
  fiscal_year           SMALLINT

dw_dim_user             — SCD Type 2 (history preserved on change)
  user_key              BIGINT AUTO_INCREMENT PRIMARY KEY
  source_user_id        BIGINT (core_users.id)
  full_name             VARCHAR
  email                 VARCHAR
  role_name             VARCHAR
  organisation_name     VARCHAR
  country_code          CHAR(2)
  status                VARCHAR
  effective_from        DATE
  effective_to          DATE NULL  (NULL = current record)
  is_current            TINYINT(1)

dw_dim_organisation     — SCD Type 2
  org_key               BIGINT AUTO_INCREMENT PRIMARY KEY
  source_org_id         BIGINT
  name                  VARCHAR
  type                  — client | partner | government | ngo | sme | startup
  country_code          CHAR(2)
  industry_sector       VARCHAR
  effective_from        DATE
  effective_to          DATE NULL
  is_current            TINYINT(1)

dw_dim_module           — SCD Type 1 (overwrite)
  module_key            TINYINT AUTO_INCREMENT PRIMARY KEY
  module_code           VARCHAR  (crm | training | marketplace | partner | startup | research | finance | project)
  module_name           VARCHAR
  is_active             TINYINT(1)

dw_dim_currency         — SCD Type 1
  currency_key          TINYINT AUTO_INCREMENT PRIMARY KEY
  currency_code         CHAR(3)  (NGN | USD | GBP | EUR)
  currency_name         VARCHAR
  symbol                VARCHAR

dw_dim_course           — SCD Type 2
  course_key            BIGINT AUTO_INCREMENT PRIMARY KEY
  source_course_id      BIGINT
  title                 VARCHAR
  category              VARCHAR
  instructor_name       VARCHAR
  price_ngn             DECIMAL(12,2)
  level                 VARCHAR  (beginner | intermediate | advanced)
  effective_from        DATE
  effective_to          DATE NULL
  is_current            TINYINT(1)

dw_dim_geography        — SCD Type 1
  geo_key               SMALLINT AUTO_INCREMENT PRIMARY KEY
  country_code          CHAR(2)
  country_name          VARCHAR
  region                VARCHAR  (West Africa | East Africa | North Africa | Southern Africa | Central Africa | International)
  is_african            TINYINT(1)

dw_dim_startup          — SCD Type 2
  startup_key           BIGINT AUTO_INCREMENT PRIMARY KEY
  source_startup_id     BIGINT
  name                  VARCHAR
  sector                VARCHAR
  program_type          VARCHAR  (general | incubator | accelerator)
  stage                 VARCHAR
  country_code          CHAR(2)
  effective_from        DATE
  effective_to          DATE NULL
  is_current            TINYINT(1)

dw_dim_partner          — SCD Type 2
  partner_key           BIGINT AUTO_INCREMENT PRIMARY KEY
  source_partner_id     BIGINT
  name                  VARCHAR
  tier                  VARCHAR  (bronze | silver | gold | platinum)
  country_code          CHAR(2)
  effective_from        DATE
  effective_to          DATE NULL
  is_current            TINYINT(1)

dw_dim_publication      — SCD Type 2
  pub_key               BIGINT AUTO_INCREMENT PRIMARY KEY
  source_publication_id BIGINT
  title                 VARCHAR
  category              VARCHAR
  access_level          VARCHAR
  publish_date          DATE
  effective_from        DATE
  effective_to          DATE NULL
  is_current            TINYINT(1)

dw_dim_project          — SCD Type 2
  project_key           BIGINT AUTO_INCREMENT PRIMARY KEY
  source_project_id     BIGINT
  name                  VARCHAR
  service_type          VARCHAR
  start_date            DATE
  target_end_date       DATE
  effective_from        DATE
  effective_to          DATE NULL
  is_current            TINYINT(1)

dw_dim_category         — SCD Type 1
  category_key          SMALLINT AUTO_INCREMENT PRIMARY KEY
  module_code           VARCHAR
  name                  VARCHAR
  parent_name           VARCHAR NULL
```

---

### 19.4 ETL Architecture

Each ETL command extracts delta changes (since last run) from source tables,
transforms them to warehouse grain, and loads into fact + dimension tables.

```
App\Console\Commands\DataWarehouse\
  LoadRevenueFacts          — billing_invoices + billing_payments → dw_fact_revenue
  LoadCRMFacts              — crm_leads + crm_opportunities → dw_fact_crm
  LoadTrainingFacts         — training_enrollments + training_completions → dw_fact_training
  LoadProjectFacts          — client_projects + client_milestones → dw_fact_projects
  LoadMarketplaceFacts      — marketplace_listings + marketplace_applications → dw_fact_marketplace
  LoadStartupFacts          — startup_profiles + startup_milestones → dw_fact_startups
  LoadPartnerFacts          — partner_referrals + partner_agreements → dw_fact_partners
  LoadResearchFacts         — research_downloads + research_publications → dw_fact_research
  RefreshDimensions         — updates all SCD Type 1 dimensions; inserts Type 2 versions
  SeedDateDimension         — one-time seed: populates dw_dim_date 2024–2040
```

ETL Schedule (all UTC):

| Command | Schedule | Window |
|---|---|---|
| LoadRevenueFacts | Daily 03:00 | ~5 min |
| LoadCRMFacts | Daily 03:15 | ~5 min |
| LoadTrainingFacts | Daily 03:30 | ~5 min |
| LoadProjectFacts | Daily 03:45 | ~3 min |
| LoadMarketplaceFacts | Daily 04:00 | ~5 min |
| LoadStartupFacts | Daily 04:15 | ~3 min |
| LoadPartnerFacts | Daily 04:30 | ~3 min |
| LoadResearchFacts | Daily 04:45 | ~3 min |
| RefreshDimensions | Weekly Sun 02:00 | ~15 min |

ETL metadata tracking:

```
dw_etl_runs
  id
  command               — LoadRevenueFacts | LoadCRMFacts | etc.
  started_at            TIMESTAMP
  completed_at          TIMESTAMP NULL
  rows_extracted        INT
  rows_loaded           INT
  rows_skipped          INT
  status                — running | success | failed
  error_message         TEXT NULL
```

---

### 19.5 Key Business Metrics by Module

#### CRM
| Metric | Fact Source |
|---|---|
| Total leads (period) | COUNT dw_fact_crm WHERE event_type = lead_created |
| Lead conversion rate | won / (won + lost) |
| Pipeline value | SUM pipeline_value_ngn WHERE event_type = qualified |
| Average deal size | AVG pipeline_value_ngn WHERE event_type = won |
| Win rate by rep | won / total_closed GROUP BY user_key |

#### Training
| Metric | Fact Source |
|---|---|
| Monthly enrollments | COUNT dw_fact_training WHERE event_type = enrollment |
| Completion rate | completed / enrolled |
| Certification rate | certified / completed |
| Revenue per course | SUM revenue_ngn GROUP BY course_key |
| Top courses | ORDER BY enrollment COUNT DESC |

#### Marketplace
| Metric | Fact Source |
|---|---|
| Active listings | COUNT dw_fact_marketplace WHERE event_type = listing_published |
| Application rate | applications / listings |
| Category distribution | COUNT GROUP BY category_key |

#### Partner
| Metric | Fact Source |
|---|---|
| Active partners | COUNT DISTINCT partner_key |
| Referral conversion rate | conversions / referrals |
| Partner-generated revenue | SUM revenue_ngn |
| Top partners | ORDER BY revenue_ngn DESC |

#### Finance
| Metric | Fact Source |
|---|---|
| MRR | analytics_mrr_snapshots (Tier 1) |
| Revenue by category | SUM gross_amount_ngn GROUP BY source_type |
| Revenue by geography | SUM gross_amount_ngn GROUP BY dw_dim_geography JOIN |
| YTD Revenue | SUM WHERE date_key BETWEEN year_start AND today |

#### Startups
| Metric | Fact Source |
|---|---|
| Active startups | COUNT DISTINCT startup_key |
| Milestone completion rate | milestones_completed / total_milestones |
| Program graduation rate | program_completed / registered |
| Sector distribution | COUNT GROUP BY dw_dim_startup.sector |

#### Research
| Metric | Fact Source |
|---|---|
| Total downloads | SUM event_count WHERE event_type = download |
| Geographic reach | COUNT DISTINCT geo_key WHERE event_type = download |
| Most downloaded reports | ORDER BY download count DESC |
| Publications by category | COUNT GROUP BY dw_dim_publication.category |

---

### 19.6 Executive Dashboard — Warehouse Widgets

The Executive Dashboard reads exclusively from `dw_` and `analytics_` tables.

```
Row 1 — Platform Health
  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────────┐
  │ MRR (this month)│  │ Active Users    │  │ Leads (30 days) │  │ Enrollments (30d)│
  └─────────────────┘  └─────────────────┘  └─────────────────┘  └──────────────────┘

Row 2 — Revenue
  ┌──────────────────────────────────────┐  ┌────────────────────────────────────────┐
  │ Revenue Trend (12 months) — Line     │  │ Revenue by Category (this month) — Pie │
  └──────────────────────────────────────┘  └────────────────────────────────────────┘

Row 3 — Operations
  ┌──────────────────────────┐  ┌──────────────────────────┐  ┌──────────────────────┐
  │ CRM Pipeline (funnel)    │  │ Training Completions (bar)│  │ Marketplace Activity │
  └──────────────────────────┘  └──────────────────────────┘  └──────────────────────┘

Row 4 — Growth
  ┌──────────────────────────────────────┐  ┌────────────────────────────────────────┐
  │ Geographic Revenue Map               │  │ Partner Performance (top 5 table)      │
  └──────────────────────────────────────┘  └────────────────────────────────────────┘
```

All charts rendered via Chart.js consuming /api/v1/analytics/warehouse/* endpoints.

---

### 19.7 Future BI Integration Path

The star schema is designed for direct connection to enterprise BI tools.

| Phase | Integration |
|---|---|
| Phase 2 (VPS) | Metabase (self-hosted, free tier) — connects directly to MySQL dw_ tables |
| Phase 3 (Cloud) | Power BI / Google Looker Studio — MySQL connector to cloud DB |
| Phase 3+ | Google BigQuery export — nightly export of dw_ tables for advanced analytics |

No schema changes required at any integration stage. The star schema works with
all listed tools out of the box.

---

## 20. COMMUNITY ARCHITECTURE (D-035)

---

### 20.1 Design Overview

The Community module provides public-facing profiles for all ecosystem participants.
It is the connective tissue across all domain modules — every user type that operates
on the platform has a corresponding community identity that can be discovered,
followed, and engaged with.

**Separation of concerns:**

```
Internal Record (module-owned)        Community Profile (member-owned)
─────────────────────────────         ────────────────────────────────
crm_leads / crm_contacts              community_profiles (consultant)
startup_profiles                      community_profiles (founder/startup)
training_instructors                  community_profiles (trainer)
partner_profiles                      community_profiles (partner)
research_authors                      community_profiles (researcher)
core_users                            community_profiles (any type)
```

One user may have one community profile. The community profile is public-facing
and member-controlled. Internal records are ICS-controlled. They are linked by
FK but governed independently.

---

### 20.2 Class Table Inheritance Schema

#### Base Table (all profile types)

```
community_profiles
  id
  tenant_id
  user_id               FK → core_users.id (UNIQUE — one profile per user)
  profile_type          — founder | startup | consultant | trainer | partner | researcher
  display_name
  tagline               — short professional headline (max 120 chars)
  bio                   — translatable longer description
  avatar_path
  cover_image_path
  website_url
  location_country      CHAR(2)
  location_city
  linkedin_url
  twitter_url
  visibility            — public | authenticated  (Phase 2: + connections_only)
  is_verified           TINYINT(1) DEFAULT 0
  verified_at           TIMESTAMP NULL
  verified_by           FK → core_users.id NULL (ICS staff who verified)
  view_count            INT DEFAULT 0
  follower_count        INT DEFAULT 0  (future social graph)
  status                — active | suspended | hidden
  created_at
  updated_at
  deleted_at
```

#### Extension Tables (type-specific attributes)

```
community_founder_profiles
  id
  profile_id            FK → community_profiles.id UNIQUE
  startup_id            FK → startup_profiles.id NULL
  stage                 — idea | mvp | growth | scale | exit
  industries            JSON
  seeking               JSON  — [funding, mentorship, partnerships, talent, customers]
  years_experience      TINYINT
  created_at
  updated_at

community_startup_profiles
  id
  profile_id            FK → community_profiles.id UNIQUE
  startup_id            FK → startup_profiles.id NULL
  founding_year         SMALLINT
  team_size             TINYINT
  stage                 — idea | mvp | growth | scale | exit
  industry              VARCHAR
  business_model        — b2b | b2c | b2g | marketplace | saas | ngo | other
  seeking               JSON  — [funding, partnerships, clients, talent]
  created_at
  updated_at

community_consultant_profiles
  id
  profile_id            FK → community_profiles.id UNIQUE
  expertise_areas       JSON  — from Knowledge Center categories (D-033)
  years_experience      TINYINT
  certifications        JSON
  languages             JSON
  availability          — available | limited | unavailable
  engagement_types      JSON  — [freelance, retainer, project_based, advisory]
  created_at
  updated_at

community_trainer_profiles
  id
  profile_id            FK → community_profiles.id UNIQUE
  instructor_id         FK → training_instructors.id NULL
  specializations       JSON  — subject areas
  certifications        JSON
  delivery_modes        JSON  — [online, in_person, hybrid]
  years_experience      TINYINT
  courses_count         INT DEFAULT 0  — cached from Training module
  created_at
  updated_at

community_partner_profiles
  id
  profile_id            FK → community_profiles.id UNIQUE
  partner_id            FK → partner_profiles.id NULL
  organisation_name     VARCHAR
  partnership_types     JSON  — [technology, referral, implementation, reseller]
  service_areas         JSON
  coverage_regions      JSON  — African regions + International
  created_at
  updated_at

community_researcher_profiles
  id
  profile_id            FK → community_profiles.id UNIQUE
  author_id             FK → research_authors.id NULL
  institution           VARCHAR
  research_areas        JSON  — from Research Center categories (D-030)
  academic_degree       VARCHAR
  orcid_id              VARCHAR NULL
  publications_count    INT DEFAULT 0  — cached from Research module
  created_at
  updated_at
```

#### Skills & Endorsements

```
community_skills
  id
  name
  slug
  category              — Technology | Consulting | Training | Research | Business
  created_at

community_profile_skills
  id
  profile_id            FK → community_profiles.id
  skill_id              FK → community_skills.id
  endorsement_count     INT DEFAULT 0  — cached
  created_at
  UNIQUE KEY (profile_id, skill_id)

community_endorsements
  id
  profile_id            FK → community_profiles.id
  skill_id              FK → community_skills.id
  endorsed_by_id        FK → community_profiles.id
  created_at
  UNIQUE KEY (profile_id, skill_id, endorsed_by_id)
```

---

### 20.3 API Endpoints

```
GET    /api/v1/community/profiles               — paginated public directory
GET    /api/v1/community/profiles/{slug}        — single profile (visibility check)
POST   /api/v1/community/profile               — create own profile (auth required)
PUT    /api/v1/community/profile               — update own profile
DELETE /api/v1/community/profile               — deactivate own profile

GET    /api/v1/community/profiles?type=founder
GET    /api/v1/community/profiles?type=consultant
GET    /api/v1/community/profiles?country=NG
GET    /api/v1/community/profiles?skill=digital-transformation

POST   /api/v1/community/profile/skills/{skill_id}/endorse
DELETE /api/v1/community/profile/skills/{skill_id}/endorse

GET    /api/v1/admin/community/profiles         — admin view (all statuses)
POST   /api/v1/admin/community/profiles/{id}/verify
POST   /api/v1/admin/community/profiles/{id}/suspend
```

---

### 20.4 Profile Discovery (Community Directory)

The Community Directory is a public-facing, filterable, searchable index of
all active verified and public profiles.

Filters:
- Profile type (founder, consultant, trainer, partner, researcher)
- Country / region
- Industry / expertise area
- Availability (consultants, trainers)
- Verification status
- Startup stage (founders)

Search:
- Phase 1: MySQL FULLTEXT on display_name, tagline, bio
- Phase 2: AI-powered semantic search (extends D-029 KnowledgeSearchService pattern)

Ordering:
- Verified profiles ranked above unverified
- Recency of activity
- Completeness of profile

---

### 20.5 Future Feature Database Seams

All future feature tables use the `community_` prefix. No schema migration
of existing tables required when these features are added.

#### Discussion Forums
```
community_forums          — forum categories (Digital Transformation, AI, etc.)
community_threads         — discussion threads within forums
community_posts           — posts within threads
community_post_reactions  — emoji reactions on posts
community_thread_follows  — users following threads for notifications
```

#### Mentorship Matching
```
community_mentorship_requests
  mentor_profile_id    FK → community_profiles.id
  mentee_profile_id    FK → community_profiles.id
  focus_areas          JSON
  status               — pending | accepted | declined | ended
  matched_by           — manual | ai (uses D-029 OpportunityMatchingService pattern)

community_mentorship_sessions
  mentorship_id        FK → community_mentorship_requests.id
  scheduled_at         TIMESTAMP
  notes                TEXT
  status               — scheduled | completed | cancelled
```

#### Event Registration
```
community_events
  title, description, event_type (online|in_person|hybrid)
  start_datetime, end_datetime
  capacity, registration_fee_ngn
  billing_plan_id      FK → billing_plans.id NULL (paid events via D-031)

community_event_registrations
  event_id, user_id, status, payment_status
  invoice_id           FK → billing_invoices.id NULL
```

#### Collaboration Requests
```
community_collaborations
  requester_profile_id    FK → community_profiles.id
  target_profile_id       FK → community_profiles.id
  type                    — project | research | partnership | mentorship
  description             TEXT
  status                  — pending | accepted | declined | completed
  notification delivered via D-022 notification system
```

#### Opportunity Sharing
No new table required. Marketplace listings (D-011) gain a
`shared_by_profile_id FK → community_profiles.id` column.
Community profile page shows shared opportunities from that member.

---

### 20.6 Events Fired by Community Module

| Event | Listeners |
|---|---|
| ProfileCreated | NotifyWelcome, LogAuditEvent, (CRM: CreateLeadIfConsultant) |
| ProfileVerified | NotifyMember, UpdateDirectoryIndex |
| ProfileUpdated | UpdateDirectoryIndex, LogAuditEvent |
| SkillEndorsed | NotifyProfileOwner, UpdateEndorsementCount |
| ProfileSuspended | NotifyMember, RemoveFromDirectory, LogAuditEvent |

---

### 20.7 CRM Integration — Consultant Lead Capture

When a Consultant Profile is created (ProfileCreated event, type=consultant):
- CRM Listener: CreateCRMLead fires
- Creates a `crm_leads` record with source = community_profile
- Assigns to ICS CRM staff for follow-up
- This surfaces self-registered consultants as warm leads in the CRM pipeline

This is the ONLY cross-module write from Community → CRM.
All other Community ↔ module communication is read-only or Event-based.

---

## APPENDIX A — DECISION REFERENCE

| Decision | Subject |
|---|---|
| D-001 | Platform-first architecture |
| D-002 | Technology stack (PHP 8.3, MySQL 8+, Tailwind, Alpine) |
| D-003 | Hosting strategy (Hostinger P1 → VPS P2 → Cloud P3) |
| D-004 | Multi-tenancy model (single platform, tenant-aware) |
| D-005 | PWA mobile strategy |
| D-006 | Compliance (NDPA, GDPR, ISO 27001, OWASP) |
| D-007 | Data residency (EU primary) |
| D-008 | Monetization strategy |
| D-009 | SLO (99.9% from Phase 2) |
| D-010 | Ecosystem operating model (not a module) |
| D-011 | Marketplace access and workflow |
| D-012 | CRM scope (internal only) |
| D-013 | Central analytics layer |
| D-014 | i18n strategy (EN → FR → AR/RTL) |
| D-015 | Organizational identity (not a web agency) |
| D-016 | Audience priority (Government first) |
| D-017 | Strategic mission (Africa's leading ecosystem) |
| D-018 | Competitive positioning |
| D-019 | Future expansion roadmap (7 modules) |
| D-020 | Laravel 11 framework |
| D-021 | RBAC authentication (Spatie + Sanctum) |
| D-022 | Notification architecture (Mail + WhatsApp + In-App) |
| D-023 | API-first (/api/v1/) |
| D-024 | Storage architecture (Flysystem local → S3) |
| D-025 | Central analytics architecture |
| D-026 | Gemini AI architecture |
| D-027 | Event-driven architecture (Events + Listeners) |
| D-028 | WCAG 2.1 Level AA — mandatory accessibility standard |
| D-029 | Gemini AI — 10 approved use cases |
| D-030 | Research Center — approved scope |
| D-031 | Payment gateway + Subscription, Invoice, Revenue Reporting architecture |
| D-032 | Data Warehouse strategy — star schema, ETL, two-tier analytics |
| D-033 | Knowledge Center — approved scope (15 categories, 11 types, 7 features) |
| D-034 | Research Center — 5-tier access model + monetization upgrade path |
| D-035 | Community architecture — 6 profile types, future feature seams |
| D-036 | Knowledge Center — 5-tier lateral access model + monetization path |
| D-037 | VPS-ready architecture, shared-hosting-first deployment (config-only migration) |
| D-038 | Unified content engine (CMS/Knowledge/Research shared logic) |
| D-039 | Security hardening baseline |
| D-040 | Community CTI + 14-role model retained (simplifications declined) |
