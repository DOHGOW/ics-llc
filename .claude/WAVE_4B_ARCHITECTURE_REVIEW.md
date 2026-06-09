# WAVE 4B ARCHITECTURE REVIEW — COMMUNITY PLATFORM
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-02
Status: Architecture / Design — Awaiting Approval (NO Wave 4b code in this wave)
Author: Lead Architect
Decision References: D-012, D-022, D-025, D-029, D-035, D-037, D-046, D-053, D-057, D-058; W4-4
Scope under review: Community Platform (community_profiles + 6 CTI extensions, skills,
profile_skills, endorsements)

Interpretation: the deliverable is an ARCHITECTURE REVIEW with an explicit "do not implement
Wave 4b yet / wait for approval" gate. This is the Wave 4b DESIGN.

---

## ⚠ THE CRITICAL FOCUS OF THIS WAVE

Community is, by D-035, the **connective tissue** linking Startup Hub, Training, Partner
Portal, Research Center, and CRM. That makes **cross-module link security** the single
highest risk: a community profile that links to `partner_profiles`, `training_instructors`,
`research_authors`, or fires a CRM lead MUST surface **only public profile fields** and leak
NOTHING from those modules' protected data (partner referrals/commissions, CRM leads/
assignment, learner data, restricted research). The same one-way boundary proven in W2-3
(partner→CRM) and reaffirmed in W4-4 applies here, broadened to five modules.

Community also adds a FIFTH module-local access rule — **visibility-scoped + owner-scoped**
— which must stay separate from the now-four proven mechanisms (AccountScope,
ContentAccessService, HasAssignmentVisibility, TrainingAccessService). Community is NOT
ContentAccessible (D-057).

---

## EXECUTIVE SUMMARY

Wave 4b designs the Community Platform as D-035 class-table-inheritance profiles: a base
`community_profiles` + six type extensions, plus a skills/endorsements graph. Access is
**visibility (public/authenticated) + owner (user_id)** — a simple module-local rule, not
the content engine. The design's hard problems are all link-security: cross-module links
must be one-way and public-only (W4b-1), and a user may only link to module records they
actually own or that ICS verifies (W4b-2). Verification/suspension are staff governance
events audited under COMMUNITY_MANAGEMENT (D-058); endorsements are analytics, not audit.
Mentorship and collaboration are D-035 Phase-2 seams (no build). Verdict: **SOUND,
conditional on W4b-1/W4b-2 at implementation; no new decisions required.** No code this wave.

---

## 1. COMMUNITY PROFILE ARCHITECTURE (D-035)

| Element | Design |
|---|---|
| Base | community_profiles (user_id UNIQUE, profile_type, display_name, tagline, bio, avatar, visibility, is_verified, status, view/follower counters); FULLTEXT(display_name, tagline, bio) |
| Six types | founder, startup, consultant, trainer, partner, researcher — each a `community_{type}_profiles` extension (profile_id UNIQUE) |
| Skills graph | community_skills (reference) → community_profile_skills (M2M, endorsement_count) → community_endorsements (peer, unique profile+skill+endorser) |
| Counters | view_count, follower_count, endorsement_count cached on rows |
| Status | active / suspended / hidden (moderation) |

## 2. CTI VALIDATION

- **Pattern:** base + exactly ONE extension per profile, matching `profile_type`
  (D-035). A consultant profile has a community_consultant_profiles row and no other
  extension. Query = base JOIN the type's extension.
- **W4b-4 (integrity):** creation must enforce profile_type ↔ extension consistency (one
  extension, correct table) inside a transaction; orphan/mismatched extensions are rejected.
- **Extensibility:** a new profile type = a new extension table + enum value only — no change
  to the base or existing types (D-035 goal preserved).
- **Soundness:** profile_id UNIQUE on each extension enforces 1:1; base user_id UNIQUE
  enforces one profile per user.

## 3. VISIBILITY MODEL (D-057)

- `visibility` ∈ {public, authenticated}; `status` ∈ {active, suspended, hidden}.
- **Public read scope:** `status = active AND (visibility = public OR <authenticated user>)`.
  Guests see public+active only; authenticated users see authenticated+active too;
  suspended/hidden are never publicly listed.
- **Owner + staff bypass:** the owner always sees their own profile (any status); ICS staff
  see all (moderation).
- **W4b-5:** this is a lightweight module-local scope — NOT ContentAccessService (no tiers,
  no lifecycle). Community is identity data, not tiered content (D-057). It must not touch
  AccountScope / HasAssignmentVisibility / TrainingAccessService either.

## 4. OWNERSHIP MODEL

- `community_profiles.user_id` UNIQUE → the user OWNS the profile (owns by user_id).
- Create: a user creates their own single profile. Update/delete: owner or ICS staff.
- Verification + moderation (is_verified, status) are ICS-staff-only governance actions.
- No org-ownership (no AccountScope); no assignment; no tier. A community profile is personal
  identity, even when it links to an organisation record (the link is a reference, §5).

## 5. CROSS-MODULE LINK SECURITY (W4b-1 / W4b-2 — the critical section)

Extension link pointers (nullable FKs / events):
| Type | Links to | Module |
|---|---|---|
| founder / startup | startup_id → startup_profiles | Startup Hub |
| trainer | instructor_id → training_instructors | Training |
| partner | partner_id → partner_profiles | Partner Portal |
| researcher | author_id → research_authors | Research Center |
| consultant | (event) ConsultantProfileCreated → CRM lead | CRM (D-012/D-053) |

**W4b-1 (CRITICAL) — one-way, public-only projection.** A community profile surfaces ONLY
the public fields stored ON its own extension (e.g. partner extension: organisation_name,
partnership_types, service_areas, coverage_regions). It MUST NEVER join into and expose the
linked module's protected data:
- partner → NEVER partner referrals, commissions, agreements, or account_id (W2-3).
- researcher → NEVER restricted/internal research or research workflow (D-034 tiers).
- trainer → NEVER enrolments, learner data, or assessment internals.
- founder/startup → only public startup fields.
Serializers WHITELIST public fields; cross-module data is never lazy-loaded into a
community response.

**W4b-2 (HIGH) — link integrity / anti-impersonation.** A user may set a link pointer ONLY
to a module record they actually own (e.g. partner_id whose partner_profiles.user_id =
self; instructor_id whose training_instructors.user_id = self; author_id tied to self), OR
the link requires ICS verification before it is trusted/displayed. This prevents a user
falsely associating their public profile with another org's partner/trainer/researcher
record.

**W4b-3 — consultant → CRM is one-way (D-053).** Creating a consultant profile may fire
ConsultantProfileCreated → CRM lead capture; the consultant NEVER sees the resulting
crm_lead (the W2-3/D-053 internal-CRM boundary). The community side holds no CRM fields.

## 6. AUDIT ARCHITECTURE (D-046 / D-058)

- Category `AuditCategory::COMMUNITY_MANAGEMENT` (added in Wave 4a, D-058).
- **Governance events audited:** ProfileVerified, ProfileSuspended / ProfileHidden
  (moderation), and ConsultantProfileCreated (the CRM seam). Verification/suspension are
  the accountability-bearing actions.
- **NOT audited (W4b-6):** endorsements, profile views, follows — high-volume engagement →
  analytics, not the governance trail. (Avoids audit-log flooding.)
- Mechanism reuse: domain events → AuditEventSubscriber → append-only AuditService. Super
  Admin actions remain HIGH automatically.

## 7. ANALYTICS ARCHITECTURE (D-025 / W4-9)

- **Own aggregator (CommunityAnalyticsAggregator), NOT content_engagement_events** — community
  profiles are not ContentAccessible (W4-9).
- KPIs: profiles by type, verified count, profile views, followers, endorsements, skill
  distribution, active vs suspended.
- D-025 discipline: scheduled aggregation; dashboards read persisted aggregates; cached
  counters (view_count/follower_count/endorsement_count) for cheap reads.

## 8. AI READINESS (D-029)

- Feature space present: skills graph, `seeking` (founder/startup), `expertise_areas`
  (consultant), `research_areas` (researcher), `specializations` (trainer/partner) — all
  JSON, plus FULLTEXT bio.
- Seams for AI mentorship/connection matching (D-029 matching pattern); `ai_requests` cost
  tracking + config caps already exist. **No AI calls in Wave 4b** (deferred to AI sprint).

## 9. FUTURE MENTORSHIP COMPATIBILITY (D-035 reserved)

- `community_mentorships` is a RESERVED Phase-2 table (mentor profile ↔ mentee profile, AI-
  matched via D-029). Wave 4b builds profiles + skills + endorsements — the actor + feature
  space mentorship needs. Adding mentorship later = a new table; **no schema change** to the
  profiles (D-037 spirit). Consultant `availability`/`engagement_types` pre-seam it.

## 10. FUTURE COLLABORATION COMPATIBILITY (D-035 reserved)

- `community_collaborations` (collaboration requests) + messaging (D-022) and
  `community_forums`/posts/replies are RESERVED Phase 2. Wave 4b leaves profiles as the
  actors; no build. Opportunity-sharing cross-posts from the Marketplace (Wave 4c) reuse
  `marketplace_listings.shared_by_profile_id` — no new table (D-035).

---

## VALIDATION MATRIX (as requested)

| Item | Validation | Result |
|---|---|---|
| **D-035** | CTI base + 6 extensions; visibility; verification; cross-module links; reserved features | ✅ |
| **D-025** | own aggregator (NOT content_engagement_events, W4-9); scheduled | ✅ |
| **D-029** | matching seams (skills/seeking JSON); deferred | ✅ |
| **D-037** | tenant_id on community_profiles; additive TenantScope; no schema change | ✅ |
| **D-046** | COMMUNITY_MANAGEMENT verify/suspend audited; endorsements not audited | ✅ |

---

## FINDINGS

| ID | Finding | Severity | Disposition |
|---|---|---|---|
| W4b-1 | Cross-module links surface ONLY public extension fields; never leak CRM/portal/content internals | **CRITICAL** | serializers whitelist; no cross-module lazy-load |
| W4b-2 | Link integrity — user may only link to records they own, or ICS-verified | **HIGH** | validate ownership / gate on verification |
| W4b-3 | consultant → CRM lead is ONE-WAY (D-053); consultant never sees the lead | MEDIUM | event-only seam; no CRM fields on community |
| W4b-4 | CTI integrity — exactly one extension per profile, matching profile_type | MEDIUM | transactional creation; reject mismatch |
| W4b-5 | Visibility is module-local (public/authenticated + status); NOT ContentAccessService | MEDIUM | lightweight scope + owner/staff bypass |
| W4b-6 | Endorsements/views/follows NOT audited (analytics only); governance events audited | LOW | avoid audit flooding |
| W4b-7 | Suspended/hidden profiles excluded from public listing | LOW | moderation scope |
| W4b-8 | Own analytics aggregator (W4-9) | MEDIUM | per-module aggregator |
| W4b-9 | Mentorship/collaboration/forums reserved (D-035 Phase 2) | LOW | seams only; no build |

---

## RISKS

| Risk | Mitigation |
|---|---|
| Community leaks partner/CRM/research internals | W4b-1 public-only whitelisted projection; one-way links |
| Impersonation via false module links | W4b-2 ownership validation / ICS verification |
| Visibility rule drifts into the content engine | W4b-5 module-local scope; four proven mechanisms untouched |
| CTI orphan/mismatch | W4b-4 transactional, type-checked creation |
| Audit-log flooding from endorsements | W4b-6 audit governance events only |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Community = visibility + owner scoped; NOT ContentAccessible; four mechanisms untouched | ✅ |
| Cross-module links one-way + public-only (W4b-1) | ✅ design |
| D-035 / D-025 / D-029 / D-037 / D-046 validated/compatible | ✅ |
| Wave 4b NOT implemented; Wave 4c (Marketplace) NOT implemented | ✅ |
| D-049 validation gate (bootstrap + GREEN CI) still in force | ⚠ carried |

---

## REVIEW VERDICT

**SOUND DESIGN — conditional on W4b-1 (cross-module link security) and W4b-2 (link
integrity) at implementation; NO new decisions required.** Community is correctly modelled
as CTI identity data with a module-local visibility+owner rule that leaves the four proven
mechanisms untouched, surfaces cross-module links one-way and public-only, audits governance
events under COMMUNITY_MANAGEMENT, and reserves mentorship/collaboration for Phase 2. Cleared
to proceed to Wave 4b implementation after approval.

Carry into implementation (no decision needed):
- **W4b-1:** whitelisted public-only projection of cross-module links; never lazy-load
  linked-module protected data into a community response.
- **W4b-2:** validate the linking user owns the linked record (or require ICS verification).

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement Wave 4b until approved.**
