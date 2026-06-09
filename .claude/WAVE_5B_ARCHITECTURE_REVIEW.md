# WAVE 5B ARCHITECTURE REVIEW — INCUBATOR PROGRAM
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-03
Status: Architecture review — NO code/migrations/models/controllers/services. Design only.
Author: Lead Architect
Validates against: D-019, D-025, D-029, D-031, D-037, D-038, D-046, D-053, D-057, D-059, D-061..D-064
Inputs: WAVE_5A_IMPLEMENTATION_REVIEW.md, ECOSYSTEM_ROADMAP_REVIEW.md, ACCESS_CONTROL_CONSOLIDATION_REVIEW.md
Existing schema: startup_programs (type general/incubator/accelerator), startup_program_enrollments

---

## EXECUTIVE SUMMARY

The Incubator Program EXTENDS Startup Hub (D-019) using the existing `startup_programs`
(type='incubator') + `startup_program_enrollments`. The single most important architectural
decision in this review (item 11) is that **Accelerator must be Option B — a specialization of
the SAME Program Architecture, not a separate one** (D-038 no-duplication). Therefore Wave 5B
must build a **GENERIC Program Architecture** (programs + cohorts + intake + enrollment +
graduation + a thin `ProgramParticipationService` + a single `PROGRAM_MANAGEMENT` audit category
+ a `ProgramAnalyticsAggregator`) that Incubator instantiates and Accelerator (5c) specializes —
not an incubator-specific silo. **Verdict: SOUND WITH CONDITIONS.**

---

## 1. PROGRAM ARCHITECTURE

| Aspect | Design |
|---|---|
| Cohorts | a program row models one cohort (startup_programs.cohort_name, start/end). For many cohorts per program brand, a thin `program_cohorts` extension is optional (M-4) |
| Intake cycles | a selection step BEFORE enrollment (applied → accepted/rejected → active). Recommend extending enrollment status (`applied`/`accepted`/`active`/`graduated`/`withdrawn`) OR a thin `program_applications` table (M-1) — incubator intake must be governed/audited, not a direct insert |
| Enrollment | startup_program_enrollments (unique startup+program) — the membership key |
| Graduation | status=graduated, graduated_at; advances the startup's lifecycle_stage (D-063, single authority) |
| Withdrawal | status=withdrawn; audited |

**Build GENERIC (the H-1 condition):** programs/cohorts/intake/enrollment/graduation/withdrawal
are generic Program Architecture — Incubator is `type='incubator'`; Accelerator (5c) reuses it.

## 2. STARTUP HUB INTEGRATION

| Aspect | Design |
|---|---|
| Startup participation | via enrollment (the program membership key) |
| Founder/team participation | via Startup Hub membership (StartupAccessService) — a team member of an enrolled startup participates in the program |
| Lifecycle transitions (H-3) | enrolment → lifecycle_stage='incubation'; graduation → next stage (e.g. validation/acceleration/investment_ready). Programs WRITE to the SINGLE lifecycle authority (D-063); they do NOT create a parallel state. Transitions explicit + audited |

## 3. TRAINING INTEGRATION (D-059)

| Aspect | Design |
|---|---|
| Course requirements | a program defines required Training courses (a thin `program_required_courses` link); curriculum delivered by Training (no parallel LMS, D-038) |
| Certification requirements | program/cohort certificates reuse Training's D-059 governance — NO new cert system |
| Completion thresholds | graduation gated by training completion (% of curriculum / required certs) — read from Training enrollments/progress; access stays TrainingAccessService |

## 4. COMMUNITY INTEGRATION (D-035)

| Aspect | Design |
|---|---|
| Mentors / advisors | startup_mentors (type mentor/advisor, M-3) — assignment audited; notes private |
| Program visibility | PUBLIC program/cohort meta (name, dates, description) via a public projection; **cohort internals (startup performance, scores, milestones) are PRIVATE** to the cohort startup + staff (no cross-startup leakage, L-1) |

## 5. CRM INTEGRATION (D-053)

| Aspect | Design |
|---|---|
| Pipeline movement | incubator startups already fire one-way CRM leads (Wave 5A StartupCreated); program progress does NOT flow back to the startup from CRM |
| Internal management | ICS manages relationships in the internal CRM (assignment-scoped, D-053) — separate from the Startup Hub/program management surface |
| Assignment model | program coordinator / mentor assignment is a PROGRAM concern (assigned_by on the program/mentor record), NOT CRM HasAssignmentVisibility (M-5). Do not conflate |

## 6. MARKETPLACE INTEGRATION (D-060)

| Aspect | Design |
|---|---|
| Opportunity recommendations | surface relevant marketplace listings (grants/funding) to cohort startups — reuse the existing public Marketplace + (deferred) AI matching |
| Grant/funding readiness | a readiness signal (lifecycle_stage + program progress) suggests eligible opportunities; applications use the existing marketplace_applications flow (private, D-060) |

## 7. ACCESS CONTROL REVIEW (A vs B)

**Question:** reuse StartupAccessService (A) OR extend the participation family with a reusable
`ProgramParticipationService` (B)?

| | Option A — reuse StartupAccessService | Option B — thin ProgramParticipationService |
|---|---|---|
| Shape | overload startup service with program logic | dedicated service for "is this startup enrolled, and is the user on its team?" |
| Coupling | program rules bleed into startup membership; bloats as Accelerator adds rules | clean separation; composes WITH StartupAccessService |
| Accelerator reuse (item 11) | would re-overload the same service | **reused directly by Accelerator** (specialization) |
| Family | participation family | participation family (same family, distinct relationship) |
| New family? | No | No |

**Recommendation: OPTION B — a thin `ProgramParticipationService` in the participation family**
(conforming to the proposed `ParticipationGate` contract). It composes with StartupAccessService
(`participatesInProgram(user, program)` = the user is on a team of a startup with an ACTIVE
enrollment), is reused unchanged by Accelerator, and keeps StartupAccessService focused. **No new
access-control family** (Validation: participation-family reuse confirmed).

## 8. AUDIT REVIEW

The user proposed `INCUBATOR_MANAGEMENT`. **Recommendation: use ONE `PROGRAM_MANAGEMENT` category
instead** — because Accelerator is a specialization of the same architecture (item 11/Option B),
separate INCUBATOR_/ACCELERATOR_ categories would duplicate. Program type is carried in the event
detail. (H-2 — decision point.)

| Event | Sensitivity |
|---|---|
| Intake acceptance / rejection | normal |
| Enrollment / withdrawal | normal |
| **Graduation** | normal (audited) |
| Mentor / advisor assignment | normal |
| Program-driven lifecycle transition | normal (writes to D-063 authority) |
| **Forced removal / program termination of a startup** | **HIGH** |
| Program fee events (when Billing lands) | HIGH (financial) |

## 9. ANALYTICS REVIEW (D-025 / W4-9)

- **`ProgramAnalyticsAggregator`** (reused by Accelerator) — own aggregator, NOT
  content_engagement_events.
- Cohort metrics: cohort size, fill rate, intake acceptance rate.
- Graduation metrics: graduation rate, time-to-graduate.
- Retention metrics: withdrawal/drop-off rate, active-through-program rate.
- Startup progression: lifecycle_stage advancement during/after program.
- No financial/ownership data in projections (C-1).

## 10. AI READINESS (D-029 — seams only)

| Seam | Substrate |
|---|---|
| Startup scoring | ai_assessments + program progress |
| Readiness scoring | milestones + training completion + cohort performance |
| Mentor matching | Community skills + startup needs |
| Risk detection | drop-off / slippage signals (cohort retention) |

Deferred to the AI sprint; structured fields shaped accordingly.

## 11. FUTURE ACCELERATOR COMPATIBILITY  ★ (most important)

**Recommendation: OPTION B — Accelerator is a SPECIALIZATION of the same Program Architecture,
NOT a separate architecture.**

Justification:
- `startup_programs.type` already covers both incubator and accelerator (one schema).
- D-038 (no duplication) + D-019 (both extend Startup Hub) — a separate accelerator architecture
  would duplicate programs/cohorts/intake/enrollment/graduation/participation/audit/analytics =
  significant tech debt + drift risk.
- **Build the generic Program Architecture ONCE in Wave 5B**; Accelerator (5c) adds only its
  specializations (demo day, investor showcase → Investment Network bridge, more intensive
  mentorship cadence) on top — it reuses ProgramParticipationService, PROGRAM_MANAGEMENT audit,
  and ProgramAnalyticsAggregator unchanged.
- Option A (separate architecture) is **rejected** — it violates the no-duplication principle.

This is the defining condition of Wave 5B: implement the program layer as GENERIC so 5c is a thin
specialization.

## 12. FUTURE TenantScope COMPATIBILITY (D-037)

- startup_programs carries tenant_id; enrollments inherit via the startup.
- **Franchise:** each franchise runs its own incubator programs (tenant-scoped).
- **Regional incubators:** region as an attribute (+ future tenant/region dimension); no access
  impact.
- **Multi-country programs:** country attributes on programs/startups; analytics gain a
  country/region dimension. All additive; nests above participation (tenant > program-participation
  > user); no schema change to enable (D-037).

---

## MANDATORY FINDINGS

| ID | Severity | Finding | Disposition |
|---|---|---|---|
| **H-1** | HIGH | Build a GENERIC Program Architecture (not incubator-specific) so Accelerator specializes it (item 11, Option B) | the defining condition of 5B |
| **H-2** | HIGH | Use ONE `PROGRAM_MANAGEMENT` audit category (not INCUBATOR_/ACCELERATOR_) — avoid duplication | decide at approval (recommend PROGRAM_MANAGEMENT) |
| **H-3** | HIGH | Program transitions WRITE to the single lifecycle authority (D-063); no parallel program state machine | enforce |
| **M-1** | MEDIUM | Intake/selection step (applied→accepted→active) — governed + audited, not a direct enrol | extend enrollment status OR thin program_applications |
| **M-2** | MEDIUM | Access = thin ProgramParticipationService (Option B, participation family) | recommended over overloading StartupAccessService |
| **M-3** | MEDIUM | Completion thresholds gate graduation via Training (D-059) — no parallel LMS | reuse Training |
| **M-4** | MEDIUM | Cohort modelling — program-per-cohort (simple) vs program_cohorts table (if many cohorts) | choose at impl; default program-per-cohort |
| **M-5** | MEDIUM | Coordinator/mentor assignment is a program concern, NOT CRM HasAssignmentVisibility | keep boundaries (D-053) |
| **L-1** | LOW | Cohort internals (performance/milestones) private; no cross-startup leakage; program meta public | scope reads |
| **L-2** | LOW | Marketplace recommendations reuse existing flow; AI matching deferred | reuse + seam |
| **L-3** | LOW | AI seams deferred (D-029) | seams only |

### Conflicts / collisions / risks
- **Architectural conflict:** building incubator-specific instead of generic (H-1) — would force
  accelerator rework; mitigated by the generic Program Architecture.
- **Schema collisions:** none — extends existing startup_programs/enrollments; optional thin
  extensions (program_applications, program_required_courses, program_cohorts) are additive.
- **Governance risks:** intake selection fairness (audited), graduation/withdrawal integrity,
  program-fee handling deferred to Billing (D-031).
- **Data leakage risks:** cohort performance / startup internals must not leak across startups or
  to the public (L-1); mentor notes private.
- **Implementation risks:** over-specializing for incubator (H-1); conflating program coordinator
  assignment with CRM assignment (M-5).

---

## FINAL VERDICT

**SOUND WITH CONDITIONS.**

Conditions:
1. **H-1** — implement a GENERIC Program Architecture (programs/cohorts/intake/enrollment/
   graduation/participation/audit/analytics); Incubator is `type='incubator'`; Accelerator (5c)
   specializes it (Option B). No separate accelerator architecture.
2. **H-2** — single `PROGRAM_MANAGEMENT` audit category (not INCUBATOR_MANAGEMENT).
3. **H-3** — program transitions write to the single lifecycle authority (D-063); no parallel state.
4. **M-1/M-2** — governed intake step; access via a thin `ProgramParticipationService`
   (participation family; no new mechanism).

Access: **participation-family reuse confirmed; no new access-control family.** No conflict with
D-019/D-025/D-037/D-038/D-053/D-057/D-059/D-061..D-064.

Proposed decisions to ratify on approval (NOT now):
- **D-065** — Generic Program Architecture; Incubator + Accelerator are program-type
  specializations (Option B); thin ProgramParticipationService (participation family).
- **D-066** — `AuditCategory::PROGRAM_MANAGEMENT` (covers incubator + accelerator; forced
  removal / program termination + fee events HIGH).

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement Incubator until approved and conditions
H-1/H-2/H-3/M-1/M-2 (D-065/D-066) are decided.**
