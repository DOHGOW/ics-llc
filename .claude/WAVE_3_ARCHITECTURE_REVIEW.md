# WAVE 3 ARCHITECTURE REVIEW — KNOWLEDGE CENTER + RESEARCH CENTER
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-01
Status: Architecture / Design — Awaiting Approval (NO Wave 3 code in this wave)
Author: Lead Architect
Decision References: D-014, D-025, D-029, D-033, D-034, D-036, D-038, D-044, D-046, D-051
Scope under review: Knowledge Center (knowledge_categories, knowledge_articles,
knowledge_tags, knowledge_article_tags, knowledge_bookmarks, knowledge_ratings,
knowledge_related) and Research Center (research_categories, research_authors,
research_publications, research_publication_authors, research_citations)

Interpretation: the deliverable is an ARCHITECTURE REVIEW with an explicit "do not
implement Wave 3 yet / wait for approval after the review" gate. This is the Wave 3 DESIGN;
implementation follows approval.

---

## ⚠ THE DEFINING VALIDATION OF THIS WAVE

Wave 3 is the moment the **Unified Content Engine (D-038/D-051) proves itself against BOTH
access patterns at once**. The CMS (Wave 1c) exercised only LATERAL tier-1. Wave 3 plugs
two new consumers into the SAME `ContentAccessService`, selected purely by a strategy flag:

| Center | Strategy | Decision | Semantics |
|---|---|---|---|
| **Knowledge** | LATERAL | D-036 | tiers 3/4 are PARALLEL (3=CLIENT, 4=PARTNER) — role-switch, not `>=` |
| **Research** | HIERARCHICAL | D-034 | tiers stack (`user_tier >= content_tier`); 3=partner, 4=internal, 5=admin |

Both `HierarchicalAccessStrategy` and `LateralAccessStrategy` **already exist** (built in
Wave 1b, D-051) and already encode these rules. Wave 3 implements the models/controllers
that consume them — it does NOT add a third access service. The retired
`KnowledgeAccessService`/`ResearchAccessService` stay retired. **This is the payoff of the
D-051 consolidation.**

---

## EXECUTIVE SUMMARY

Wave 3 delivers the two content libraries: the **Knowledge Center** (practical, SEO-driven,
operational resources — D-036 lateral tiers) and the **Research Center** (formal, citable,
institutional publications — D-034 hierarchical tiers). Both reuse `HasContentLifecycle` +
`HasFullTextSearch` + `ContentAccessService` (no new engine, D-038), are i18n-ready (D-014,
no schema change), feed the unified `content_engagement_events` analytics (D-051/D-025), and
are AI-ready (D-029 seams present, implementation deferred). Two items need attention before
implementation: a **real audit-module bug** (W3-2) and the **public-teaser projection** rule
(W3-3). Verdict: **SOUND, conditional on those two.** No code this wave; **no new decisions
required** — Wave 3 is clean reuse.

---

## 1. KNOWLEDGE CENTER REVIEW (D-036)

Tables: knowledge_categories (hierarchical, parent_id), knowledge_articles, knowledge_tags
(+ knowledge_article_tags M2M), knowledge_bookmarks, knowledge_ratings, knowledge_related.

| Aspect | Design |
|---|---|
| Engine reuse | knowledge_articles use HasContentLifecycle + HasFullTextSearch + implement ContentAccessible (strategy=LATERAL) |
| Tiers (D-036) | access_tier 1 public · 2 member · 3 CLIENT · 4 PARTNER · 5 internal — LATERAL (Gov Rep capped 1/2, D-044) |
| Search | FULLTEXT(title, excerpt, body) → `toSearchableColumns() = [title, excerpt, body]` (W1c-1) |
| Engagement | views/downloads → content_engagement_events; bookmarks + ratings are user-state tables (W3-6); cached counters on the row |
| Assets | broad `type` enum (guide/template/toolkit/sop/checklist/video/download/client_doc/internal_kb…); downloadable file_path gated by tier |
| Related | knowledge_related (manual / auto_category / auto_tag / ai_suggested + score) — AI seam (D-029) |
| Roles | ICS Content staff manage (knowledge.articles.create/publish); tiered reads via knowledge.tierN.read |

## 2. RESEARCH CENTER REVIEW (D-034)

Tables: research_categories, research_authors, research_publications,
research_publication_authors (M2M + author_order), research_citations.

| Aspect | Design |
|---|---|
| Engine reuse | research_publications use HasContentLifecycle + HasFullTextSearch + ContentAccessible (strategy=HIERARCHICAL) |
| Tiers (D-034) | access_tier 1 public · 2 member · 3 partner · 4 internal · 5 admin — HIERARCHICAL (`user_tier >= tier`) |
| Search | FULLTEXT(title, abstract) → `toSearchableColumns() = [title, abstract]` (W1c-1) |
| Authorship | research_authors (may be EXTERNAL — user_id NULL; ORCID for dedup); M2M with author_order (W3-8) |
| Academic metadata | DOI, publish_date, abstract (always public) — interoperability + discoverability |
| Citations | research_citations = structured INBOUND citation graph; citation COUNT also via engagement event_type='citation' (W3-4) |
| Engagement | views/downloads → content_engagement_events; cached counters on the row |
| Roles | ICS Content staff + research authors manage; tiered reads via research.tierN.read |

## 3. ACCESS CONTROL REVIEW (D-034 / D-036 / D-038 / D-051)

- **One service, two strategies.** ContentAccessService selects Hierarchical vs Lateral by
  `content->accessStrategy()`. Both already implemented and tier-correct.
- **Draft override** (existing): unpublished content → ICS staff only, regardless of tier.
- **Download gating**: `canDownload()` = file present AND `canAccess()`; file delivery is
  policy-gated/streamed or signed (same posture as W2-5), never a public file_path.
- **⚠ W3-1 — tier integers are STRATEGY-RELATIVE.** access_tier `3` means CLIENT in
  Knowledge but PARTNER in Research. The number is meaningful ONLY within its strategy; code
  must NEVER compare tiers across modules. The two-strategy design is exactly what prevents
  this confusion — documented, no change needed.
- **Separation invariant intact**: ContentAccessService is tier-scoped ONLY — never
  AccountScope, never HasAssignmentVisibility. The three mechanisms stay independent; Wave 3
  touches only the content engine.

## 4. SECURITY REVIEW

| Concern | Control |
|---|---|
| Gated body/file leakage | ContentAccessService gates body + download; only the public teaser is exposed (W3-3) |
| **Teaser projection (W3-3)** | title/excerpt(K)/abstract(R)/SEO are ALWAYS public (SEO, D-036/D-034 intent) even for gated items; the SERIALISER returns the teaser for non-entitled users and full body/file only when canAccess/canDownload — never weaken ContentAccessService to achieve this |
| File downloads | streamed behind canDownload + (Phase-2) signed URLs; counters via EngagementRecorder |
| Draft exposure | draft override → staff-only until published |
| Rating/bookmark abuse | unique (user, article); one rating per user; authenticated only |
| Cross-module tier confusion | strategy-relative tiers (W3-1); no cross-module comparison |

## 5. AUDIT REVIEW (D-046)

- Publication lifecycle is **already audited**: Knowledge/Research reuse HasContentLifecycle,
  which fires `ContentPublished`/`ContentArchived` → AuditEventSubscriber (wired in Wave 1c).
  So Wave 3 inherits publish/archive auditing for free.
- **⚠ W3-2 — REAL BUG to fix at implementation (HIGH).** The existing
  `handleContentPublished`/`handleContentArchived` handlers **hard-code `module = 'cms'`**.
  For Knowledge/Research publications this mislabels the audit record. Fix: derive the module
  from `$e->content->contentModule()` (the ContentAccessible contract already provides
  'knowledge'/'research'/'cms'). This is a correctness fix, not a new decision.
- Category stays `content_management` (publish/archive of any content). All Super Admin
  actions remain HIGH (AuditService).

## 6. ANALYTICS REVIEW (D-025 / D-051)

- All engagement flows into the unified `content_engagement_events` (view/download/citation),
  with `country_code` + `referrer_url` for geographic/source analytics — the single content
  analytics table (D-051) now exercised by THREE modules (CMS, Knowledge, Research).
- Cached counters on rows (view_count, download_count, bookmark_count, average_rating;
  research view/download_count) for cheap reads; the append-only detail feeds D-025.
- Aggregation layer (D-025) computes: top content, downloads by tier, citation counts,
  geographic reach, rating distributions — scheduled, not per-request. `analytics.content.reports`
  gates the dashboards.
- Citation analytics (W3-4): research_citations gives the structured graph; the engagement
  `citation` count gives volume — both feed author/publication impact metrics (future).

## 7. TRANSLATION REVIEW (D-014)

- Phase 1: English base columns (title/excerpt/abstract/body/SEO). Center reads base directly.
- Phase 2: translatable via the HasTranslations concern + i18n_translations
  (locale → fallback → base) — **NO schema change** (D-037). abstract/excerpt are public SEO
  fields → translating them directly improves multilingual discoverability.
- Per-locale slugs are a Phase-2 URL decision; no schema impact now.
- All UI chrome via the translator; `<html lang/dir>` from Task 8 (WCAG 3.1.1).

## 8. AI READINESS REVIEW (D-029)

- **Seams present, implementation DEFERRED to the AI sprint** (Wave 3 builds manual content):
  - `knowledge_related.relation_type='ai_suggested'` + `score` — related-content AI (D-029 #?).
  - `knowledge_articles.metadata` JSON — loose coupling for AI-linked artefacts.
  - abstract/body/excerpt — embedding/RAG source for the AI Research Assistant (D-029 #6) and
    AI Content Drafting (D-029 #10, assists draft stage — never auto-publishes, P-1).
  - `ai_requests` (existing) tracks AI cost; `content_engagement_events` + ratings provide the
    training/ranking signal.
- Guardrails (D-026/COST-01: daily/user/guest caps) already in config/ics.php. Wave 3 leaves
  the data shaped for AI; it wires no AI calls.

---

## VALIDATION MATRIX (as requested)

| Item | Validation | Result |
|---|---|---|
| **D-034** Research hierarchical | HierarchicalAccessStrategy (user_tier >= tier) matches blueprint tiers | ✅ |
| **D-036** Knowledge lateral | LateralAccessStrategy (3=client, 4=partner parallel; Gov capped) matches | ✅ |
| **D-038** unified engine | both reuse HasContentLifecycle + HasFullTextSearch + ContentAccessService | ✅ |
| **D-051** consolidation | one ContentAccessService, one content_engagement_events; retired services stay retired | ✅ proven by both consumers |
| **D-025** analytics | unified events + cached counters + aggregation layer | ✅ |
| **D-014** translation | base now; HasTranslations Phase 2, no schema change | ✅ |
| **D-029** AI readiness | ai_suggested/metadata/abstract seams; implementation deferred | ✅ |

---

## FINDINGS

| ID | Finding | Severity | Disposition |
|---|---|---|---|
| W3-1 | access_tier is strategy-relative (3=CLIENT in K, PARTNER in R) — never cross-compare | MEDIUM | Document; two-strategy design already prevents it |
| W3-2 | Audit handlers hard-code module='cms' → mislabels Knowledge/Research publications | **HIGH** | Fix at impl: derive module from `contentModule()` |
| W3-3 | Public teaser (title/excerpt/abstract/SEO) vs gated body/file — enforce at serialiser | MEDIUM | Serialiser returns teaser for non-entitled; never weaken ContentAccessService |
| W3-4 | Two citation concepts: research_citations (graph) vs engagement citation (count) | MEDIUM | Keep both; document roles |
| W3-5 | FULLTEXT columns differ per model (K: title,excerpt,body / R: title,abstract) | LOW | toSearchableColumns must match each migration (W1c-1) |
| W3-6 | bookmarks/ratings are user-state tables, NOT append-only events | LOW | Distinct from content_engagement_events; average_rating cached on write |
| W3-7 | Knowledge vs Research content-type separation (D-033) — no type in both | MEDIUM | Editorial + module enforcement; client_doc/internal_kb are Knowledge |
| W3-8 | research_authors may be external (user_id NULL); ORCID dedup | LOW | Author identity separate from platform users |

---

## RISKS

| Risk | Mitigation |
|---|---|
| Gated content body/file leaks via teaser path | Serialiser gates body/file on canAccess/canDownload (W3-3); ContentAccessService unchanged |
| Knowledge/Research publish audited under wrong module | W3-2 module-derivation fix |
| Tier cross-comparison bug | strategy-relative tiers documented (W3-1); per-strategy logic only |
| FULLTEXT missing/mismatched → search breaks | W3-5 verified in impl review (W1c-1 mandate) |
| Premature AI coupling | AI seams only; no AI calls in Wave 3 (D-029 deferred) |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Both centers reuse the content engine (no duplication, D-038) | ✅ |
| One ContentAccessService validates BOTH strategies (D-051 payoff) | ✅ |
| Tier-scoped only — AccountScope + HasAssignmentVisibility untouched/unmixed | ✅ |
| Unified analytics (content_engagement_events) + translation + AI seams | ✅ |
| Wave 3 NOT implemented; no code produced | ✅ |
| D-049 validation gate (bootstrap + GREEN CI) still in force | ⚠ carried |

---

## REVIEW VERDICT

**SOUND DESIGN — conditional on the W3-2 audit-module fix and the W3-3 teaser-projection
rule at implementation.** Wave 3 is clean reuse of the Unified Content Engine and proves the
D-051 consolidation against both the hierarchical (D-034) and lateral (D-036) access
patterns simultaneously — i18n-ready, AI-ready, and feeding the unified analytics. **No new
decisions are required.** Cleared to proceed to Wave 3 implementation after approval.

Items to carry into implementation (no decision needed):
- **W3-2:** derive the audit module from `contentModule()` (fixes CMS-hardcoding for
  Knowledge/Research publish/archive).
- **W3-3:** teaser-vs-gated projection enforced at the serialiser; ContentAccessService and
  the two strategies remain unchanged.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement Wave 3 until approved.**
