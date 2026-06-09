# WAVE 4C ARCHITECTURE REVIEW — OPPORTUNITY MARKETPLACE
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-03
Status: Architecture / Design — Awaiting Approval (NO Wave 4c code in this wave)
Author: Lead Architect
Decision References: D-011, D-025, D-029, D-031, D-037, D-046, D-053, D-057, D-058; W4-3/W4-7
Scope under review: Opportunity Marketplace (marketplace_categories, marketplace_listings,
marketplace_applications, marketplace_listing_reviews) + the mandatory Marketplace Trust Model

Interpretation: the deliverable is an ARCHITECTURE REVIEW with an explicit "do not implement
Wave 4c yet / wait for approval" gate. This is the Wave 4c DESIGN.

---

## ⚠ THE DEFINING CONCERN OF THIS WAVE

The Marketplace is the platform's first **public, user-generated, publicly-published** surface
(grants/tenders/jobs posted by external parties for the world to see and apply to). That makes
**trust + fraud prevention** the dominant concern — and the mandatory Trust Model (§Trust
Model) is the heart of this review. The review surfaces one real gap: **the blueprint has no
abuse-reporting table**, so abuse reporting cannot be built as-is. This is proposed as D-060
(a blueprint amendment) below.

The Marketplace adds a SIXTH module-local access rule — **listing-status + review + owner/
applicant** (D-057) — which must stay separate from the five prior mechanisms (AccountScope,
ContentAccessService, HasAssignmentVisibility, TrainingAccessService, Community visibility).
The Marketplace is NOT ContentAccessible and NOT org-isolated.

---

## EXECUTIVE SUMMARY

Wave 4c designs the Opportunity Marketplace per D-011: posters (ICS admins, approved
partners, approved organisations) submit listings → ICS reviews → published listings are
public and searchable → users apply. Access is **listing-status + review + owner/applicant**:
published = public; pre-publish = owner + reviewers; applications = applicant + poster + ICS.
A mandatory **pre-publication review gate** plus restricted posting rights, duplicate
detection, spam rate-limits, auto-expiry, and **abuse reporting** form the Trust Model.
Approve/reject/remove + application decisions + report resolutions are audited under
MARKETPLACE_MANAGEMENT (D-058). Verdict: **SOUND, conditional on D-060 (Trust Model +
abuse-reporting table).** No code this wave.

---

## 1. MARKETPLACE WORKFLOW ARCHITECTURE (D-011)

Posting rights (D-011): **ICS Administrators, Approved Partners, Approved Organisations** —
not open to all users. Workflow: **Submission → Review → Approval → Publication**, realised
by the listing status machine + marketplace_listing_reviews (one record per decision).

| Stage | Status | Who sees |
|---|---|---|
| Author | draft | owner + ICS |
| Submit | pending_review | owner + ICS reviewers |
| Approve | published | PUBLIC |
| Reject | rejected | owner + ICS (with reason) |
| Lapse | expired | owner + ICS (dropped from public browse) |

## 2. LISTING LIFECYCLE ARCHITECTURE (W4-3 / D-057)

- A dedicated **MarketplaceListingService** drives transitions (submit/approve/reject/
  expire/remove) — NOT HasContentLifecycle (the states, review records, and public-listing
  semantics differ; reusing the content engine would be wrong, W4-3).
- `marketplace_listing_reviews` is the immutable decision log (reviewer, approve/reject,
  notes) — distinct from the audit trail (which records the governance event).
- Published listings are the ONLY publicly visible ones; the public scope =
  `status = published AND (deadline IS NULL OR deadline >= today)`.
- `application_count` cached on the listing (analytics).

## 3. APPLICATION ARCHITECTURE

- marketplace_applications: one per (listing, applicant) — `uk_mkt_applications` (prevents
  duplicate applications). Status: submitted → under_review → shortlisted → accepted/rejected.
- **Applicant-owned** (applicant_id); the listing poster + ICS review applications.
- **W4-7 / W4c-5:** cover letters + attachments are private — visible ONLY to the applicant,
  the listing poster, and ICS; attachments streamed/gated (W2-5 posture), never public.
- Applicant PII is never exposed on the public listing; the public sees only `application_count`.

## 4. OWNERSHIP MODEL (D-057)

| Entity | Owner | Rule |
|---|---|---|
| Listing | posted_by_id (+ informational organisation_id) | owner manages draft; ICS reviews/moderates; public reads published |
| Application | applicant_id | applicant manages own; poster/ICS review |
| Review record | reviewer (ICS) | staff only |

- `organisation_id` (→ crm_accounts) is **informational provenance, NOT an isolation key** —
  published listings are public; AccountScope does NOT apply (W4c-1). This is the same
  "account_id-is-not-always-ownership" discipline established in CRM (D-053).
- NOT ContentAccessible; NOT the other five mechanisms. Permission + status + owner/applicant.

## 5. MODERATION ARCHITECTURE

- **Pre-publication:** mandatory ICS review (approve/reject) before any listing goes public
  (D-011) — the primary spam/fraud gate.
- **Post-publication:** moderators can unpublish/remove a published listing (status →
  rejected/removed) in response to reports; the action is audited.
- **Reviewer queue:** pending_review listings + reported listings form the moderation queue.
- Permissions: `marketplace.listings.review` / moderation held by ICS staff; posting by
  `marketplace.listings.create` (restricted roles).

## 6. FRAUD PREVENTION + VERIFICATION MODEL

- **Posting rights restriction** (D-011): only ICS / approved partners / approved orgs may
  post — the first fraud barrier (no anonymous/open posting).
- **Mandatory review gate:** nothing publishes without ICS approval.
- **Verified poster:** partner/org posters carry their verification status (partner_profiles
  /crm_accounts) — a "verified poster" signal on the listing (display-only; no internal leak,
  W2-3 spirit).
- **Applicant authentication:** only authenticated users apply; one application per listing.
- Detail in §Trust Model.

---

## ★ MANDATORY: MARKETPLACE TRUST MODEL

### 6.1 Listing Verification
- Restricted posting rights + **mandatory pre-publication ICS review** (D-011). Every listing
  is human-approved before publication (no auto-publish). Reviewer records a decision
  (marketplace_listing_reviews) and the event is audited.
- Optional verified-poster badge from the poster's partner/org verification (display-only).

### 6.2 Application Verification
- Authenticated applicants only; `uk_mkt_applications` enforces one application per listing.
- Attachments are validated (mime/size) and gated (applicant + poster + ICS); future: AV/AI
  scan seam. Rate-limited submission (anti-flood).

### 6.3 Duplicate Detection
- **Applications:** structurally prevented by the unique (listing, applicant) constraint.
- **Listings:** at submission, a similarity check flags likely duplicates (same poster +
  normalised title/description hash, or fuzzy match within a window) → the reviewer is warned;
  not auto-blocked (avoids false positives). Phase-2 AI similarity (D-029) can strengthen this.

### 6.4 Spam Prevention
- Restricted posting rights + mandatory review (spam can't reach the public).
- **Rate limits** on listing submission and application (named throttles, T-9.2 pattern).
- Poster reputation signal (history of rejected/removed listings) surfaced to reviewers.
- Honeypot/throttle on public application forms.

### 6.5 Expiry Handling
- `deadline` drives expiry: a **scheduled job** flips published → expired when `deadline <
  today`; expired listings drop from public browse but remain for records/analytics.
- Lazy fallback: the public scope also filters expired even before the job runs.

### 6.6 Moderation Workflow
- Submit → reviewer queue → approve (published) / reject (rejected, reason) — audited.
- Post-publication reports → moderation queue → unpublish/remove (audited). A configurable
  **report threshold auto-hides** a listing pending re-review (fails safe).

### 6.7 Abuse Reporting  ⚠ (requires a NEW table — D-060)
- The blueprint has **no abuse-reporting table**. Proposed **`marketplace_listing_reports`**:
  `listing_id`, `reporter_id`, `reason` (enum: spam/scam/inappropriate/duplicate/other),
  `details`, `status` (open/reviewed/dismissed/actioned), `reviewed_by`, `created_at`.
- Any authenticated user may report a published listing (one open report per reporter+listing).
- Reports queue for moderators; reaching a threshold auto-hides the listing (→ pending_review)
  pending moderator action. Report resolution is audited (MARKETPLACE_MANAGEMENT).

---

## 7. AUDIT ARCHITECTURE (D-046 / D-058)

- Category `AuditCategory::MARKETPLACE_MANAGEMENT` (added Wave 4a, D-058).
- **Audited governance events:** ListingApproved, ListingRejected, ListingRemoved (moderation),
  ApplicationStatusChanged (accepted/rejected/shortlisted), ListingReportResolved.
- **NOT audited (analytics/high-volume):** listing views, application submissions, report
  creation (the *creation* is engagement; the *resolution* is governance), auto-expiry
  (system). Keeps the trail governance-focused (W4b-6 discipline).
- Mechanism reuse: domain events → AuditEventSubscriber → append-only AuditService.

## 8. ANALYTICS ARCHITECTURE (D-025 / W4-9)

- **Own aggregator (MarketplaceAnalyticsAggregator), NOT content_engagement_events** (W4-9).
- KPIs: listings by type/status, active vs expired, applications per listing,
  application→accepted conversion, time-to-review, top categories, report rate.
- Cached `application_count` on listings; scheduled aggregation; gated report endpoint.

## 9. AI READINESS (D-029)

- Seams: `ai.marketplace.match` (exists) — match applicants↔listings, recommend opportunities
  (using Community profiles/skills); AI duplicate/spam detection (Phase 2). Listing FULLTEXT +
  application data are the corpus. **No AI calls in Wave 4c** (deferred to the AI sprint).

---

## VALIDATION MATRIX (as requested)

| Item | Validation | Result |
|---|---|---|
| **D-025** | own aggregator (NOT content_engagement_events, W4-9); scheduled | ✅ |
| **D-029** | match/dedup seams; deferred | ✅ |
| **D-037** | tenant_id on listings/applications; additive TenantScope; no schema change | ✅ |
| **D-046** | MARKETPLACE_MANAGEMENT for approve/reject/remove/application/report-resolution | ✅ |
| **D-057** | listing-status + review + owner/applicant; NOT the other mechanisms; org_id informational | ✅ |

---

## FINDINGS

| ID | Finding | Severity | Disposition |
|---|---|---|---|
| W4c-1 | Marketplace access = status+review+owner/applicant; organisation_id is informational NOT isolation | **HIGH** | module-local rule; AccountScope NOT applied |
| W4c-2 | No abuse-reporting table in blueprint → cannot build reporting as-is | **HIGH (gap)** | Proposed D-060: marketplace_listing_reports |
| W4c-3 | Mandatory pre-publication review + restricted posting (fraud gate) | **HIGH** | enforce; no auto-publish |
| W4c-4 | Expiry via scheduled job + lazy public-scope filter | MEDIUM | cron + scope |
| W4c-5 | Application cover letters/attachments private (applicant+poster+ICS), streamed | MEDIUM | gated/streamed (W2-5) |
| W4c-6 | Duplicate detection: unique application; listing similarity flag to reviewer | MEDIUM | constraint + similarity warn |
| W4c-7 | Spam: rate limits + review gate + reputation | MEDIUM | named throttles + reviewer signal |
| W4c-8 | Report threshold auto-hide (fail-safe) | MEDIUM | configurable threshold |
| W4c-9 | Own analytics aggregator (W4-9) | MEDIUM | per-module aggregator |
| W4c-10 | AI match/dedup deferred (D-029) | LOW | seams only |

---

## RISKS

| Risk | Mitigation |
|---|---|
| Spam/scam listings reach the public | restricted posting + mandatory review + reports + auto-hide |
| Duplicate listings/applications | unique application constraint; listing similarity flag |
| Applicant PII exposure | applications private (applicant+poster+ICS); public sees only counts |
| Stale listings shown | scheduled auto-expiry + lazy scope filter |
| Abuse with no recourse | D-060 abuse-reporting table + moderation queue |
| organisation_id mistaken for isolation key | W4c-1: informational only; AccountScope not applied |

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Marketplace access is module-local; six mechanisms remain separate | ✅ |
| Trust Model addressed (verify/dup/spam/expiry/moderation/abuse) | ✅ design |
| D-025 / D-029 / D-037 / D-046 / D-057 validated/compatible | ✅ |
| Wave 4c NOT implemented; no code produced | ✅ |
| D-049 validation gate (bootstrap + GREEN CI) still in force | ⚠ carried |

---

## REVIEW VERDICT

**SOUND DESIGN — conditional on approving D-060 (Marketplace Trust Model + abuse-reporting
table).** The Marketplace correctly adds a sixth module-local access rule, mandates
pre-publication review, treats organisation_id as provenance not isolation, and specifies a
complete trust model. The one real gap (no abuse-reporting table) is resolved by D-060.
Cleared to proceed to Wave 4c implementation after approval and the decision.

Pending approval to record on sign-off:
- **D-060 (proposed):** Marketplace Trust Model — add `marketplace_listing_reports`
  (abuse reporting); mandatory pre-publication ICS review (D-011); restricted posting rights;
  duplicate detection (unique application + listing similarity flag); spam rate-limits +
  reputation; scheduled auto-expiry; report-threshold auto-hide. Audit listing approve/reject/
  remove + application decisions + report resolution under MARKETPLACE_MANAGEMENT.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement Wave 4c until approved.**
