# LOCALIZATION ARCHITECTURE REVIEW — PRE-TASK 8
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Review Complete — Awaiting Approval
Reviewer: Chief Enterprise Architect
Decision References: D-014 (i18n), D-028 (WCAG), D-037 (config-only), D-016/D-017
Inputs: ENTERPRISE_ARCHITECTURE_BLUEPRINT §13, Tasks 1–7 implementation

---

## ⚠ DECISION-REFERENCE CORRECTION (read first)

The request asked to validate **"D-012 Internationalization Decision."** D-012 is
actually **CRM Scope (internal CRM)**. The Internationalization decision is **D-014**
(English → French → Arabic/RTL; localization supported from day one). This review
validates **D-014** as the i18n decision. Flagging rather than silently
substituting, to keep the decision log authoritative.

---

## EXECUTIVE SUMMARY

The localization foundation is well-designed and consistent with D-014/D-037/D-028:
a two-layer model (UI lang files + the dormant `i18n_translations` table), RTL-ready
Tailwind, a per-user `locale` column, and English as the Phase 1 baseline. The
architecture is intact; what remains for Task 8 is the **runtime wiring** (locale
detection, `<html lang/dir>`, config-driven locale list) plus formatting helpers
(date/time, currency). Verdict: **SOUND — implement the Task 8 wiring per the
recommendations.**

---

## PART A — DECISION VALIDATION

### D-014 — Internationalization (the real i18n decision) — ALIGNED
- EN (Phase 1) → FR (Phase 2) → AR/RTL (Phase 3); day-one localization support.
- Implemented foundations: `lang/en/ics/common.php`; Tailwind logical utilities
  (RTL-ready); `app.css` RTL note; `i18n_translations` table (Task 3, dormant);
  `core_users.locale`; `config/app.php` APP_LOCALE=en, APP_FALLBACK_LOCALE=en.
- Remaining (Task 8): locale detection, `<html lang/dir>`, config-driven locales.

### D-037 — Config-Only Migration — INTACT (with one gap)
- `i18n_translations` exists from Phase 1 but is dormant; adding FR/AR is data +
  config, not schema/code redesign. ✅
- **Gap LOC-7:** the available/active locale list is not yet config-driven. To make
  "add a language" truly config-only, introduce `config('app.available_locales')` /
  `active_locales`.

### D-028 — WCAG 2.1 AA — PARTIALLY ADDRESSED
- RTL-ready layout (logical CSS) is in place. **Not yet wired:** `<html lang>`
  (WCAG 3.1.1 Language of Page) and `dir` (text direction). These are Task 8.
- Translated content must also carry accessible attributes (alt text, form labels)
  per language — a content-module concern as translations land.

---

## PART B — AREA REVIEWS

### 1. Language Architecture — SOUND
Two layers (Blueprint §13): UI strings via `__('...')` in `/lang/{locale}/` (Laravel
11 root `/lang`), and dynamic content via `i18n_translations`. Per-user `locale`;
app default + fallback in config. Resolution order (planned): user pref → session →
Accept-Language → default.

### 2. Translation Storage Strategy — SOUND
- UI: PHP array files (versioned, fast, cache-friendly).
- Dynamic: `i18n_translations(translatable_type, translatable_id, locale, field,
  value)` — unique per (type,id,locale,field). Clean separation.
- **Rec (LOC-3):** add a `HasTranslations` trait + accessor, with eager-loading /
  caching to avoid N+1 on translated reads (build when FR content lands).

### 3. i18n Translation Table Usage — READY, DORMANT
- Present from Phase 1; unused while English-only (D-037). Activated by populating
  rows + enabling the locale. No schema change to add a language.
- **Rec:** define translatable fields per model; per-content translation-status
  tracking (later) so partially-translated content is handled gracefully.

### 4. RTL Readiness — STRONG
- Tailwind logical utilities (ms/me, ps/pe, start/end), `dir`-driven layout, RTL
  note in `app.css`. No physical left/right in the base.
- **Rec (LOC-6):** wire `<html dir="rtl">` when locale = ar; RTL QA pass before AR
  ships (Phase 3). Confirm third-party widgets honour `dir`.

### 5. Fallback Language Strategy — DEFINED FOR UI, GAP FOR CONTENT
- UI: `APP_FALLBACK_LOCALE=en` → missing key falls back to English. ✅
- **Gap LOC-8:** dynamic-content fallback chain is undefined. Define: requested
  locale → fallback locale → base record field; never render an empty string.

### 6. Date/Time Localization — PARTIAL
- UTC stored (APP_TIMEZONE=UTC); `core_users.timezone` exists. Display conversion +
  locale-aware formatting not yet implemented.
- **Rec (LOC-4):** a date/time helper — store UTC, convert to the user's timezone,
  format with Carbon `->locale($user->locale)`. Government: unambiguous date formats.

### 7. Currency Localization — PARTIAL
- Billing carries a `currency` column (NGN/USD/GBP/EUR); Phase 1 mostly NGN.
  Continental scale (D-017) implies multi-currency display.
- **Rec (LOC-5):** a currency formatter using PHP `intl` NumberFormatter keyed by
  locale + currency (symbol, separators). Store decimals; format on display. (The
  `intl` extension is in the dependency/capability checks.)

### 8. Government Accessibility Implications — KEY (D-028 / D-016)
- `<html lang>` (WCAG 3.1.1) and `dir` are mandatory for government accessibility —
  wire in Task 8.
- French is strategic for Francophone government (D-016 audience #1) — not cosmetic;
  resource professional FR translation in Phase 2.
- Accessible, keyboard-navigable language switcher; translated a11y attributes;
  in-content language marks (WCAG 3.1.2) where mixed languages appear.

### 9. Future Multi-Language Expansion — CLEAR PATH
- EN → FR (Phase 2) → AR (Phase 3). Adding a language = lang files + i18n rows +
  enable locale (config). No schema change (D-037).
- **Rec (LOC-9/10):** translation workflow/tooling; professional FR; RTL QA for AR;
  per-content translation status; config-driven locale list (LOC-7).

---

## PART C — FINDINGS

| ID | Finding | Severity |
|---|---|---|
| LOC-1 | Request referenced D-012 for i18n; correct decision is D-014 | INFO (corrected) |
| LOC-2 | Locale detection not yet implemented | (Task 8) |
| LOC-3 | No HasTranslations trait + translation caching for dynamic content | MEDIUM (Phase 2) |
| LOC-4 | No date/time localization helper | MEDIUM |
| LOC-5 | No currency localization helper | MEDIUM |
| LOC-6 | `<html lang/dir>` not wired (WCAG 3.1.1) | HIGH (Task 8 / D-028) |
| LOC-7 | Locale list not config-driven (config-only add) | MEDIUM (D-037) |
| LOC-8 | Dynamic-content fallback chain undefined | MEDIUM |

---

## PART D — RECOMMENDATIONS

### Task 8 (Phase 1 foundation)
| # | Recommendation |
|---|---|
| R-1 | Locale detection middleware (user pref → session → Accept-Language → default) sets app locale |
| R-2 | `config('app.available_locales')` + `active_locales` (en active; fr/ar reserved) — config-only language addition (D-037) |
| R-3 | `<html lang="{locale}" dir="{ltr\|rtl}">` in the base layout (WCAG 3.1.1 / D-028) |
| R-4 | Accessible, keyboard-navigable language-switcher scaffold |
| R-5 | Date/time helper (UTC → user timezone, Carbon locale formatting) |
| R-6 | Currency helper (intl NumberFormatter, locale + currency) |

### Phase 2 / 3 (when FR/AR land)
| # | Recommendation |
|---|---|
| R-7 | `HasTranslations` trait + eager-load/cache for dynamic content |
| R-8 | Dynamic-content fallback chain (locale → fallback → base field) |
| R-9 | Translation workflow/tooling; professional FR; RTL QA for AR |
| R-10 | Per-content translation-status tracking |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| 9 areas reviewed | ✅ |
| D-014 (i18n), D-037, D-028 validated | ✅ (D-012→D-014 corrected) |
| Recommendations provided | ✅ |
| Task 8 NOT implemented | ✅ |

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Accessibility/Compliance | | | | |

**Status:** Awaiting Approval. **Do not implement Task 8 until approved.**
