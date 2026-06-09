# LARASTAN REMEDIATION REPORT — Phase 3
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05 → executed
Status: EXECUTED — Larastan GREEN via baseline (tracked debt).
Author: Lead Architect

## RESULT: ✅ `phpstan analyse` PASSES (exit 0, "No errors")

## DECISION: BASELINE (accepted debt) — not annotations (this milestone)

- First analysis surfaced **114 errors** at level 5, **~98% `property.notFound`** on dynamic Eloquent
  attributes (e.g. `Model::$module`, `$marks`, `$validity_months`) — i.e. models accessed without
  `@property` annotations. **No runtime impact** (Eloquent magic resolves them at runtime; all 57 tests
  pass).
- Generated `phpstan-baseline.neon` (114 entries) and included it in `phpstan.neon`. Re-run → **No errors**.

### Why baseline now (per D-089/plan), annotations later
- The GREEN-CI milestone is the objective; the findings are type-noise, not defects.
- A baseline is the sanctioned interim (FINAL_GREEN_CI_EXECUTION_PLAN Step 4) — it freezes current debt
  WITHOUT hiding NEW issues: any new property.notFound (or other error) outside the baseline fails CI.

## GUARDRAILS

- `phpstan.neon` documents the baseline as **tracked technical debt**: burn it down by adding `@property`
  annotations to models; **do NOT grow the baseline**.
- Level remains **5** (unchanged); the project comment "raise the level incrementally" stands.

## BURN-DOWN RECOMMENDATION (post-GREEN, not blocking)

1. Add `@property` / `@property-read` docblocks (or generate via an IDE-helper) to the Eloquent models
   that account for the 114 entries (Training, Billing, Startup/Program, etc.).
2. Delete the corresponding baseline entries as each model is annotated.
3. Periodically regenerate the baseline to confirm it only shrinks.

## STATUS

Larastan gate: ✅ GREEN (baseline). Burn-down tracked as non-blocking quality debt.
