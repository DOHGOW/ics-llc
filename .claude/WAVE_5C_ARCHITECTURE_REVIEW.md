# WAVE 5C ARCHITECTURE REVIEW — ACCELERATOR PROGRAM
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-03
Status: Architecture review — NO code/migrations/models/services/controllers/routes. Design only.
Author: Lead Architect
Validates against: D-025, D-029, D-037, D-046, D-053, D-057, D-059, D-061, D-063, D-065, D-066, D-067; C-1
Inputs: WAVE_5B_IMPLEMENTATION_REVIEW.md, ECOSYSTEM_ROADMAP_REVIEW.md, ACCESS_CONTROL_CONSOLIDATION_REVIEW.md

**PRIMARY OBJECTIVE:** prove Accelerator remains a THIN SPECIALIZATION of the Generic Program
Architecture (D-065) — not a second platform — and duplicates none of Program Architecture,
Startup Hub, or the future Investment Network.

---

## EXECUTIVE SUMMARY

Accelerator is `startup_programs.type='accelerator'` running on the Wave 5B Generic Program
Architecture. It REUSES programs, cohorts, intake, enrollment/participation, lifecycle routing,
PROGRAM_MANAGEMENT audit, and the program analytics aggregator. The ONLY new surface is a thin
set of accelerator EVENTS — Demo Day, Pitch, Investor Showcase, Readiness Review, Graduation
Showcase — modelled as ONE generic `program_events` mechanism (+ judging/scoring + a readiness
signal). **Estimated reuse from Wave 5B: ~85%** (> 80%). The defining governance line:
**Accelerator PREPARES startups; the Investment Network (5d) EXECUTES investment** — Accelerator
must NOT build an investor registry, fundraising workflow, cap-table store, or due-diligence
system. **Verdict: SOUND WITH CONDITIONS.**

---

## 1. REUSE ANALYSIS (quantified)

| Layer | Reused from 5B/5A? | Items |
|---|---|---|
| Entities | ✅ reuse | startup_programs (type=accelerator), program_cohorts, program_coordinators, startup_program_enrollments (participation), startup_profiles/team/mentors |
| Services | ✅ reuse | ProgramParticipationService, IntakeService, ProgramEnrollmentService, ProgramGovernanceService, CompletionValidator, StartupAccessService, StartupGovernanceService (lifecycle) |
| Workflows | ✅ reuse | intake (applied→accepted→active), enrollment, graduation, withdrawal, forced removal, cohort/program governance |
| Audit | ✅ reuse | PROGRAM_MANAGEMENT (D-066), program type as context |
| Analytics | ✅ reuse | ProgramAnalyticsAggregator.snapshot('accelerator') |
| Lifecycle | ✅ reuse | accept → 'acceleration' (already type-aware in IntakeService); graduation → staff-routed lifecycle (D-063) |
| **NEW (accelerator-only)** | ⛳ ~15% | program_events (demo_day/pitch/showcase/readiness types) + judges + scores; a readiness signal; thin EventService/ReadinessService; showcase exposure view |

**Estimated reuse ≈ 85%** of the program domain. The new surface is the events/judging/readiness/
showcase layer only. **Above the 80% threshold — no shortfall to explain.**

## 2. ACCELERATOR-SPECIFIC FEATURES (the only new surface)

All modelled as ONE generic `program_events` table (type enum) + scoring — NOT five separate
subsystems (M-1, keeps reuse high):

| Feature | Modelled as |
|---|---|
| Demo Day | program_event(type=demo_day) + judges + scores → ranking (derived) |
| Investor Showcase | program_event(type=showcase) — EXPOSURE only (curated/public startup view to invited investors) |
| Pitch Sessions | program_event(type=pitch) + submission (gated deck) + feedback + scores |
| Readiness Reviews | program_event(type=readiness_review) + readiness checkpoints/score |
| Graduation Showcase | program_event(type=graduation_showcase) — exposure of graduating cohort |

## 3. DEMO DAY ARCHITECTURE

| Aspect | Design |
|---|---|
| Startup participation | cohort startups with an active enrollment (reuse ProgramParticipationService) — no new participation model |
| Judge participation | judges are EXISTING users (mentors/staff/invited) assigned to the event (program_event_judges) — NOT a new judge registry |
| Scoring | program_event_scores (judge × startup × criterion); one score per judge/startup/criterion (integrity, M-4) |
| Ranking | DERIVED/computed from scores (not an authoritative stored state, L-1) |
| Audit | event creation + score finalization audited under PROGRAM_MANAGEMENT (type context); finalization locks scores |

## 4. INVESTOR SHOWCASE ARCHITECTURE (governance-critical)

| Aspect | Design |
|---|---|
| Investor access | invited investors REFERENCE existing identities (Community 'investor' profile type / Investment Network 5d) — **NO duplicate investor registry** (H-2) |
| Startup exposure | a CURATED, public-projection view of showcase startups (reuse StartupPublicResource fields) |
| **Visibility boundaries (H-1)** | EXPOSURE ONLY — NO cap-table, financials, internal milestones, or data-room (C-1/M-1/W4b-1). Showcase does not grant data-room access |
| Future Investment Network integration | ONE-WAY handoff: showcase → expression of interest → (5d) NDA-gated data-room grant + deal. Accelerator stops at exposure + interest signal; **5d executes** (H-1/D-069) |

## 5. PITCH EVENT ARCHITECTURE

| Aspect | Design |
|---|---|
| Submission | startup submits a pitch deck — gated file, streamed (W2-5/M-3); reuse storage |
| Review workflow | coordinators/judges review (reuse ProgramParticipationService.canManageCohort) |
| Feedback model | feedback notes to the startup (private to the startup + reviewers) |
| Scoring model | reuse program_event_scores (same as Demo Day) — no second scoring system |

## 6. INVESTMENT READINESS ARCHITECTURE

| Aspect | Design |
|---|---|
| Readiness checkpoints | a checklist on the readiness event (non-financial maturity criteria) |
| Readiness score | computed from checkpoints (+ deferred AI, D-029) — a MATURITY signal, NOT financial; never cap-table (H-3) |
| Graduation criteria | readiness threshold gates accelerator graduation via the existing CompletionValidator hook (D-067) — reuse, not a new graduation engine |

AI scoring remains DEFERRED (D-029).

## 7. STARTUP HUB INTEGRATION

| Aspect | Design |
|---|---|
| Lifecycle transitions | enrolment → 'acceleration' (already type-aware); routed through StartupGovernanceService (D-063, H-3) |
| Graduation | reuse ProgramEnrollmentService.graduate (completion/readiness-validated) |
| Alumni movement | graduation may advance lifecycle toward investment_ready / alumni via the Startup governance layer (staff-routed) — no parallel state |

## 8. COMMUNITY INTEGRATION (D-035)

| Aspect | Design |
|---|---|
| Mentor / advisor visibility | reuse startup_mentors (type mentor/advisor); notes private |
| Public showcase boundaries | showcase public view = curated public fields only (W4b-1); cohort internals/financials never public |

## 9. AUDIT ARCHITECTURE (reuse PROGRAM_MANAGEMENT, D-066)

- Reuse `PROGRAM_MANAGEMENT`; program type carried as context.
- Additional audited events (normal): event created, scoring finalized, readiness determined,
  showcase access granted.
- **Additional HIGH events:** score override after finalization, readiness override, showcase
  access revocation — treated HIGH (integrity/governance). Forced removal / graduation reversal
  already HIGH (D-066). **No new audit category** required.

## 10. ANALYTICS ARCHITECTURE (D-025 / W4-9)

- Reuse ProgramAnalyticsAggregator.snapshot('accelerator') for cohort/graduation/retention.
- Thin accelerator additions: cohort success metrics (graduation→investment_ready), readiness
  metrics (score distribution), showcase metrics (events held, startups showcased), investor
  engagement metrics (interest signals — COUNTS only, no investor PII, no financials).
- Own aggregator; NOT content_engagement_events; no cap-table/financial data (C-1).

## 11. INVESTMENT NETWORK COMPATIBILITY  ★ (CRITICAL)

**Boundary rule (D-069 proposed): Accelerator PREPARES; Investment Network (5d) EXECUTES.**

| Must NOT duplicate | Owner | Accelerator stance |
|---|---|---|
| Investor registry | Investment Network (5d) / Community 'investor' type | reference only; NO new investor table (H-2) |
| Fundraising workflows (deals/term sheets) | Investment Network (5d) | none in Accelerator |
| Cap-table storage | Investment Network (5d) data room (C-1 system of record) | none in Accelerator; readiness is non-financial |
| Due-diligence system | Investment Network (5d) | none in Accelerator |

Accelerator outputs an INVESTMENT-READY signal + expression-of-interest; the one-way handoff to
5d is where investors, data rooms, deals, and diligence live. **Any of the four above appearing
in Accelerator = governance violation → STOP and flag.** (None proposed here.)

## 12. FUTURE TenantScope COMPATIBILITY (D-037)

- programs/cohorts/events carry tenant_id; events inherit via cohort/program.
- **Regional accelerators / multi-country cohorts:** region/country attributes; analytics gain a
  region dimension; access unaffected.
- **Franchise:** each franchise runs its own accelerator programs (tenant-scoped); TenantScope
  nests above program participation; additive, no schema change (D-037).

---

## MANDATORY FINDINGS

| ID | Severity | Finding | Disposition |
|---|---|---|---|
| **CG-1** | **CRITICAL (guardrail)** | Accelerator must NOT build investor registry / fundraising / cap-table / due-diligence (Investment Network 5d) | enforce boundary (D-069); none proposed |
| **H-1** | HIGH | Investor Showcase is EXPOSURE ONLY — no data-room, no cap-table, curated/public fields | reuse public projection (W4b-1/C-1/M-1) |
| **H-2** | HIGH | No duplicate investor registry — investors reference Community/5d identities | reference, don't create |
| **H-3** | HIGH | Readiness/demo-day data is NON-financial; never store/expose cap-table | maturity signals only |
| **M-1** | MEDIUM | Model accelerator events generically (one program_events + scores) — avoid per-feature sprawl | keeps reuse ~85% |
| **M-2** | MEDIUM | Graduation gated by readiness via CompletionValidator (reuse D-067) | reuse, no new engine |
| **M-3** | MEDIUM | Pitch deck / showcase files gated + streamed (W2-5) | reuse storage posture |
| **M-4** | MEDIUM | Judge scoring integrity — one score per judge/startup/criterion; finalization locks | enforce |
| **L-1** | LOW | Ranking derived/computed, not authoritative stored state | compute on read |
| **L-2** | LOW | AI readiness/judging scoring deferred (D-029) | seams only |
| **L-3** | LOW | Analytics reuse ProgramAnalyticsAggregator + thin showcase/readiness metrics | reuse |

### Conflicts / collisions / risks
- **Architectural conflict:** building accelerator as a "second platform" (the thing to avoid) —
  mitigated by specialization + generic program_events (M-1).
- **Schema collisions:** none — new tables (program_events, program_event_judges,
  program_event_scores, readiness) are additive + accelerator-scoped; **must NOT add investor/
  deal/cap-table/diligence tables** (5d owns those).
- **Governance risks:** showcase exposure creeping into investment execution (CG-1); scoring/
  readiness integrity; fairness of readiness determination (audited).
- **Data leakage risks:** showcase must not expose internal/financial (C-1/M-1); judge scores
  visible to the scored startup only (its own feedback), not other startups'; pitch decks gated.
- **Implementation risks:** duplicating Investment Network (CG-1); per-feature table sprawl (M-1).

---

## FINAL VERDICT

**SOUND WITH CONDITIONS.** Accelerator is a thin specialization (~85% reuse, > 80%) of the
Generic Program Architecture; it adds only a generic events/judging/readiness/showcase layer and
duplicates NONE of Program Architecture, Startup Hub, or Investment Network. No governance
violation is present (no investor registry / fundraising / cap-table / due-diligence proposed).

Conditions:
1. **CG-1/D-069** — Accelerator PREPARES; Investment Network (5d) EXECUTES. No investor registry,
   fundraising, cap-table store, or due-diligence in Accelerator.
2. **H-1/H-2/H-3** — Investor Showcase is exposure-only (curated/public, no data-room/cap-table);
   investors reference existing identities; readiness data is non-financial.
3. **M-1** — generic program_events model (one mechanism for demo_day/pitch/showcase/readiness).
4. **M-2** — graduation gated by readiness via the existing CompletionValidator (reuse D-067).

Access: participation-family reuse confirmed; **no new access-control family**. Audit reuses
PROGRAM_MANAGEMENT. No conflict with D-025/D-037/D-046/D-053/D-057/D-059/D-061/D-063/D-065/D-066/D-067.

Proposed decisions to ratify on approval (NOT now):
- **D-068** — Accelerator = thin specialization (generic program_events + judging/scoring +
  readiness; reuses ProgramParticipationService / PROGRAM_MANAGEMENT / ProgramAnalyticsAggregator).
- **D-069** — Accelerator↔Investment Network boundary (PREPARE vs EXECUTE); no duplication of
  investor registry / fundraising / cap-table / due-diligence.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement Accelerator until approved and conditions
CG-1/H-1/H-2/H-3/M-1/M-2 (D-068/D-069) are decided.**
