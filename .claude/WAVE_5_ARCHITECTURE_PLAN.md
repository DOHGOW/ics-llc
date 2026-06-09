# WAVE 5 ARCHITECTURE PLAN
# ICS Enterprise Ecosystem Platform — Startup Hub · Incubator · Accelerator · Investment Network

Version: 1.0
Date: 2026-06-03
Status: Architecture plan — no code, no schema. Sequencing + boundaries for Wave 5.
Author: Lead Architect
Governing decisions: D-019, D-025, D-029, D-031, D-037, D-038, D-050, D-053, D-057, D-058, D-060
Inputs: ECOSYSTEM_ROADMAP_REVIEW.md, ACCESS_CONTROL_CONSOLIDATION_REVIEW.md

> Membership System and Franchise Operations are intentionally OUT of Wave 5 sequencing here:
> Membership is a Billing-coupled cross-cutting feature (sequence with the Billing wave), and
> Franchise is the TenantScope activation that warrants its own dedicated wave + tenancy
> governance review. This plan covers the four requested modules.

---

## BUILD ORDER

| Wave | Module | Rationale |
|---|---|---|
| **5a** | **Startup Hub** | Foundation — the base entity (startup_profiles) every other Wave-5 module extends or references |
| **5b** | **Incubator Program** | Extends Startup Hub programs (type='incubator'); smaller/earlier-stage; validates the program-participation pattern |
| **5c** | **Accelerator Program** | Extends Startup Hub programs (type='accelerator'); adds the demo-day/investor-exposure bridge |
| **5d** | **Investment Network** | Depends on Startup Hub (startups) + Accelerator (demo-day pipeline); highest compliance — built last, after a dedicated INVESTMENT_GOVERNANCE_REVIEW |

**Hard rule:** 5a before all; 5d last. 5b and 5c both extend 5a's `startup_programs` (enum
types) — they must NOT fork parallel table sets (D-038 no-duplication; roadmap conflict C-1).

---

## DEPENDENCIES

```
Startup Hub (5a)
   ├── Incubator (5b)         extends startup_programs(type=incubator) + enrollments
   ├── Accelerator (5c)       extends startup_programs(type=accelerator) + demo-day
   │        └── feeds ──► Investment Network (5d)   (demo-day → investor exposure, OPT-IN, grant-gated)
   └── referenced by ──► Investment Network (5d)    (the startups + their data rooms)

Cross-cutting (already built): Community (profiles link to startups, one-way W4b-1),
Training (curriculum delivery), CRM (investor/startup as one-way leads, D-053),
Billing/D-031 (program + deal fees — seam only until the Billing wave).
```

| Module | Depends on | External seams (deferred) |
|---|---|---|
| 5a Startup Hub | Core, Community (links) | Training (curriculum), AI readiness (D-029 #8) |
| 5b Incubator | 5a | Billing (fees), Training |
| 5c Accelerator | 5a | Investment Network (5d), Billing |
| 5d Investment Network | 5a, 5c | Billing (deal fees), CRM (one-way), legal/KYC (B-1) |

---

## ACCESS / SECURITY BOUNDARIES (per the consolidation review — reuse, don't invent)

| Module | Mechanism (REUSE) | Boundary |
|---|---|---|
| 5a Startup Hub | **Membership/participation** (thin StartupAccessService: founder-owner + team membership) | mentor notes + internal milestones owner/team/staff only; public page curated |
| 5b Incubator | program participation (enrolled startup) | cohort content scoped to participants + staff |
| 5c Accelerator | program participation | demo-day investor exposure is OPT-IN per startup |
| 5d Investment Network | **grant-based participation** (data-room grants) + **compliance overlay** | financials/cap tables NEVER to non-granted investors, public, Community, or via accelerator (leakage L-1); NDA before data-room |

- NONE use AccountScope, ContentAccessService, HasAssignmentVisibility, Community visibility, or
  Marketplace listing-status. All are the **membership/participation family** (optionally
  conforming to the proposed `ParticipationGate` contract).
- Staff/owner bypass + default-deny in every service (the family invariant).
- Files (pitch decks, data-room docs) are policy-gated + streamed (W2-5/W4-7 posture), never public.

---

## AUDIT CATEGORIES (propose at the respective wave; pattern mirrors D-054/D-056/D-058)

| Module | Proposed category | HIGH-sensitivity events |
|---|---|---|
| 5a/5b/5c Startup Hub family | `STARTUP_MANAGEMENT` | program graduation, mentor assignment, status change |
| 5d Investment Network | `INVESTMENT_MANAGEMENT` | **data-room grant/revoke, NDA acceptance, deal stage change** (financial → HIGH via the D-056 forceSensitivity override) |

- Reuse the existing AuditEventSubscriber + append-only AuditService; add handlers only.
- Engagement (profile views, interest expressions) = analytics, NOT audit (W4b-6 discipline).

---

## ANALYTICS STRATEGY (D-025 / W4-9)

- **Per-module aggregators** (StartupAnalyticsAggregator, InvestmentAnalyticsAggregator) —
  NOT content_engagement_events (these modules are not ContentAccessible).
- Startup family KPIs: startups by stage/program, milestone completion, cohort graduation rate,
  mentor load, time-to-graduate.
- Investment KPIs: pipeline (interest → data-room → deal), conversion, sector trends — **no
  per-investor PII** in dashboards (aggregate only).
- Scheduled aggregation (Laravel scheduler, the routes/console.php pattern from Wave 4c);
  dashboards read persisted aggregates.

---

## AI SEAMS (D-029 — seams only, no AI calls in Wave 5)

| Seam | Module | Substrate |
|---|---|---|
| Startup Readiness Assessment (D-029 #8) | 5a | ai_assessments table (exists); startup profile + milestones |
| Mentor matching | 5a | Community skills + startup needs |
| Curriculum recommendation | 5b/5c | Training corpus + cohort progress |
| Investor↔startup matching | 5d | startup profiles + investor theses (ai.marketplace.match-style) |
| Deal-flow scoring | 5d | pipeline data |

- All deferred to the AI sprint; `ai_requests` cost tracking + config caps already exist (D-037).
- Wave 5 leaves JSON/structured fields shaped for these (no migration needed later).

---

## FUTURE BILLING INTEGRATION (D-031 — seams)

| Module | Billable | Integration |
|---|---|---|
| 5b Incubator | program/cohort fees | invoice on enrollment (like Training paid-course seam, W4-6) |
| 5c Accelerator | program fees | same |
| 5d Investment Network | deal/success fees, data-room subscriptions | invoice on deal close / access grant |

- Pattern: create an invoice (Billing) and gate the paid action on settlement; payment execution
  belongs to the Billing wave. Wave 5 builds the free/seam path and the invoice hook only —
  exactly as Training did (paid enrolment → 402 until Billing lands).

---

## FUTURE FRANCHISE COMPATIBILITY (D-037 TenantScope)

- Every Wave-5 table carries `tenant_id` (as all platform tables do).
- All Wave-5 access services filter by their participation/grant keys ONLY — orthogonal to
  tenant_id, so a future TenantScope composes ABOVE them with no rework (tenant > account/
  participation > user; D-050 #4).
- No Wave-5 design choice may assume a single tenant (e.g., no global "the cohort" without a
  tenant dimension). This keeps Franchise activation additive (no schema change, D-037).

---

## GATING GOVERNANCE REVIEWS (before the relevant sub-wave)

| Before | Produce | Why |
|---|---|---|
| 5a | (none extra) | schema exists; standard architecture review |
| 5d Investment Network | **INVESTMENT_GOVERNANCE_REVIEW.md** | securities/financial compliance: KYC/accreditation (B-1), NDA + data-room gating, redaction, immutable audit, deal lifecycle — mirror the TRAINING_CERTIFICATION_GOVERNANCE_REVIEW gate |
| (future) Franchise | TENANCY_GOVERNANCE_REVIEW.md | tenant isolation is load-bearing; cross-tenant breach risk |

---

## PROPOSED WAVE 5 CADENCE (each step = architecture review → approval → implementation → impl review → approval)

1. **WAVE_5A_ARCHITECTURE_REVIEW** → approve → implement Startup Hub → WAVE_5A_IMPLEMENTATION_REVIEW.
2. **WAVE_5B** Incubator (extends 5a) — same cadence.
3. **WAVE_5C** Accelerator (extends 5a; investor bridge stubbed) — same cadence.
4. **INVESTMENT_GOVERNANCE_REVIEW** → **WAVE_5D_ARCHITECTURE_REVIEW** → approve → implement →
   WAVE_5D_IMPLEMENTATION_REVIEW.

Proposed decisions to be ratified at the respective reviews (NOT now):
- D-061 (proposed): Startup Hub access = founder-owner + team/program participation
  (membership family; thin StartupAccessService; not AccountScope/ContentAccessible).
- D-062 (proposed): STARTUP_MANAGEMENT + INVESTMENT_MANAGEMENT audit categories (investment
  financial events HIGH).
- D-063 (proposed): Investment Network data-room grant model + NDA/redaction compliance overlay.
- (optional) D-0xx: adopt the `ParticipationGate` conformance contract for the membership family.

---

## VERDICT

**Wave 5 is sequenced as Startup Hub → Incubator → Accelerator → Investment Network**, with each
module reusing the membership/participation access family (no new mechanism), auditing under new
per-domain categories, analytics via dedicated aggregators, AI/Billing as deferred seams, and
full forward-compatibility with the reserved TenantScope (Franchise). Investment Network is gated
behind a dedicated compliance governance review. No architectural conflict with D-037, D-038,
D-050, D-053, D-055, D-057, or D-060. Awaiting approval to begin WAVE_5A_ARCHITECTURE_REVIEW.
