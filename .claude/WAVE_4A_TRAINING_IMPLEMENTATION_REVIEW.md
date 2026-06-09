# WAVE 4A IMPLEMENTATION REVIEW — TRAINING INSTITUTE
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-02
Status: Implementation complete — Awaiting Approval (STOP before Wave 4b)
Author: Lead Architect
Decision References: D-025, D-029, D-031, D-037, D-046, D-057, D-058, D-059; W4-5/W4-6/W4-9
Design baselines: WAVE_4_ARCHITECTURE_REVIEW.md, TRAINING_CERTIFICATION_GOVERNANCE_REVIEW.md

---

## EXECUTIVE SUMMARY

Wave 4a delivers the Training Institute with **enrollment-gated access** (D-057) — a new,
module-local authorization rule that is provably separate from the three proven mechanisms
(AccountScope / ContentAccessService / HasAssignmentVisibility) and does not touch any of
them. Courses are not ContentAccessible. Assessment integrity is enforced server-side
(W4-5: `correct_answer` never leaves the server). Certificates implement the full D-059
governance standard: ICS-CERT numbering via a race-safe sequence, tamper-evident hash,
public minimal-disclosure verification, and revoke/reissue/expiry lifecycle — issuance and
revocation audited HIGH (D-058). Analytics use a dedicated aggregator (not
content_engagement_events, W4-9). Paid enrolment is correctly blocked pending Billing (W4-6).

**Verdict: IMPLEMENTATION SOUND.** Standing caveat unchanged: overlay must bootstrap + run
GREEN in CI (MySQL — FULLTEXT, sequence locking) before operationally "done" (R-012/R-013).

---

## DELIVERABLES

| Layer | Artifact |
|---|---|
| Migrations | course_categories, instructors, courses (+validity_months), sections, lessons, enrollments, lesson_progress, assessments, assessment_questions, assessment_submissions, certificate_sequences, certificates (+D-059 cols) |
| Models | Training\{TrainingCourse, Lesson, Enrollment, LessonProgress, Assessment, AssessmentQuestion, AssessmentSubmission, Certificate, Instructor} |
| Access | Training\TrainingAccessService (enrollment gate, D-057) |
| Services | EnrollmentService, AssessmentService (server-side grading), CertificateService (D-059), TrainingAnalyticsAggregator |
| Events | Training\{EnrollmentCreated, CourseCompleted, CertificateIssued, CertificateRevoked, AssessmentGraded, InstructorApproved} |
| Audit | AuditCategory::TRAINING_MANAGEMENT (+COMMUNITY/MARKETPLACE, D-058); 6 handlers; cert issue/revoke HIGH |
| Controllers | CourseCatalog, Enrollment, Assessment, Certificate, TrainingReport; Admin\{Course, Instructor} |
| Routes | routes/training.php (public catalogue + verification; auth learner/staff); registered |
| Docs | DECISION_LOG (D-057/D-058/D-059), TRAINING_CERTIFICATION_GOVERNANCE_REVIEW, DATABASE_BLUEPRINT note, this review, PROJECT_MEMORY |

---

## 1. ENROLLMENT ARCHITECTURE VALIDATION (D-057)

| Check | Result | Evidence |
|---|---|---|
| Enrollment is the access mechanism | ✅ | TrainingAccessService.canAccessLesson = is_preview OR active enrollment (or staff/instructor) |
| NOT AccountScope/ContentAccessService/HasAssignmentVisibility | ✅ | no BelongsToAccount, no ContentAccessible, no HasAssignmentVisibility on any training model |
| Courses not ContentAccessible | ✅ | TrainingCourse is a plain model; publish is a status transition |
| Catalogue public; content gated | ✅ | CourseCatalogController public (titles + is_preview only); lesson body via gated viewLesson |
| Unique enrollment per user+course | ✅ | uk_enrollment_user_course; firstOrCreate |
| Progress + completion | ✅ | recordLessonProgress recomputes %; 100% → complete() → certificate |
| Paid enrolment blocked (W4-6) | ✅ | is_paid → HTTP 402 (Billing seam, D-031); free enrols immediately |
| Learner-owned | ✅ | enrollment.user_id; my/courses scoped to the caller |

## 2. ASSESSMENT SECURITY VALIDATION (W4-5)

| Check | Result | Evidence |
|---|---|---|
| `correct_answer` never serialised | ✅ | AssessmentQuestion $hidden + explicit column select excludes it in `show` |
| Grading is server-side | ✅ | AssessmentService.autoGrade reads correct_answer server-side only |
| Attempts capped | ✅ | submit() aborts at assessment.max_attempts |
| Must be enrolled to attempt | ✅ | canAttempt / activeEnrollment guard on show + submit |
| Subjective items await instructor | ✅ | assignment/short-answer-blank → ungraded until grade() |
| Pass computed vs pass_score | ✅ | score% >= pass_score → passed; AssessmentGraded fired (audited) |
| Submit gated by permission | ✅ | training.assessments.submit; grade by training.assessments.grade |

## 3. CERTIFICATE GOVERNANCE VALIDATION (D-059)

| Check | Result | Evidence |
|---|---|---|
| Numbering ICS-CERT-{YYYY}-{NNNNNN} | ✅ | allocateNumber() + training_certificate_sequences, row-locked in a transaction |
| Number unique + immutable | ✅ | unique column; allocated once; reissue gets a NEW number |
| Tamper-evident | ✅ | verification_hash = SHA-256 over immutable facts (number/holder/course/issued_at); verify recomputes + hash_equals |
| Public verification, minimal disclosure | ✅ | verify(): holder name + course + dates + status only; no PII/scores/IDs; public route + throttle |
| Live status (expiry) | ✅ | liveStatus() reports expired when expires_at past, regardless of stored flag |
| Revocation | ✅ | staff-only; reason required; terminal; CertificateRevoked audited HIGH |
| Expiry model | ✅ | course.validity_months → expires_at; NULL = no expiry; fixed at issuance |
| Reissue | ✅ | new number, supersede prior (reissued_from_id), lineage kept; revoked cannot reissue (422) |
| Idempotent issuance | ✅ | issueForEnrollment returns existing valid/superseded cert |
| PDF gated | ✅ | download = owner or staff; streamed; verifier never streams the PDF |

## 4. AUDIT VALIDATION (D-058 / D-046)

| Check | Result | Evidence |
|---|---|---|
| TRAINING_MANAGEMENT category added | ✅ | AuditCategory::TRAINING_MANAGEMENT (+ COMMUNITY/MARKETPLACE for 4b/4c) |
| 6 events wired | ✅ | handlers + subscriptions in AuditEventSubscriber |
| **CertificateIssued = HIGH** | ✅ | handleCertificateIssued forces AuditSensitivity::HIGH |
| **CertificateRevoked = HIGH** | ✅ | handleCertificateRevoked forces HIGH (reason captured) |
| Enrolment/completion/grading/instructor audited | ✅ | normal sensitivity |
| Append-only + Super-Admin HIGH intact | ✅ | AuditService unchanged |

## 5. ANALYTICS VALIDATION (D-025 / W4-9)

| Check | Result | Evidence |
|---|---|---|
| Dedicated aggregator (NOT content_engagement_events) | ✅ | TrainingAnalyticsAggregator; Training is not ContentAccessible (W4-9) |
| KPIs | ✅ | enrolments, completion rate, assessment pass rate, certs issued/revoked, popular courses |
| Scheduled, not per-request | ✅ | aggregator for a scheduled job; report endpoint gated by training.reports.view |
| Cached counters | ✅ | enrollment_count/completion_count on the course; no heavy per-request scans |

## 6. AI READINESS VALIDATION (D-029)

| Check | Result | Evidence |
|---|---|---|
| Recommendation corpus present | ✅ | courses/lessons/assessments queryable; ai.training.recommend permission exists |
| No AI calls wired (deferred) | ✅ | Wave 4a builds manual training; AI sprint consumes later |
| Cost guardrails ready | ✅ | config/ics.php ai caps; ai_requests table |
| Assessment item-gen seam | ✅ | structured questions/options JSON available for future AI authoring |

---

## CORRECTNESS DECISIONS (self-flagged)

1. **Hash excludes mutable `status`** — verification_hash covers only immutable facts
   (number/holder/course/issued_at) so the integrity check stays stable across
   revoke/expire/supersede, while status is tracked + reported separately. Detects tampering;
   doesn't false-positive on lifecycle changes.
2. **Paid enrolment returns 402, not a silent free pass** — honest Billing seam (W4-6);
   free courses enrol immediately. No paid content is ever unlocked without payment.
3. **Certificate issuance is idempotent + automatic on completion** — one valid cert per
   enrollment; re-completion never duplicates.
4. **Reissue preserves the achievement date** (`issued_at`) while `created_at` marks the
   reissue moment; revoked certs cannot be reissued (422).
5. **Course publish is a plain status transition** — TrainingCourse is intentionally NOT
   ContentAccessible and does NOT reuse HasContentLifecycle (which would mis-audit under
   content_management and pull in the wrong module, per the W3-2 lesson).

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Enrollment-gated; proven three mechanisms untouched/unmixed | ✅ |
| Assessment answers never exposed; grading server-side (W4-5) | ✅ |
| Certificate governance per D-059 (number/verify/revoke/expiry/reissue) | ✅ |
| TRAINING_MANAGEMENT audit; cert issue/revoke HIGH (D-058) | ✅ |
| Analytics via own aggregator (W4-9); AI seams only (D-029) | ✅ |
| Wave 4b/4c NOT implemented | ✅ |
| Bootstrap + GREEN CI still required before "done" (R-012/R-013) | ⚠ carried |

---

## REVIEW VERDICT

**IMPLEMENTATION SOUND.** Training adds enrollment-gated access as a clean, module-local
rule that leaves the proven three mechanisms untouched; enforces assessment integrity
server-side; and realises the full D-059 certificate governance standard with HIGH-sensitivity
auditing. Cleared for approval.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **STOP — do not begin Wave 4b (Community) until approved.**
