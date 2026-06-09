# WAVE 4C IMPLEMENTATION REVIEW — OPPORTUNITY MARKETPLACE
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-03
Status: Implementation complete — Awaiting Approval (STOP before Wave 5)
Author: Lead Architect
Decision References: D-011, D-025, D-029, D-037, D-046, D-057, D-058, D-060; W4-3/W4-7
Design baseline: WAVE_4C_ARCHITECTURE_REVIEW.md (approved)

---

## EXECUTIVE SUMMARY

Wave 4c delivers the Opportunity Marketplace with the full D-060 Trust Model: restricted
posting + mandatory pre-publication review (no auto-publish), DB-enforced duplicate
prevention, an abuse-reporting workflow with fail-safe auto-hide, scheduled auto-expiry,
streamed/gated application attachments, MARKETPLACE_MANAGEMENT auditing of governance
actions, and a dedicated analytics aggregator. Access is the sixth module-local rule
(listing-status + review + owner/applicant); `organisation_id` is provenance, not isolation
— AccountScope is not applied, and the Marketplace is not ContentAccessible. This completes
Wave 4 (Training + Community + Marketplace).

**Verdict: IMPLEMENTATION SOUND.** Standing caveat unchanged: overlay must bootstrap + run
GREEN in CI (MySQL — FULLTEXT, scheduler) before operationally "done" (R-012/R-013).

---

## DELIVERABLES

| Layer | Artifact |
|---|---|
| Migrations | marketplace_categories, marketplace_listings (+removed status, FULLTEXT), marketplace_applications (unique listing+applicant), marketplace_listing_reviews, **marketplace_listing_reports (D-060)** |
| Models | Marketplace\{MarketplaceCategory, MarketplaceListing, MarketplaceApplication, ListingReview, ListingReport} |
| Services | MarketplaceListingService (review/expiry/duplicate-flag), ApplicationService, ReportService (auto-hide), MarketplaceAnalyticsAggregator |
| Events | Marketplace\{ListingReviewed, ApplicationStatusChanged, ListingReportResolved} |
| Audit | 3 handlers → MARKETPLACE_MANAGEMENT (remove = HIGH) |
| Controllers | Marketplace, Listing, Application, Report, MarketplaceReport; Admin\Moderation |
| Config | ics.marketplace.report_autohide_threshold |
| Schedule | routes/console.php — daily marketplace:expire-listings |
| Routes | routes/marketplace.php (public browse; auth author/apply/report/moderate; throttled); registered |
| Docs | DECISION_LOG (D-060), DATABASE_BLUEPRINT (reports table + note), this review, PROJECT_MEMORY |

---

## 1. TRUST MODEL VALIDATION (D-060)

| Control | Result | Evidence |
|---|---|---|
| Restricted posting rights | ✅ | store gated by marketplace.listings.create (ICS/approved partners/orgs, D-011) |
| **Mandatory pre-publication review (no auto-publish)** | ✅ | submit → pending_review only; publish ONLY via ModerationController.approve |
| **Duplicate application prevention (DB)** | ✅ | unique (listing_id, applicant_id); ApplicationService throws clean 422 |
| Duplicate listing detection | ✅ | submit() returns duplicate_suspected (same poster + normalised title) to the reviewer |
| **Abuse reporting workflow** | ✅ | marketplace_listing_reports; one open report per reporter+listing |
| **Auto-hide threshold (fail-safe)** | ✅ | ReportService: open reports ≥ config threshold → listing back to pending_review |
| **Streamed attachment delivery** | ✅ | ApplicationController.downloadAttachment: applicant/poster/ICS only; disk->download; never public |
| Spam rate-limits | ✅ | submit/apply/report on throttle:public-forms |
| Auto-expiry | ✅ | scheduled expireOverdue() + publicVisible lazy filter |
| organisation_id = provenance, not isolation | ✅ | no AccountScope; stored, never used for access |

## 2. MODERATION VALIDATION

| Check | Result | Evidence |
|---|---|---|
| Review queue | ✅ | ModerationController.queue = pending_review (incl. auto-hidden) |
| Approve → published + review record | ✅ | service.approve writes marketplace_listing_reviews + ListingReviewed('approved') |
| Reject → rejected + reason | ✅ | service.reject; notes captured |
| Post-publication removal | ✅ | service.remove → status removed; ListingReviewed('removed') HIGH audit |
| Permission-gated | ✅ | approve = marketplace.listings.approve; reject/remove = marketplace.listings.reject |
| Immutable decision log | ✅ | ListingReview (no timestamps mutation; append) |

## 3. REPORTING VALIDATION (D-060)

| Check | Result | Evidence |
|---|---|---|
| Any authenticated user may report a published listing | ✅ | ReportController.report; publicVisible guard |
| One open report per reporter+listing | ✅ | unique (listing_id, reporter_id); service throws 422 on repeat |
| Threshold auto-hide | ✅ | autoHideIfOverThreshold → pending_review |
| Moderator queue | ✅ | ReportController.index (open) gated by marketplace.reports.view |
| Resolution audited | ✅ | resolve → ListingReportResolved → MARKETPLACE_MANAGEMENT |
| Report creation NOT audited (analytics) | ✅ | report() emits no audit event (W4b-6 discipline) |

## 4. AUDIT VALIDATION (D-046 / D-058)

| Check | Result | Evidence |
|---|---|---|
| MARKETPLACE_MANAGEMENT category | ✅ | added Wave 4a (D-058) |
| Listing approve/reject/remove audited | ✅ | handleListingReviewed (remove forced HIGH) |
| Application decisions audited | ✅ | handleApplicationStatusChanged |
| Report resolution audited | ✅ | handleListingReportResolved |
| Views / application creation / report creation NOT audited | ✅ | analytics counters only (W4b-6) |
| Append-only + Super-Admin HIGH intact | ✅ | AuditService unchanged |

## 5. ANALYTICS VALIDATION (D-025 / W4-9)

| Check | Result | Evidence |
|---|---|---|
| Own aggregator (NOT content_engagement_events) | ✅ | MarketplaceAnalyticsAggregator; Marketplace not ContentAccessible |
| KPIs | ✅ | listings by status/type, applications, acceptance rate, open reports |
| Cached counter | ✅ | application_count on listing |
| Scheduled + gated report | ✅ | aggregator for scheduled job; endpoint gated by marketplace.reports.view |

## 6. AI READINESS VALIDATION (D-029)

| Check | Result | Evidence |
|---|---|---|
| Match seam | ✅ | ai.marketplace.match permission; listing FULLTEXT + application corpus |
| Dedup/spam AI seam | ✅ | duplicate_suspected heuristic is the pre-AI hook; Phase-2 AI similarity |
| No AI calls (deferred) | ✅ | Wave 4c builds manual marketplace; AI sprint consumes later |

---

## CORRECTNESS DECISIONS (self-flagged)

1. **Listing lifecycle is a dedicated service, not HasContentLifecycle** (W4-3) — distinct
   states (pending_review/expired/rejected/removed) + review records; reusing the content
   engine would mis-model and mis-audit it.
2. **Auto-hide is fail-safe** — at the threshold a *published* listing returns to
   pending_review (re-review), rather than hard-deleting; reversible by a moderator.
3. **organisation_id never touches access** — stored as provenance; the public scope and all
   gates use status + owner/applicant only (D-060 #1).
4. **Report creation vs resolution** — creation is analytics (no audit, high volume);
   resolution is the governance act (audited), matching the W4b-6 boundary.
5. **routes/console.php created** (was absent in the overlay) to host the scheduled expiry
   sweep; the public scope also filters expired lazily so correctness never depends on cron.

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Six module access mechanisms remain separate; Marketplace not ContentAccessible / no AccountScope | ✅ |
| Trust Model fully implemented (review/dup/report/auto-hide/expiry/streamed attachments) | ✅ |
| MARKETPLACE_MANAGEMENT audit; analytics aggregator (W4-9) | ✅ |
| D-011 / D-025 / D-029 / D-037 / D-046 / D-057 / D-060 satisfied | ✅ |
| Wave 4 COMPLETE (Training + Community + Marketplace) | ✅ |
| Bootstrap + GREEN CI still required before "done" (R-012/R-013) | ⚠ carried |

---

## REVIEW VERDICT

**IMPLEMENTATION SOUND.** The Marketplace implements the complete D-060 Trust Model with a
mandatory review gate, DB-enforced duplicate prevention, an abuse-reporting workflow with
fail-safe auto-hide, scheduled expiry, and gated/streamed attachments — all while keeping the
sixth access rule module-local and treating organisation_id as provenance only. Wave 4 is
complete. Cleared for approval.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **STOP — do not begin Wave 5 until approved.**
