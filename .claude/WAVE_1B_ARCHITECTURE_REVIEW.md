# WAVE 1B ARCHITECTURE REVIEW — UNIFIED CONTENT ENGINE
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Architecture/Design — Awaiting Approval (no engine/CMS/CRM code yet)
Author: Chief Enterprise Architect
Decision References: D-014, D-028, D-030, D-033, D-034, D-036, D-037, D-038

Interpretation note: the named deliverable is an ARCHITECTURE REVIEW and the
instruction is "wait for approval after Wave 1b review" — so this is the Wave 1b
DESIGN; implementation (traits, service, migration, seams) follows approval.

---

## EXECUTIVE SUMMARY

Wave 1b designs the Unified Content Engine (D-038): one implementation of content
lifecycle, full-text search, tiered access, and engagement tracking, reused by CMS,
Knowledge Center, and Research Center. A change to content policy is made once, not
three times. The engine is **tier-scoped via ContentAccessService** and is explicitly
**separate from AccountScope** (org isolation, W1-3). Verdict: **SOUND DESIGN —
proceed to Wave 1b implementation after approval.**

Components: `HasContentLifecycle` (trait), `HasFullTextSearch` (trait),
`ContentAccessService` (one service, two strategies), `content_engagement_events`
(unified append-only table), and three thin integration seams.

---

## 1. CONTENT LIFECYCLE REVIEW — `HasContentLifecycle`

Shared trait for every content model (content_articles, knowledge_articles,
research_publications).

States: `draft → under_review → published → archived`.

Provided:
- `status` enum + transitions: `submitForReview()`, `publish()`, `archive()`.
- Slug: auto-generated from title, unique per model; immutable after publish.
- SEO: `seo_title`, `seo_description`, `published_at`.
- Scope: `published()` (status=published AND published_at <= now).
- Events: `ContentPublished`, `ContentArchived` (audited via the subscriber).
- **Human approval (P-1):** `publish()` is gated by the module policy permission
  (e.g. `knowledge.articles.publish`); no auto-publish.

Rationale: identical lifecycle for all content; no per-module reimplementation (D-038).

---

## 2. ACCESS CONTROL REVIEW — `ContentAccessService`

ONE service evaluating BOTH approved tier patterns, selected by a strategy flag on
the content/model. Replaces the separately-designed Research/Knowledge access
services (Sprint 1 blueprint §4.6) with a single implementation (D-038).

```
ContentAccessService::canAccess(?User $user, ContentAccessible $content): bool
  strategy = $content->accessStrategy()      // 'hierarchical' | 'lateral'
  tier     = $content->accessTier()          // 1..5

  HIERARCHICAL (Research, D-034):  userTier(user) >= tier
  LATERAL      (Knowledge, D-036): role-switch — tiers 1/2 additive; 3 = client;
                                   4 = partner; 5 = internal (ICS staff)
  Draft override: non-published content → ICS staff only.

canDownload(user, content): canAccess(...) AND content has a file
```

- `ContentAccessible` interface: `accessStrategy()`, `accessTier()`, `module()`.
- Guest = tier 1; abstracts/summaries always public (SEO) regardless of tier.
- **Subscription tier elevation (D-034/D-036 monetisation):** a future hook —
  `userTier()` will also consider an active billing subscription (D-031) without a
  schema change.
- **Separate from AccountScope (W1-3 / requirement 2):** content is NOT org-owned;
  access is by tier, never by account. The two mechanisms never mix.

---

## 3. SEARCH ARCHITECTURE REVIEW — `HasFullTextSearch`

- Trait declares searchable columns: `toSearchableColumns(): array` (e.g. title,
  excerpt/abstract, body).
- Phase 1: MySQL 8 `FULLTEXT` index + `MATCH(...) AGAINST(... IN NATURAL LANGUAGE
  MODE)`; a `search(string $term)` query scope; mandatory pagination.
- Driver is config-driven (`config('ics.search.driver')` = `fulltext` | `scout`):
  Phase 2 swaps to Laravel Scout + Meilisearch with NO code change in modules
  (D-037 pattern).
- Public search results cached + fronted by Cloudflare (PERF-01).

---

## 4. PERFORMANCE REVIEW

| Concern | Design |
|---|---|
| FULLTEXT on shared hosting | indexed; paginated; cached; Cloudflare edge cache; Meilisearch Phase 2 |
| Engagement volume | `content_engagement_events` append-only; bulk insert; prune via retention (SCAL-03); partition by month on VPS |
| Tier checks | in-memory role/tier comparison; no extra query beyond the content row |
| N+1 | services eager-load authors/categories; counters cached on the content row |
| Translations (Phase 2) | translation reads cached/eager-loaded (R-7) — see §8 |

No Phase 1 performance blocker; growth items have documented mitigations.

---

## 5. CMS DEPENDENCY REVIEW

- Tables: `content_pages`, `content_articles`, `content_media`.
- Uses `HasContentLifecycle` + `HasFullTextSearch`. **Not** org-owned; **not**
  tier-gated beyond public (CMS is public/SEO content) — ContentAccessService may
  treat CMS as tier 1 (public) with the lifecycle controlling visibility.
- Events: `ContentPublished`. WCAG 2.1 AA (D-028); i18n-ready (§8).
- First consumer — validates the engine abstraction before Knowledge/Research.

## 6. KNOWLEDGE CENTER DEPENDENCY REVIEW

- Table: `knowledge_articles` (15 types, D-033). Strategy = **lateral** (D-036:
  tiers 1/2 additive; 3 client; 4 partner; 5 internal).
- Uses lifecycle + search + `ContentAccessService` (lateral) + engagement events.
- Seam for AI Knowledge Search (D-029 #5) and AI Content Drafting (#10) — event/
  service hooks, AI logic in the AI sprint.

## 7. RESEARCH CENTER DEPENDENCY REVIEW

- Table: `research_publications` (D-030). Strategy = **hierarchical** (D-034:
  user_tier >= content_tier; abstract always public).
- Uses lifecycle + search + `ContentAccessService` (hierarchical) + engagement
  (views/downloads/citations) + citation generation (APA/Chicago/IEEE).
- Seam for AI Research Assistant (D-029 #6).

---

## 8. FUTURE TRANSLATION COMPATIBILITY REVIEW (D-014)

- The engine is designed so a `HasTranslations` concern (R-7, Phase 2) layers on
  without rework: translatable fields (title, excerpt/abstract, body) resolve via
  `i18n_translations` keyed by (model, id, locale, field).
- Read path: `content->translate(field, locale)` → requested locale → fallback
  locale → base field (the dynamic-content fallback chain, R-8) — never empty.
- Phase 1 English uses base columns directly; the translation layer is dormant
  (D-037: built later, no schema change to add FR/AR).
- Slugs and SEO fields are translatable too; URL strategy per locale is a Phase 2
  decision (no schema impact).

---

## UNIFIED ENGAGEMENT TABLE (supersedes per-module tables — D-038)

```sql
content_engagement_events (
  id            BIGINT UNSIGNED PK,
  tenant_id     BIGINT UNSIGNED NULL,
  content_type  VARCHAR(100) NOT NULL,   -- polymorphic model class
  content_id    BIGINT UNSIGNED NOT NULL,
  event_type    ENUM('view','download','citation') NOT NULL,
  user_id       BIGINT UNSIGNED NULL,
  session_id    VARCHAR(64) NULL,        -- guest dedup
  ip_address    VARCHAR(45) NULL,
  country_code  CHAR(2) NULL,
  referrer_url  VARCHAR(500) NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- append-only
  KEY (content_type, content_id), KEY (event_type), KEY (created_at)
)
```
This replaces the separately-blueprinted `knowledge_views`, `knowledge_downloads`,
and `research_downloads` (D-038 unification). Cached counters remain on the content
rows; the events table is the analytics source. **Blueprint reconciliation required**
at implementation (note in §findings).

---

## FINDINGS

| ID | Finding | Severity |
|---|---|---|
| W1b-1 | Adopting `content_engagement_events` supersedes knowledge_views/knowledge_downloads/research_downloads — reconcile DATABASE_BLUEPRINT at implementation (D-038) | MEDIUM (doc) |
| W1b-2 | One `ContentAccessService` replaces the Sprint-1 module-specific access services — confirm the consolidation | MEDIUM (design) |
| W1b-3 | CMS access semantics: treat as tier-1 public + lifecycle-gated (not tiered) — confirm | LOW |
| W1b-4 | Search driver swap (FULLTEXT→Meilisearch) must stay config-only — enforce in implementation | LOW |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Single content engine (no triplication, D-038) | ✅ design |
| ContentAccessService ≠ AccountScope (separate) | ✅ |
| Both tier patterns (hierarchical + lateral) in one service | ✅ |
| Lifecycle / search / engagement / translation seams designed | ✅ |
| CMS/Knowledge/Research seams defined (not implemented) | ✅ |
| CMS / CRM NOT implemented | ✅ |

---

## REVIEW VERDICT

**SOUND DESIGN.** The Unified Content Engine consolidates lifecycle, search, tiered
access, and engagement into one implementation with three thin seams, separate from
org isolation, and translation-ready. Cleared to proceed to **Wave 1b implementation**
after approval (then Wave 1c CMS as the first consumer).

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do not implement CMS or CRM.** On approval, Wave 1b
implementation: traits + ContentAccessService + content_engagement_events migration +
the three integration seams (no module business logic yet).
