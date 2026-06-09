# LOCALIZATION TEST SPECIFICATION
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Specification — tests authored in Task 10.1
Decision References: D-014, D-028, D-037
Framework: PHPUnit 11

---

## PURPOSE

Defines the automated tests guaranteeing the Task 8 localization foundation behaves
correctly and accessibly. These become CI gates (IMPLEMENTATION_GOVERNANCE §7).

---

## 1. LOCALE DETECTION TESTS (R-1)
| ID | Test | Expected |
|---|---|---|
| LD-1 | `?locale=en` sets app locale to en + persists to session | locale=en |
| LD-2 | `?locale=fr` while fr inactive → ignored, falls to default | locale=en |
| LD-3 | Session locale (active) is used when no query | session locale |
| LD-4 | Authenticated user.locale (active) used when no query/session | user locale |
| LD-5 | Accept-Language first active match used | matched locale |
| LD-6 | No signals → configured default | default |
| LD-7 | Inactive locale never selected from any source | default |

## 2. FALLBACK LANGUAGE TESTS (R-2 / D-037)
| ID | Test | Expected |
|---|---|---|
| FB-1 | Missing UI translation key falls back to APP_FALLBACK_LOCALE | English string/key |
| FB-2 | `active` empty/misconfigured → defaults to [default] (never empty) | [en] |
| FB-3 | `active` intersected with `available` (unknown codes dropped) | only valid |

## 3. RTL TESTS (R-3 / requirement 7)
| ID | Test | Expected |
|---|---|---|
| RT-1 | LocaleRegistry::direction('ar') = 'rtl' | rtl |
| RT-2 | direction('en') / direction('fr') = 'ltr' | ltr |
| RT-3 | With ar active (test config), layout emits `dir="rtl"` | rtl |
| RT-4 | ar is NOT active by default (Phase 1) | isActive('ar') false |

## 4. LANGUAGE SWITCHER TESTS (R-4)
| ID | Test | Expected |
|---|---|---|
| LS-1 | One active locale → switcher not rendered | absent |
| LS-2 | Multiple active locales → switcher renders each with lang/hreflang | present |
| LS-3 | Active locale marked `aria-current="true"` | present |
| LS-4 | Switcher `<nav>` has an aria-label | present |
| LS-5 | Switch link carries `?locale=` for each active locale | present |

## 5. DATE/TIME FORMATTING TESTS (R-5)
| ID | Test | Expected |
|---|---|---|
| DT-1 | UTC timestamp formatted in a given timezone | converted |
| DT-2 | Locale affects month/day names (en vs fr fixture) | localized |
| DT-3 | date()/dateTime()/time() return non-empty localized strings | ok |
| DT-4 | No hardcoded timezone — uses config/app.timezone or passed tz | ok |

## 6. CURRENCY FORMATTING TESTS (R-6)
| ID | Test | Expected |
|---|---|---|
| CU-1 | format(1234.5,'NGN','en') uses intl (symbol + grouping) | localized |
| CU-2 | Locale changes grouping/symbol placement (en vs fr fixture) | localized |
| CU-3 | intl unavailable → fallback "CODE 0.00" | "NGN 1,234.50" |
| CU-4 | No hardcoded currency — code is a parameter | ok |

## 7. ACCESSIBILITY TESTS (D-028 / WCAG)
| ID | Test | Expected |
|---|---|---|
| AC-1 | Rendered layout has `<html lang="{active}">` | present |
| AC-2 | Rendered layout has `<html dir="{ltr|rtl}">` | present |
| AC-3 | Skip-to-content link present and first focusable | present |
| AC-4 | Switcher options carry `lang` (WCAG 3.1.2) | present |
| AC-5 | dir flips to rtl when active locale is Arabic (test config) | rtl |

---

## ACCEPTANCE CRITERIA (from Task 8)
- [ ] HTML `lang` attribute verified (AC-1)
- [ ] HTML `dir` attribute verified (AC-2, AC-5)
- [ ] Locale registry is configuration-driven (FB-2/3, LD-7, CONFIG review)
- [ ] No hardcoded locale assumptions (LD-7, DT-4, CU-4 + code review of LocaleRegistry)

---

## EXECUTION
- CI quality job; failure BLOCKS merge. Authored in Task 10.1.

## APPROVAL
| Role | Name | Signature | Date |
|---|---|---|---|
| Lead Architect | | | |
| Accessibility/Compliance | | | |
