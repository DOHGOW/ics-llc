# WAVE 5A ARCHITECTURE REVIEW — STARTUP HUB
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-03
Status: Architecture review — NO code/migrations/models/controllers/services. Design only.
Author: Lead Architect
Validates against: D-035, D-037, D-038, D-050, D-053, D-055, D-057, D-059, D-060
Inputs: ECOSYSTEM_ROADMAP_REVIEW.md, ACCESS_CONTROL_CONSOLIDATION_REVIEW.md, WAVE_5_ARCHITECTURE_PLAN.md
Existing schema: startup_profiles, startup_team_members, startup_milestones, startup_mentors,
startup_programs, startup_program_enrollments

---

## EXECUTIVE SUMMARY

Startup Hub is the foundation of Wave 5: founder-owned startups with teams, milestones,
mentorship, and program participation. Its access need is the **membership/participation
family** — confirmed reusable, requiring **no new access mechanism**. The design is sound, with
conditions: (a) **ownership-percentage / cap-table data is sensitive financial information and
must NOT live unprotected in Startup Hub** — it belongs to the Investment Network's gated
data-room (5d), or must be heavily gated; (b) the requested **7-stage lifecycle must be
reconciled** with the three existing overlapping enums (status/stage/program_type) into one
authoritative model; (c) **founder departure / ownership transfer is governance-sensitive** and
must be explicit + audited HIGH; (d) **startup_profiles must stay distinct from crm_accounts**
(founder-owned, not account-owned; CRM link one-way per D-053). **Verdict: SOUND WITH
CONDITIONS.**

---

## DECISION-COMPLIANCE VALIDATION

| Decision | Requirement | Startup Hub | Verdict |
|---|---|---|---|
| **D-035** Community | Founder/startup community profiles link to startup_profiles (one-way, public-only W4b-1) | Startup Hub is the source of truth; Community is the public projection; links validated by ownership (W4b-2) | ✅ |
| **D-037** Config-only | tenant_id present; env-driven; no schema change to enable tenancy | startup_profiles/programs carry tenant_id; TenantScope-ready | ✅ |
| **D-038** Unified Content Engine | content lifecycle implemented once; do not fork | Startup Hub is NOT tiered content → does NOT use ContentAccessService; milestones/programs are not "content" | ✅ (correctly NOT reused) |
| **D-050** Account Ownership | org isolation by account_id where org-owned | Startup Hub is **founder-owned, NOT account-owned** → NO AccountScope/BelongsToAccount | ✅ (must stay this way) |
| **D-053** CRM Assignment | account_id on CRM = subject; CRM internals one-way | startup→CRM lead is ONE-WAY (founder never sees the internal lead/pipeline) | ✅ |
| **D-055** Portal Ownership | partners get a crm_account; portals org-owned | Startup is NOT a portal org; does not adopt the portal/AccountScope model | ✅ |
| **D-057** Module Access | module-local, reuse a family; no new mechanism unless necessary | participation family (founder + team + program) | ✅ |
| **D-059** Certificate Governance | one certificate system (Training) | program/cohort certificates REUSE Training's D-059 governance — no parallel cert system | ✅ |
| **D-060** Marketplace Trust | listings reviewed; applications private | startups apply to opportunities via the existing marketplace_applications flow (applicant=founder) | ✅ |

---

## 1. STARTUP LIFECYCLE MODEL  (⚠ reconciliation needed — HIGH)

Requested lifecycle: **idea → registered → validation → incubation → acceleration →
investment_ready → alumni.**

Existing enums on startup_profiles overlap and conflict:
- `stage` (idea/mvp/growth/scale/exit) — product/maturity stage
- `status` (pending/active/graduated/inactive) — admin state
- `program_type` (general/incubator/accelerator) — program track

**Finding (H-1):** the requested 7-stage lifecycle is a DIFFERENT axis (the startup's journey
through ICS) and maps to none of the three cleanly. Recommendation: introduce ONE authoritative
`lifecycle_stage` enum (idea/registered/validation/incubation/acceleration/investment_ready/
alumni) as the journey axis; keep `stage` as product maturity (distinct, legitimate); RETIRE or
narrow `status`/`program_type` overlap (program track derives from startup_program_enrollments,
not a duplicated column). **Do not keep three overlapping enums** (technical debt). This is a
blueprint reconciliation to ratify at implementation (proposed D-063).

- Transitions are explicit + permission-gated (no implicit jumps); incubation/acceleration
  transitions are driven by program enrollment (5b/5c); investment_ready gates Investment
  Network exposure (5d, opt-in); alumni = graduated + program complete.

## 2. FOUNDER RELATIONSHIP MODEL

| Aspect | Design |
|---|---|
| Founder ownership | startup_profiles.founder_id = the OWNER (single primary owner) |
| Multiple founders | co-founders via startup_team_members.is_founder=1 (already supported) |
| Founder role hierarchy | startup_team_members.role (free text → recommend an enum: founder/co_founder/admin/member) |
| Founder invitation | **NEW flow needed** — invite a user to a startup team (invitation token + accept). Recommend a `startup_team_invitations` extension (status pending/accepted/expired) rather than direct insert |
| Founder departure | **governance-sensitive (H-2)** — if the departing user is `founder_id`, ownership MUST transfer to another founder/co-founder before removal; a startup is NEVER orphaned. Audited HIGH |

**Finding (H-2):** ownership transfer on founder departure is a high-sensitivity governance
action (who controls the startup). It must be an explicit, audited operation; deleting the
primary founder without transfer is rejected.

## 3. STARTUP GOVERNANCE MODEL

| Aspect | Design |
|---|---|
| **Ownership percentages** | ⚠ **CRITICAL (C-1)** — cap-table data is SENSITIVE FINANCIAL info. It must NOT be stored unprotected in Startup Hub or exposed to Community/public. **Defer cap-table to the Investment Network data-room (5d)** under its NDA/redaction/grant overlay; if a minimal ownership signal is needed in 5a, gate it to founders/staff only and exclude from all public/community projections |
| Advisory board | extend `startup_mentors` with a `role`/`type` (mentor / advisor) — NOT a new parallel table (D-038 no-duplication) |
| Mentors | startup_mentors (assignment audited; mentor notes private to startup/mentor/staff) |
| Program participation | startup_program_enrollments (the membership key for incubation/acceleration) |

## 4. STARTUP MEMBERSHIP ARCHITECTURE  (Mandatory Validation A & B)

**A — Reuse the participation family? YES.** Startup Hub access is exactly "is the user a
participant in this startup/program?" — the same shape as TrainingAccessService and as
identified in ACCESS_CONTROL_CONSOLIDATION_REVIEW.md. A thin **StartupAccessService** answers:
- `isFounderOrTeam(user, startup)` → founder_id OR an accepted team member
- `participatesInProgram(startup, program)` → an active startup_program_enrollment
- staff/owner bypass; default-deny; NEVER falls back to AccountScope.

**B — New access mechanism required? NO.** It maps cleanly to the membership/participation
family. Comparison:
| Mechanism | Fit | Why not |
|---|---|---|
| AccountScope | ✗ | startups are founder-owned, not crm_account-owned (would mean the wrong thing, D-050/D-053 lesson) |
| ContentAccessService | ✗ | startups/milestones are not tiered content |
| HasAssignmentVisibility | ✗ | not internal CRM assignment |
| **TrainingAccessService (membership family)** | ✓ | same "is the user a participant?" shape |
| Community visibility | ✗ | identity visibility, not team participation |
| Marketplace listing-status | ✗ | no review/publish workflow |

**Migration impact: none** — reuse the pattern; optionally conform to the proposed
`ParticipationGate` contract from the consolidation review. No new global scope, no schema-wide change.

## 5. COMMUNITY INTEGRATION (D-035)

- Founder + startup **Community** profiles (community_founder_profiles / community_startup_profiles)
  LINK to startup_profiles via the link pointers validated under W4b-2 (the user must own the
  startup). The Community side is the **public projection** (W4b-1 publicFields only).
- **Public vs internal startup data (M-1):** PUBLIC = name, tagline, sector, public stage,
  logo (the curated Community page). INTERNAL = mentor notes, internal milestones, financials/
  cap table, program internals — founder/team/staff only; NEVER on the Community/public surface.
- Verification: ICS verifies the startup (reuse the Community verification + a Startup Hub
  status); a verified badge is display-only and leaks nothing internal.

## 6. CRM INTEGRATION (D-053)

- **Lead creation:** a new startup (or an interest signal) may fire a ONE-WAY CRM lead
  (source='startup'), mirroring consultant→CRM (W4b-3). The founder NEVER sees the internal lead.
- **Pipeline conversion:** ICS staff work the lead in the internal CRM (assignment-scoped,
  D-053); none of that surfaces to the startup.
- **Startup account creation:** if ICS engages the startup commercially, a separate crm_account
  may be created — but **startup_profiles ≠ crm_accounts** (H-3). They are linked by reference
  only; the Startup Hub entity stays founder-owned, the crm_account stays internal/sales.
- **Ownership boundaries:** Startup Hub (founder-owned) and CRM (internal, assignment-scoped)
  never share an access mechanism; the link is one-way provenance.

## 7. MARKETPLACE INTEGRATION (D-060)

- **Opportunity applications:** a founder applies to marketplace opportunities via the existing
  marketplace_applications flow (applicant = founder user) — private, unique per listing,
  attachments streamed (D-060/W4-7). No new application model.
- **Startup visibility:** a startup appears publicly only via its Community page / accelerator
  showcase (opt-in) — not by being in Startup Hub.
- **Opportunity matching:** AI matching (ai.marketplace.match) of startups↔opportunities is a
  seam (deferred); uses public startup attributes + listing text.

## 8. TRAINING INTEGRATION (D-059)

- **Certifications:** program/cohort completion issues certificates through Training's D-059
  governance (numbering/verification/revocation/expiry/reissue) — NO parallel certificate system.
- **Program readiness:** course completion / assessment results can gate program admission
  (a readiness input).
- **Learning progress:** training enrollments/progress feed startup readiness analytics; access
  remains TrainingAccessService (enrollment) — Startup Hub reads aggregates, never the raw
  enrollment internals of unrelated users.

## 9. ANALYTICS ARCHITECTURE (D-025 / W4-9)

- **Own aggregator** (StartupAnalyticsAggregator), NOT content_engagement_events.
- Startup KPIs: startups by lifecycle_stage / sector, active vs alumni, milestone completion rate.
- Program KPIs: cohort fill, progress, graduation rate, drop-off.
- Graduation metrics: time-to-graduate, graduation→funding (with 5d).
- Funding-readiness metrics: AI readiness score distribution (from ai_assessments), milestone +
  training completion — aggregate only; NO cap-table/financial PII in dashboards.

## 10. AUDIT ARCHITECTURE (D-046 / D-058 pattern)

- **Propose `AuditCategory::STARTUP_MANAGEMENT`** (D-062).
- **HIGH-sensitivity events:** founder departure / **ownership transfer**, ownership-% change
  (if any signal is stored), startup verification, program graduation outcome.
- Normal: mentor assignment, milestone status, program enrollment/withdrawal.
- Engagement (profile views) = analytics, NOT audit (W4b-6 discipline).
- Reuse AuditEventSubscriber + append-only AuditService (forceSensitivity for HIGH events, D-056).

## 11. AI READINESS (D-029 — seams only)

| Seam | Substrate |
|---|---|
| Startup scoring / readiness (D-029 #8) | ai_assessments (exists); profile + milestones + training |
| Funding readiness | milestones + financ. signals (gated) + program outcomes |
| Mentor matching | Community skills + startup needs/sector |
| Risk assessment | milestone slippage, churn, financial gating |

No AI calls in 5a; structured/JSON fields shaped so the AI sprint consumes without migration.

## 12. FUTURE TenantScope COMPATIBILITY (D-037)

- tenant_id on startup_profiles/programs; the participation access keys (founder_id, team,
  enrollment) are orthogonal to tenant_id → a future TenantScope composes ABOVE with no rework
  (tenant > startup-participation > user).
- **Franchise compatibility:** a franchise tenant operates its own Startup Hub; no design choice
  assumes a single tenant (cohorts/programs are tenant-scoped).
- **Multi-region:** region is an attribute (country_code) + (future) tenant/region dimension in
  analytics; no access-model impact.

---

## MANDATORY VALIDATION — C, D, E

### C. Conflicts / collisions / leakage / governance
| Type | Item |
|---|---|
| **Conflict** | three overlapping lifecycle enums (status/stage/program_type) vs the requested 7-stage journey (H-1) |
| **Conflict** | Incubator/Accelerator must extend startup_programs (types), not fork (roadmap C-1) — relevant to 5b/5c |
| **Schema collision** | none new in 5a (schema exists); `startup_profiles` must NOT be conflated with `crm_accounts` (H-3) |
| **Leakage (CRITICAL)** | cap-table / ownership % to public/Community/non-granted parties (C-1) |
| **Leakage** | mentor notes / internal milestones on the public page (M-1) |
| **Governance** | founder departure / ownership transfer (H-2); startup verification integrity |

### D. Explicit findings
| ID | Severity | Finding | Disposition |
|---|---|---|---|
| **C-1** | **CRITICAL** | Ownership %/cap-table is sensitive financial data | Defer to Investment Network gated data-room (5d) OR gate to founder/staff + exclude from all public/community/analytics; ratify at impl (D-063) |
| **H-1** | HIGH | Lifecycle vs 3 overlapping enums | Introduce one authoritative `lifecycle_stage`; reconcile status/stage/program_type; no duplication (D-063) |
| **H-2** | HIGH | Founder departure = ownership transfer | Explicit, audited-HIGH transfer; never orphan a startup |
| **H-3** | HIGH | startup_profiles ≠ crm_accounts | Founder-owned, NOT account-owned; CRM link one-way (D-053); no AccountScope |
| **M-1** | MEDIUM | Public vs internal startup data | Community public-only (W4b-1); internal to founder/team/staff |
| **M-2** | MEDIUM | Founder invitation flow | Add startup_team_invitations (token + accept); not direct insert |
| **M-3** | MEDIUM | Advisory board vs mentors | Extend startup_mentors with role/type; no new table (D-038) |
| **M-4** | MEDIUM | Team role hierarchy | Promote role free-text → enum (founder/co_founder/admin/member) |
| **L-1** | LOW | Program participation reuse | startup_program_enrollments is the membership key (5b/5c extend) |
| **L-2** | LOW | AI seams | ai_assessments + JSON fields; deferred (D-029) |

### E. FINAL VERDICT

**SOUND WITH CONDITIONS.**

Conditions to satisfy at/before Wave 5A implementation:
1. **C-1** — cap-table / ownership % is NOT built into public Startup Hub; deferred to the
   Investment Network gated data-room (5d) or gated to founder/staff and excluded from public/
   community/analytics.
2. **H-1** — reconcile the lifecycle into ONE authoritative `lifecycle_stage` (no three
   overlapping enums).
3. **H-2** — founder departure performs an explicit, audited-HIGH ownership transfer; no orphans.
4. **H-3** — keep startup_profiles distinct from crm_accounts; founder-owned, no AccountScope,
   CRM link one-way (D-053).

Access: **reuse the participation family** (StartupAccessService) — NO new mechanism (Validation
A confirmed, B negative). No conflict with D-035/D-037/D-038/D-050/D-053/D-055/D-057/D-059/D-060.

Proposed decisions to ratify on approval (NOT now):
- **D-061** — Startup Hub access = participation family (founder + team + program; thin
  StartupAccessService; not AccountScope/ContentAccessible).
- **D-062** — `AuditCategory::STARTUP_MANAGEMENT` (ownership transfer / verification = HIGH).
- **D-063** — Startup lifecycle model (authoritative `lifecycle_stage`) + cap-table deferral to
  Investment Network + founder-transfer governance.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement Startup Hub until approved and conditions
C-1/H-1/H-2/H-3 (D-061/D-062/D-063) are decided.**
