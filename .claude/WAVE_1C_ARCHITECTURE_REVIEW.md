# WAVE 1C ARCHITECTURE REVIEW — CORPORATE WEBSITE / CMS
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Architecture/Design — Awaiting Approval (no CMS code yet)
Author: Chief Enterprise Architect
Decision References: D-010, D-014, D-028, D-037, D-038, D-051

Interpretation: the named deliverable is an ARCHITECTURE REVIEW and the instruction
is "wait for approval after the CMS architecture review before implementation" — so
this is the Wave 1c DESIGN; implementation follows approval.

---

## ⚠ HIGH IMPLEMENTATION REMINDER (W1b-i3)

Every searchable content model MUST explicitly declare a **FULLTEXT index** in its
migration, matching the model's `toSearchableColumns()`. This is a mandatory,
verified item in the Wave 1c implementation review (no FULLTEXT index = `MATCH …
AGAINST` fails).

---

## EXECUTIVE SUMMARY

Wave 1c designs the Corporate Website / CMS as the FIRST consumer of the Unified
Content Engine (D-038/D-051) — validating the abstraction. CMS content is **public
(tier 1), not org-owned**: it uses HasContentLifecycle + HasFullTextSearch +
ContentAccessService (uniformly), never AccountScope. It is WCAG 2.1 AA, i18n-ready,
and audit-ready on publish. Verdict: **SOUND DESIGN — proceed to Wave 1c
implementation after approval.**

---

## 1. CMS ARCHITECTURE REVIEW

Tables (DATABASE_BLUEPRINT): `content_pages`, `content_articles`, `content_media`.

| Aspect | Design |
|---|---|
| Ownership | NOT org-owned — no BelongsToAccount, no AccountScope (W1-3). Public content. |
| Access | Tier 1 (public) once published; drafts visible to ICS staff only (ContentAccessService draft override). Both strategies return true for tier 1 — CMS plugs in uniformly. |
| Engine reuse | content_pages/articles use HasContentLifecycle + HasFullTextSearch + implement ContentAccessible (tier 1). |
| Roles | ICS Staff — Content manages CMS (cms.* permissions); publish requires cms.articles.publish / cms.pages.publish (human approval, P-1). |
| Structure | content_media is an asset library (not lifecycle content); referenced by pages/articles. |
| Layers | Controllers (thin) → CmsService → models; API-first (/api/v1/content); Blade for public rendering. |

## 2. CONTENT LIFECYCLE REVIEW

- States via HasContentLifecycle: draft → under_review → published → archived.
- `publish()` gated by permission (cms.*.publish) — no auto-publish (P-1).
- `published()` scope used for all public reads; drafts never public.
- ContentPublished / ContentArchived events fire (audited — §Audit-ready).
- Slug auto-generated, unique; editable while draft; stable after publish (a slug
  change post-publish should create a redirect — a Phase-2 nicety, noted).

## 3. SEARCH REVIEW (W1b-i3)

- content_articles: `toSearchableColumns()` = [title, body] (optionally excerpt) →
  migration MUST declare `FULLTEXT(title, body)` (W1b-i3, HIGH).
- content_pages: searchable [title, body] → FULLTEXT(title, body).
- content_media: searched by `original_name` / `alt_text` via LIKE (small set) — no
  FULLTEXT required; documented.
- Driver config-driven (ics.search.driver) → Meilisearch swap Phase 2 (config-only).
- Public search results paginated + cached + Cloudflare-fronted.

## 4. SEO REVIEW

- Per-item: `seo_title`, `seo_description`, `slug`, `published_at`.
- Canonical URL from slug; clean, human-readable slugs (SEO + accessibility).
- Sitemap + structured data (JSON-LD) generated for published content (implementation).
- Meta defaults fall back to title/excerpt when SEO fields are empty.
- Per-locale SEO (title/description/slug) is translatable in Phase 2 (§Localization).
- Google Analytics + Search Console hooks (D-002) — markup, not data model.

## 5. LOCALIZATION REVIEW (D-014)

- Phase 1: English base columns (title/body/excerpt/SEO). CMS reads base directly.
- Phase 2: translatable via the HasTranslations concern + i18n_translations
  (locale → fallback → base, R-8) — NO schema change (D-037). Slugs translatable
  per locale (URL strategy is a Phase-2 decision; no schema impact).
- All UI chrome via the translator; `<html lang/dir>` from Task 8 (WCAG 3.1.1).

## 6. ACCESSIBILITY REVIEW (D-028 / WCAG 2.1 AA)

- **content_media.alt_text is REQUIRED** for images (validation) — non-decorative
  images must have descriptive alt text (WCAG 1.1.1).
- Rendered content uses a logical heading hierarchy; the editor/output must not skip
  levels; CMS provides guidance/validation.
- Colour contrast + focus-visible inherited from the design system (Task 1/8).
- Links have discernible text; media has captions where applicable (video — future).
- Public pages carry `<html lang/dir>`, skip link, landmarks (Task 8 layout).
- SEO slugs + semantic markup aid both SEO and assistive technology.

## 7. PERFORMANCE REVIEW

- FULLTEXT on shared hosting: indexed + paginated + cached + Cloudflare edge.
- Media: stored via Laravel Storage (D-024); served with cache headers; image size
  limits (D-024); CDN/Cloudflare for delivery; object storage Phase 3.
- Engagement: article views recorded via EngagementRecorder (content_engagement_events,
  event_type=view); cached view counter on the row; no per-view heavy work.
- Published-content reads cached; Cloudflare caches public pages.

---

## CMS FEATURE MAP

| Feature | Design |
|---|---|
| Page Management | content_pages CRUD; lifecycle; SEO; template field |
| Article Management | content_articles CRUD; lifecycle; categories/tags (later); FULLTEXT |
| Media Library | content_media upload (Storage, D-024); alt_text required; mime/size validation |
| SEO Metadata | seo_title/description, slug, canonical, sitemap, JSON-LD |
| Slug Management | auto-unique slug; editable in draft; stable post-publish |
| Draft/Review/Publish | HasContentLifecycle; permission-gated publish; audited |

---

## REQUIREMENTS VERIFICATION

| # | Requirement | Design status |
|---|---|---|
| 1 | WCAG 2.1 AA | ✅ alt_text required, headings, lang/dir, contrast/focus |
| 2 | D-014 localization | ✅ base now; HasTranslations Phase 2 (no schema change) |
| 3 | FULLTEXT verification | ✅ mandated (W1b-i3); verified in impl review |
| 4 | Content lifecycle enforcement | ✅ HasContentLifecycle; permission-gated publish |
| 5 | Engagement integration | ✅ EngagementRecorder → content_engagement_events |
| 6 | Audit-ready publishing | ✅ ContentPublished/Archived → audit (see below) |

**Audit-ready publishing:** Wave 1c wires ContentPublished/ContentArchived to the
AuditEventSubscriber (a new `content_management` category; normal sensitivity; actor =
publishing staff). This closes W1b-i2 for CMS.

---

## FINDINGS

| ID | Finding | Severity |
|---|---|---|
| W1c-1 | FULLTEXT indexes must be declared per searchable migration (W1b-i3) | HIGH |
| W1c-2 | content_media.alt_text required-validation for images (WCAG 1.1.1) | MEDIUM |
| W1c-3 | Slug-change-after-publish should create a redirect (Phase 2) | LOW |
| W1c-4 | Add `content_management` audit category for publish events | LOW |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| CMS reuses the content engine (no duplication, D-038) | ✅ design |
| CMS is public/tier-1; NOT org-owned (AccountScope untouched) | ✅ |
| ContentAccessService used; D-034/D-036 unchanged | ✅ |
| WCAG / i18n / FULLTEXT / lifecycle / engagement / audit addressed | ✅ |
| CMS NOT implemented; CRM NOT implemented | ✅ |

---

## REVIEW VERDICT

**SOUND DESIGN.** CMS is the first content-engine consumer — public, accessible,
i18n-ready, FULLTEXT-searchable, lifecycle-governed, and audit-ready on publish.
Cleared to proceed to **Wave 1c implementation** after approval.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Accessibility/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do not implement CMS until approved.** On approval,
Wave 1c implementation: migrations (with FULLTEXT — W1b-i3), models (engine traits +
ContentAccessible), CmsService, controllers/routes, media handling, audit wiring.
