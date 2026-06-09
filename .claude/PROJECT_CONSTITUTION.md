# ICS ENTERPRISE ECOSYSTEM PLATFORM — PROJECT CONSTITUTION

Version: 2.0
Date: 2026-05-29
Status: Governing Document

---

## PART I — ORGANIZATIONAL IDENTITY

### 1.1 Who ICS Is

ICS is a Technology, Consulting, Capacity Development, and Innovation Organization.

ICS is NOT a web design agency.
ICS is NOT a creative agency.
ICS is NOT a freelance services marketplace.

Every decision, module, interface, tone, and feature of this platform must
reflect an enterprise-grade, institution-level organization.

### 1.2 Strategic Mission

Become Africa's leading digital transformation, technology consulting, innovation,
and capacity development ecosystem.

### 1.3 Core Service Lines

- Digital Transformation Consulting
- Enterprise Technology Integration
- Capacity Development & Training
- Innovation Programs (Incubation, Acceleration)
- AI & Data Solutions
- Managed Technology Services
- Government & Public Sector Advisory
- Research & Knowledge Production

### 1.4 Organizational Positioning

ICS competes against:

- Global and regional consulting firms
- Technology integrators and systems integrators
- Digital transformation firms
- Professional training and certification institutes
- Innovation hubs and incubation centers

ICS does NOT compete primarily with web design agencies or creative studios.
Platform design, module scope, content tone, and feature priorities must reflect this.

---

## PART II — PRIMARY AUDIENCE

Priority order governs all UX, feature, compliance, and content decisions.
Higher-priority audiences receive first-class treatment in every module.

1. Government Agencies (national, state, local, regional bodies)
2. International Organizations (UN agencies, development banks, multilaterals)
3. Corporate Enterprises (large private sector organizations)
4. NGOs and Civil Society Organizations
5. SMEs (Small and Medium Enterprises)
6. Startups (early-stage ventures)
7. Individuals (professionals, students, practitioners)

### Audience Implications (Governing Rules)

- Government-readiness is a baseline requirement, not an optional feature.
- WCAG 2.1 accessibility compliance is mandatory (government procurement standard).
- French language (Phase 2) is strategic, not cosmetic — Francophone Africa is a primary government market.
- Formal tone, institutional credibility, and audit-grade documentation are non-negotiable.
- Platform must support procurement language, formal agreements, and structured reporting.

---

## PART III — PLATFORM SCOPE

### 3.1 Current Approved Modules

1.  Corporate Website
2.  CRM (Internal Enterprise)
3.  Client Portal
4.  Startup Hub
5.  Partner Portal
6.  Training Institute (LMS)
7.  Opportunity Marketplace
8.  Knowledge Center
9.  Research Center
10. AI Services
11. Analytics Layer
12. Subscription Module (Phase 2)
13. Community Module (D-035) — Founder, Startup, Consultant, Trainer,
    Partner, Researcher profiles; connective tissue across all domain modules

### 3.2 Future Expansion — Reserved Architecture

The following modules are on the approved future roadmap.
Architecture must not block their addition. Database and module boundaries
must reserve space for these from day one.

13. LMS (standalone or Training Institute extension — to be defined)
14. Vendor Marketplace
15. Membership System (extends Subscription Module)
16. Incubator Program (extends Startup Hub)
17. Accelerator Program (extends Startup Hub)
18. Investment Network (new — investor-startup connectivity)
19. Franchise Operations (new — requires tenant-aware architecture)

### 3.3 Architecture Rule for Future Modules

No current module may be designed in a way that structurally prevents
a future module from being added. Violating this rule requires a formal
architecture review and approved Decision Log entry before proceeding.

---

## PART IV — GLOBAL COMPLIANCE DIRECTIVE

This directive applies to every future task, prompt, request, feature, module,
architecture decision, code generation activity, refactoring activity, and
documentation activity.

Before generating any response, Claude must:

1. Review all previously approved project decisions.
2. Review the Project Constitution.
3. Review approved architecture documents.
4. Review approved database designs.
5. Review approved workflows.
6. Validate consistency with existing project standards.
7. Identify conflicts or contradictions.
8. Identify architectural risks.
9. Identify scalability concerns.
10. Identify security concerns.
11. Recommend corrections where required.

If a request conflicts with any approved project standard:

- Do not proceed immediately.
- Explain the conflict.
- Explain the consequences.
- Propose compliant alternatives.
- Await approval before continuing.

Claude must never:

- Ignore previous project decisions.
- Create duplicate functionality.
- Generate code that bypasses architecture.
- Create undocumented features.
- Introduce technical debt.
- Sacrifice security for convenience.
- Break established naming conventions.
- Create orphan database tables.
- Generate features outside the approved roadmap without explicitly
  identifying them as roadmap extensions.
- Design any feature that treats ICS as a web agency.
- Ignore audience priority order when making UX or feature decisions.

---

## PART V — GOVERNING PRINCIPLES

### P-1: Enterprise Credibility First
Every interface, feature, and piece of content must project institutional credibility
appropriate for government, international organization, and enterprise clients.
The Knowledge Center and Research Center are the public face of ICS's intellectual
authority. No content is auto-published — human editorial approval is required on
all content types in both modules. Quality of published content represents the ICS brand.

### P-2: Africa-Scale Thinking
Architecture decisions must support continental-scale operations. No regional
hardcoding. Multi-language, multi-currency, and multi-timezone readiness by design.

### P-3: Government-Ready by Default
WCAG 2.1 Level AA accessibility, audit trails, formal documentation, procurement support,
and data sovereignty controls are baseline requirements, not optional enhancements.
Every UI component must pass WCAG 2.1 AA before it is accepted. No exceptions.

### P-4: Security Without Compromise
No feature, deadline, or convenience justifies weakening the security model.
NDPA, GDPR, ISO 27001, and OWASP compliance are non-negotiable.

### P-5: Architecture Before Code
No development begins without an approved architecture decision. Technical debt
introduced by skipping architecture costs more than the time saved.

### P-6: Scalability by Design
Every component is designed to survive growth from 100 users to 100,000 users
without requiring structural rewrites. Phase-based scaling is pre-planned, not reactive.

### P-7: Future Modules Must Fit
Every current design decision must leave clean seams for the 7 future modules
defined in Part III. A decision that blocks future expansion is a defect.

### P-9: Community Profiles Are Public-Facing — Not Internal Records
Community profiles (community_profiles) are public-facing identities for ecosystem
participants. They are distinct from internal operational records in the CRM, Partner
Portal, Training Institute, and Research Center. A single user may have both an
internal record and a community profile. The two must never be merged. Internal records
are managed by ICS staff; community profiles are owned and controlled by the member.

### P-10: VPS-Ready Architecture, Shared-Deployable Runtime (D-037)
The architecture is VPS-first and remains intact regardless of where it is deployed.
Initial deployment runs on Hostinger Premium Shared Hosting; migration to VPS must
require CONFIGURATION CHANGES ONLY — no database redesign, no application redesign,
no code rewrites.

This imposes three binding design rules on every module:
  1. No infrastructure driver name (queue, cache, session, filesystem, mail) may be
     hardcoded. All are resolved from config()/.env.
  2. Every heavy or non-instant Listener implements ShouldQueue. On shared hosting
     it runs via the database/cron queue; on VPS the same code runs async on Redis.
     The switch is a .env value, never a code change.
  3. Capabilities deferred to VPS (Redis, persistent workers, Data Warehouse ETL
     automation, heavy background jobs, advanced event processing, community scaling,
     high-volume AI) are BUILT in full (schema + code) and gated OFF by env feature
     flags on shared hosting. Deferral is a runtime switch, not a missing feature.

The Data Warehouse (D-032), i18n architecture (D-014), and tenant-ready design
(D-004) are part of the intact architecture. Their schemas exist from the first
migration; only their runtime automation is phased. They are never removed to
"simplify" a shared-hosting deployment.

### P-8: Tiered Content Access Must Be Monetization-Ready
Modules with tiered content access (Research Center D-034, Knowledge Center D-036,
and future modules) must design their access gates to accept both role-based and
subscription-based tier elevation from day one. Adding paid content tiers later
must never require a schema migration or architectural redesign. The billing layer
(D-031) is the designated mechanism for subscription-based tier elevation.

Access Gate Patterns — two approved variants:
  Hierarchical (Research Center): user_tier >= content_tier. Each higher role
  includes all lower tier content. Use when tiers are strictly additive.
  Lateral (Knowledge Center): role-switch logic. Tiers 3 and 4 are parallel
  and role-specific. Use when audience segments receive different content
  at the same privilege level. ICS Staff always access all tiers in both patterns.

---

## PART VII — FINALIZED TECHNOLOGY ARCHITECTURE

### 7.1 Approved Technology Stack

| Layer | Technology | Ref |
|---|---|---|
| Backend Framework | Laravel 11 | D-020 |
| Language | PHP 8.3 | D-002 |
| Database | MySQL 8+ | D-002 |
| Templating Engine | Laravel Blade | D-020 |
| CSS Framework | Tailwind CSS | D-002 |
| JavaScript | Alpine.js + Vanilla JS | D-002 |
| API Authentication | Laravel Sanctum | D-021 |
| RBAC | Spatie Laravel-Permission | D-021 |
| File Storage | Laravel Flysystem | D-024 |
| Notifications | Laravel Notifications | D-022 |
| Mobile | Progressive Web App (PWA) | D-005 |
| AI | Google Gemini API | D-026 |
| Email | Brevo SMTP/API | D-022 |
| Messaging | WhatsApp Business API | D-022 |
| Analytics UI | Chart.js | D-025 |
| Accessibility Standard | WCAG 2.1 Level AA | D-028 |

### 7.2 Approved Architectural Patterns

All development must conform to these patterns without exception:

| Pattern | Requirement |
|---|---|
| Modular Monolith | Clean module boundaries; each module independently extractable |
| API-First | All business logic exposed via /api/v1/; frontend consumes API |
| Event-Driven | Cross-module communication via Laravel Events only |
| Repository | Database access abstracted behind repository or service layer |
| RBAC + Policy | All authorization enforced server-side via Gates and Policies |
| Tenant-Aware | tenant_id on all core business tables from first migration |
| i18n-First | All user-facing strings routed through translation layer |
| Queue-First | All background work dispatched to job queue; never inline |
| Accessibility-First | All UI components designed to WCAG 2.1 AA from the first component |
| Config-Driven Runtime | No driver hardcoded; all infra resolved from .env; heavy listeners ShouldQueue (D-037) |
| Unified Content Engine | CMS/Knowledge/Research share lifecycle, search, access, engagement logic — never triplicated (D-038) |
| Secure-by-Baseline | D-039 hardening baseline applied: .env off web root, app-layer audit immutability, AI PII redaction, Cloudflare, SMTP fallback |

### 7.3 Non-Negotiable Architecture Rules

Violation of any of these rules requires a formal architecture review and
approved amendment before the code is accepted.

1. No direct cross-module database queries. Cross-module data flows via Events.
2. No business logic in controllers. Controllers handle request/response only.
3. No raw SQL queries. Eloquent ORM with parameterized queries exclusively.
4. No secrets in codebase. All credentials in .env; .env excluded from git.
5. No frontend permission checks. All authorization enforced on the server.
6. All tables carry tenant_id (nullable Phase 1, enforced Phase 3+).
7. All translatable content routed through the i18n layer; no hardcoded strings.
8. All file uploads processed through Laravel Storage facade exclusively.
9. All background processing via job queue; no synchronous long operations.
10. All cross-module analytics queries target aggregation tables, not source tables.
11. All UI components must pass WCAG 2.1 Level AA before acceptance. No exceptions.

---

## PART VI — AMENDMENT PROCEDURE

Amendments to this constitution require:

1. A formal request with justification.
2. An impact assessment against all approved decisions.
3. An explicit approval before any implementation change.
4. A new or updated Decision Log entry.
5. An update to PROJECT_MEMORY.md.

Minor clarifications may be added without full amendment review.
Structural changes to Parts I through V require full review.
