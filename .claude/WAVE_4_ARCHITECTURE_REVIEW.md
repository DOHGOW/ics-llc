# WAVE 4 ARCHITECTURE REVIEW — TRAINING + COMMUNITY + MARKETPLACE
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-02
Status: Architecture / Design — Awaiting Approval (NO Wave 4 code in this wave)
Author: Lead Architect
Decision References: D-011, D-025, D-029, D-031, D-035, D-037, D-046; Wave 1a/1b/2/3 mechanisms
Scope under review: Training Institute, Community Platform, Opportunity Marketplace

Interpretation: the deliverable is an ARCHITECTURE REVIEW with an explicit "do not implement
Wave 4 yet / wait for approval after the review" gate. This is the Wave 4 DESIGN.

---

## ⚠ THE GOVERNING TENSION OF THIS WAVE

Through Wave 3 we proved **three** isolation/access mechanisms and kept them strictly
separate: **AccountScope** (org-owned), **ContentAccessService** (tiered content),
**HasAssignmentVisibility** (internal CRM). Wave 4 is the wave where **access-control
proliferation risk is highest**: each of the three new modules needs a DIFFERENT access
rule, and none of the three proven mechanisms fits. The single most important outcome of
this review is to establish those new rules cleanly — as **permission + module-specific
ownership/membership checks** — WITHOUT mixing them into (or weakening) the proven three,
and WITHOUT forcing identity/opportunity data into the content engine.

| Module | Access rule (NEW) | NOT this |
|---|---|---|
| Training | **enrollment-gated** (enrolled → lessons; is_preview public) | not tiered content, not org-owned |
| Community | **visibility-scoped** (public / authenticated) + owner (user_id) | not ContentAccessService tiers |
| Marketplace | **listing-status + review** (draft→review→published public) + owner/applicant | not org-isolated, not content lifecycle |

These are lightweight authorization RULES (policies/scopes), not new sweeping scopes. The
three proven mechanisms remain untouched.

---

## EXECUTIVE SUMMARY

Wave 4 delivers the platform's engagement surface: the **Training Institute** (courses →
enrollment → lessons → assessments → certificates), the **Community Platform** (D-035
class-table-inheritance profiles, the connective tissue across modules), and the
**Opportunity Marketplace** (D-011 submission→review→publication of grants/tenders/jobs +
applications). Each introduces its own access rule, all permission-gated and all distinct
from the proven three. Paid training defers to Billing (D-031); cross-module community links
are one-way and leak no internals; analytics use per-module counters/aggregators (NOT
content_engagement_events — these modules are not ContentAccessible). Verdict: **SOUND,
conditional on the two proposed decisions (D-057, D-058).** No code this wave.

---

## 1. TRAINING REVIEW

Tables: training_course_categories, training_courses, training_course_sections,
training_lessons, training_enrollments, training_lesson_progress, training_assessments,
training_assessment_questions, training_assessment_submissions, training_certificates,
training_instructors.

| Aspect | Design |
|---|---|
| Catalogue | published courses are PUBLIC to browse/search (FULLTEXT title, description); is_preview lessons public |
| **Access (W4-5)** | lesson/assessment content gated by ENROLLMENT (training_enrollments, unique user+course); a policy checks "is this user enrolled (active) in this course?" — not tiers, not org |
| Enrollment | free → immediate active; paid → invoice (D-031) then active on payment (W4-6); progress via training_lesson_progress |
| Assessments | quiz/assignment/exam; attempts capped; auto- or instructor-graded; **correct_answer NEVER sent to learners** (W4-5) |
| Certificates | issued on completion; certificate_number unique; verification_url PUBLIC + tamper-evident; issuance audited HIGH (credential integrity) |
| Ownership | courses ICS/instructor-managed (instructor_id; training_instructors approved by staff); learner data (enrollment/progress/submission/cert) owned by user_id |
| Roles | Trainer manages own courses (training.courses.*); Learner enrolls/reads enrolled (training.enrollments.*, training.lessons.access.enrolled); staff grade/issue |

**Enrollment is the access mechanism (W4-1/W4-5).** It is a membership check, implemented as
a policy/gate (`can('access', $lesson)` ⇒ enrolled + active, or is_preview). It is NOT a
global scope and must not be confused with AccountScope or ContentAccessService.

## 2. COMMUNITY REVIEW (D-035)

Tables: community_profiles (base) + 6 extensions (founder/startup/consultant/trainer/
partner/researcher) via **Class Table Inheritance**; community_skills, community_profile_skills,
community_endorsements (+ followers, future forums/mentorship/events RESERVED).

| Aspect | Design |
|---|---|
| **Access (W4-2)** | visibility = public \| authenticated — a simple profile-level scope; NOT ContentAccessService (no tiers, no lifecycle). Identity data, not tiered content |
| Ownership | community_profiles.user_id UNIQUE — one profile per user; the user owns it (owns by user_id); ICS verifies |
| CTI pattern | base + community_{type}_profiles extension; new type = new extension table only (D-035) |
| Verification | ICS verifies consultant/trainer/researcher (is_verified, verified_by) — required for paid services; audited (W4-8) |
| Skills/endorsements | community_skills M2M; peer endorsements; cached endorsement/follower counters |
| **Cross-module links (W4-4)** | founder/startup→startup_profiles, trainer→training_instructors, partner→partner_profiles, researcher→research_authors, consultant→CRM lead capture (D-012). Links are nullable FKs (loose coupling) and ONE-WAY — community exposes only public profile fields; it NEVER leaks Partner/CRM/portal internals (W2-3 spirit) |

**Community is identity data, not content (W4-2).** Do not route it through
ContentAccessService. A `public OR (authenticated and logged-in)` visibility scope plus
owner/staff policies is the correct, minimal control.

## 3. MARKETPLACE REVIEW (D-011)

Tables: marketplace_categories, marketplace_listings, marketplace_applications,
marketplace_listing_reviews.

| Aspect | Design |
|---|---|
| Workflow (D-011) | Submission → Review → Approval → Publication: status draft → pending_review → published → expired/rejected; marketplace_listing_reviews records each decision |
| Posting rights | ICS Admins, Approved Partners, Approved Organisations (posted_by_id + optional organisation_id → crm_accounts) |
| **Access (W4-3)** | published listings are PUBLIC (browse/search FULLTEXT); drafts/pending visible to owner + ICS reviewers. This is owner-draft + admin-review + public-publish — NOT a content tier, NOT AccountScope |
| Applications | applicant applies (unique listing+applicant); applicant owns their application; poster/ICS review (submitted→…→accepted/rejected) |
| **Files (W4-7)** | application attachments + listing files gated to applicant + poster/ICS; streamed (W2-5 posture), never public |
| Lifecycle distinct | listing states (pending_review/expired/rejected) + review records differ from HasContentLifecycle → a MarketplaceListingService, NOT the content engine |
| Community link | a listing may be shared on a community profile (shared_by_profile_id) — cross-post, no new table (D-035) |

## 4. SECURITY REVIEW

| Threat | Control |
|---|---|
| Non-enrolled user reads paid lesson | enrollment policy (W4-5); is_preview is the only public lesson |
| Learner sees assessment answers | correct_answer never serialised to learners; grading server-side (W4-5) |
| Certificate forgery | certificate_number unique + public verification_url; issuance audited HIGH |
| Community link leaks CRM/portal internals | one-way links; public profile fields only (W4-4) |
| Marketplace draft/PII exposure | drafts owner+reviewer only; application attachments gated + streamed (W4-7) |
| Unauthorised posting | posting rights enforced (ICS/approved partner/approved org); review gate before publish |
| Profile/ instructor self-approval | verification + instructor approval are staff-only, audited (W4-8) |
| Access-rule confusion | W4-1: enrollment/visibility/listing rules are module-local policies; the proven three mechanisms untouched |

## 5. AUDIT REVIEW (D-046)

Governance events to record:

| Module | Event | Sensitivity |
|---|---|---|
| Training | EnrollmentCreated, CourseCompleted | normal |
| Training | **CertificateIssued** (credential) | **HIGH** |
| Training | InstructorApproved, AssessmentGraded | normal |
| Community | **ProfileVerified**, ProfileSuspended | normal→high |
| Marketplace | ListingApproved / ListingRejected (review decision) | normal |
| Marketplace | ApplicationStatusChanged (accepted/rejected) | normal |

- **Propose three categories (D-058):** `AuditCategory::TRAINING_MANAGEMENT`,
  `COMMUNITY_MANAGEMENT`, `MARKETPLACE_MANAGEMENT` (mirrors content_/crm_/portal_management).
  **CertificateIssued = HIGH** (credential integrity) via the existing $forceSensitivity
  override (D-056). All Super Admin actions remain HIGH.
- Mechanism reuse: domain events → AuditEventSubscriber → append-only AuditService. No new
  audit infrastructure.

## 6. ANALYTICS REVIEW (D-025)

- **Training:** enrolments, completion rate, assessment pass rate, certificates issued,
  popular courses.
- **Community:** profiles by type, profile views, followers, endorsements, verified count.
- **Marketplace:** listings by type, applications per listing, application→accepted
  conversion, active vs expired.
- **W4-9 — these modules use their OWN cached counters + dedicated aggregators, NOT
  content_engagement_events** (they are not ContentAccessible; that table is for
  CMS/Knowledge/Research content engagement). Same D-025 discipline: aggregation layer +
  scheduled jobs; dashboards read persisted aggregates, never live source scans.

## 7. AI READINESS REVIEW (D-029)

- **Seams present, implementation DEFERRED to the AI sprint:**
  - Training: AI course recommendations (`ai.training.recommend` exists); assessment
    item-generation (future) — courses/lessons/assessments are the corpus.
  - Community: AI mentorship matching (D-035 reserved; D-029 matching pattern) — profiles +
    skills + seeking JSON are the feature space; `ai.*` caps in config/ics.php.
  - Marketplace: AI opportunity matching (`ai.marketplace.match` exists) — listing text +
    applicant profile.
- No AI calls in Wave 4; the schema (JSON skills/seeking/specializations, FULLTEXT) is
  shaped so the AI sprint consumes it without migration. `ai_requests` tracks cost.

## 8. FUTURE TenantScope + LMS COMPATIBILITY

- **TenantScope (D-037):** tenant_id present on training_courses/enrollments/lessons,
  community_profiles, marketplace_listings/applications. TenantScope (Phase 3) composes
  above any module rule (tenant > … > user); additive; no schema change to enable.
- **LMS (future):** the training schema is LMS-shaped (sections, lessons by type, progress,
  assessments, attempts, certificates). Basic LMS needs no schema change; richer
  SCORM/xAPI is Phase 2 (new packages/statements tables, lesson-type extension) — reserved,
  not built now.

---

## VALIDATION MATRIX (as requested)

| Item | Validation | Result |
|---|---|---|
| **D-035** Community | class-table-inheritance base + 6 extensions; visibility model; cross-module links | ✅ |
| **D-025** Analytics | per-module counters + aggregation layer (NOT content_engagement_events, W4-9) | ✅ |
| **D-029** AI readiness | recommend/match seams; JSON feature space; deferred | ✅ |
| **D-037** TenantScope | tenant_id present; additive; no schema change | ✅ |
| **D-046** Audit | new module categories proposed (D-058); certificate HIGH | ✅ (pending D-058) |

---

## FINDINGS

| ID | Finding | Severity | Disposition |
|---|---|---|---|
| W4-1 | Three NEW module access rules (enrollment/visibility/listing) must stay separate from the proven three + each other | **HIGH** | Proposed D-057 (module-local policies/scopes; permission-gated) |
| W4-2 | Community = identity data; visibility public/authenticated; NOT ContentAccessService | MEDIUM | simple visibility scope + owner policy |
| W4-3 | Marketplace listing lifecycle ≠ HasContentLifecycle; has review records + expiry | MEDIUM | MarketplaceListingService; not the content engine |
| W4-4 | Cross-module community links must be ONE-WAY; no CRM/portal internal leakage | MEDIUM | public profile fields only (W2-3 spirit) |
| W4-5 | Enrollment-gated lessons; correct_answer hidden; certificate tamper-evident | **HIGH** | enrollment policy + server-side grading + unique cert number |
| W4-6 | Paid courses depend on Billing (D-031) | MEDIUM | free enroll now; paid = invoice seam, activate on payment; payment exec deferred |
| W4-7 | Application/listing files gated + streamed | MEDIUM | applicant + poster/ICS only (W2-5 posture) |
| W4-8 | Instructor approval + profile verification staff-only, audited | LOW | staff governance + audit |
| W4-9 | Use own counters/aggregators, NOT content_engagement_events | MEDIUM | per-module analytics |
| W4-10 | LMS SCORM/xAPI is Phase 2 | LOW | reserved; no schema change now |

---

## RISKS

| Risk | Mitigation |
|---|---|
| Access-rule sprawl / mixing with proven mechanisms | W4-1: module-local policies; proven three untouched; documented boundaries |
| Paid content accessible without payment | enrollment activates only on free OR paid-invoice-settled (W4-6) |
| Assessment integrity | answers server-side; attempts capped; grading authoritative (W4-5) |
| Community leaks portal/CRM internals | one-way public links (W4-4) |
| Forced content-engine reuse where it doesn't fit | W4-2/W4-3: community & marketplace are NOT content; own services |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| New access rules are module-local + permission-gated; proven three untouched | ✅ |
| Community/Marketplace NOT forced into ContentAccessService | ✅ |
| D-035 / D-025 / D-029 / D-037 / D-046 validated/compatible | ✅ |
| Wave 4 NOT implemented; no code produced | ✅ |
| D-049 validation gate (bootstrap + GREEN CI) still in force | ⚠ carried |

---

## REVIEW VERDICT

**SOUND DESIGN — conditional on approving D-057 and D-058.** Wave 4 adds the engagement
modules with three new, clearly-bounded access rules that do not touch or weaken the proven
three mechanisms, keeps identity/opportunity data out of the content engine, defers paid
training to Billing and AI to the AI sprint, and remains TenantScope-/LMS-ready. Cleared to
proceed to Wave 4 implementation after approval and the two decisions.

Pending approvals to record on sign-off:
- **D-057 (proposed):** Wave 4 access model — Training **enrollment-gated**, Community
  **visibility-scoped** (public/authenticated, owner=user_id), Marketplace
  **listing-status + review** (owner-draft/ICS-review/public-publish, applicant-owned
  applications). All permission-gated; NONE use AccountScope/ContentAccessService/
  HasAssignmentVisibility; Community & Marketplace are NOT routed through the content engine.
- **D-058 (proposed):** Wave 4 audit categories `TRAINING_MANAGEMENT`, `COMMUNITY_MANAGEMENT`,
  `MARKETPLACE_MANAGEMENT`; **CertificateIssued = HIGH** sensitivity.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement Wave 4 until approved.**
