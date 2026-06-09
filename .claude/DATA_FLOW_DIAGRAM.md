# DATA FLOW DIAGRAM
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: Awaiting Approval
Author: Chief Enterprise Architect

Decision References: D-022 (Notifications), D-024 (Storage), D-026 (AI), D-027 (Events), D-031 (Billing), D-032 (Data Warehouse)

---

## EXECUTIVE SUMMARY

This document maps how data flows through the platform — from entry points
through processing, storage, integration, and output. It covers all major
data flows across user interactions, business processes, external integrations,
and analytics.

Understanding data flow is essential for:
- Security analysis (identifying where PII travels)
- Integration planning (external API dependencies)
- Performance design (identifying high-volume paths)
- NDPA/GDPR compliance (tracking PII scope per D-006)

Flow Categories Documented: 11
External Systems: 7
PII Flows: Identified and marked ⚠️

---

## PLATFORM BOUNDARY MAP

```
╔══════════════════════════════════════════════════════════════════════╗
║                    ICS ENTERPRISE PLATFORM                           ║
║                    (Laravel 11 / MySQL 8+)                           ║
║                                                                      ║
║  ┌──────────────┐   ┌──────────────┐   ┌──────────────────────────┐ ║
║  │  Web Browser │   │   PWA App    │   │  API Consumers (future)  │ ║
║  │  (Blade/HTML)│   │  (Service    │   │  (Mobile, 3rd-party)     │ ║
║  │  Alpine.js   │   │   Worker)    │   │                          │ ║
║  └──────┬───────┘   └──────┬───────┘   └───────────┬──────────────┘ ║
║         │                  │                        │                ║
║  ───────┴──────────────────┴────────────────────────┴────────────── ║
║                       HTTPS / TLS 1.3                                ║
║  ─────────────────────────────────────────────────────────────────  ║
║         │                                                            ║
║  ┌──────▼──────────────────────────────────────────────────────┐    ║
║  │               LARAVEL APPLICATION                           │    ║
║  │  Middleware: Auth | RBAC | CSRF | Rate Limit | i18n         │    ║
║  │  Routes: web.php | api.php (/api/v1/)                       │    ║
║  │  Controllers → Services → Models → Events → Listeners       │    ║
║  └──────┬──────────────────────────────────────────────────────┘    ║
║         │                                                            ║
║  ┌──────┼──────────────────────────────────────────────────┐        ║
║  │      │           DATA STORES                            │        ║
║  │  ┌───▼────┐  ┌──────────┐  ┌───────┐  ┌────────────┐  │        ║
║  │  │MySQL8+ │  │File      │  │Cache  │  │Job Queue   │  │        ║
║  │  │(primary│  │Storage   │  │(MySQL │  │(MySQL sys_ │  │        ║
║  │  │ DB)    │  │(Hostinger│  │Phase1)│  │jobs Phase1)│  │        ║
║  │  └────────┘  └──────────┘  └───────┘  └────────────┘  │        ║
║  └──────────────────────────────────────────────────────┘          ║
║                                                                      ║
╚════════════════════════════════════════════════════════════════════╝
                    │         │          │         │
          ┌─────────▼──┐  ┌───▼───┐  ┌──▼────┐  ┌▼──────────────┐
          │  Brevo     │  │Paystack│  │Gemini │  │WhatsApp       │
          │  (Email)   │  │(Billing│  │AI API │  │Business API   │
          └────────────┘  └────────┘  └───────┘  └───────────────┘
          ┌─────────────────────────┐  ┌──────────────────────────┐
          │  Google Analytics       │  │  Google Search Console   │
          └─────────────────────────┘  └──────────────────────────┘
```

---

## FLOW 1 — USER AUTHENTICATION FLOW

⚠️ PII Data: email, password hash, IP address, user agent

```
STEP 1 — Login Request
  Browser → POST /api/v1/auth/login { email, password }
  Middleware: CSRF check | Rate limit (5 attempts/15min) | IP logging

STEP 2 — Credential Validation
  AuthController → AuthService::authenticate()
  AuthService → core_users (WHERE email = ?) [PDO prepared]
  AuthService → password_verify(input, stored_hash)
  
STEP 3a — FAILURE PATH
  AuthService → Increment failed attempt counter (sys_cache)
  → If attempts >= 5: AccountLocked event → send alert email
  → Return 401 response

STEP 3b — SUCCESS PATH
  AuthService → Check MFA requirement (role-based)
  → If MFA required: issue temp token; await TOTP verification
  → If MFA satisfied: issue Sanctum bearer token
  AuthService → UpdateLastSeen listener (update last_login_at, last_ip)
  AuthService → LogAuditEvent (core_audit_logs)
  
STEP 4 — Session Established
  Response: { token, user, permissions[] }
  Token stored: personal_access_tokens
  Frontend: store token in memory (SPA) or session (web)
  
STEP 5 — Subsequent Requests
  Request header: Authorization: Bearer {token}
  Middleware: Sanctum token lookup → core_users → RBAC load
  Every request: token validity + role + policy check
```

**Security Controls:**
- Brute force: 5 attempts → lockout → E-CORE-005
- Token expiry: 24h default
- Session fixation: token regenerated on privilege change
- All IP addresses logged to audit trail

---

## FLOW 2 — CONTENT CREATION FLOW (CMS / Knowledge / Research)

⚠️ PII: author identity, creation timestamps

```
STEP 1 — Draft Creation
  ICS Content Staff → POST /api/v1/content/articles
  Controller validates: auth check | RBAC check | FormRequest validation
  ContentService → content_articles INSERT (status=draft)
  FileUploaded event (if media attached) → core_audit_logs

STEP 2 — File Attachment (if any)
  Staff → POST /api/v1/storage/upload { file }
  StorageService → validate type, size, extension
  StorageService → rename to UUID.{ext}
  StorageService → store to storage/app/private/ or public/
  FileUploaded event → audit log

STEP 3 — Submit for Review
  Staff → PUT /api/v1/content/articles/{id} { status: "under_review" }
  → NotifyReviewer (if applicable)

STEP 4 — Publish
  Content Staff / Platform Admin → PUT status = "published"
  ArticlePublished event fired:
    → IndexArticleForSearch (queue)
    → GenerateRelatedContent (queue)
    → UpdateKnowledgeAnalytics (queue)
  content_articles.published_at = now()
  content_articles.status = published

STEP 5 — Reader Access
  Guest/Authenticated User → GET /api/v1/knowledge/articles/{slug}
  KnowledgeAccessService::canAccess($user, $article)
    → switch access_tier → role check
  If allowed: return article data
  If denied: return 403 with suggested upgrade path
  
  ArticleView event (background): RecordViewEvent → knowledge_views INSERT
  UpdateViewCounter (queue)
```

**i18n Path:**
  Article title/body stored via i18n_translations WHERE locale = 'en'
  Phase 2: French translations added to same table with locale = 'fr'

---

## FLOW 3 — LEAD-TO-CLIENT PIPELINE FLOW

⚠️ PII: Contact name, email, phone, company data — HIGH SENSITIVITY

```
STEP 1 — Lead Entry (multiple sources)

  Source A: Website inquiry form
    Visitor → POST /inquiry { name, email, message }
    ContentController → CRM LeadService::createFromInquiry()
    → crm_leads INSERT | E-CRM-001 → AssignToRep, SendAlert

  Source B: Community consultant profile
    User creates consultant profile → E-COMM-001 ProfileCreated
    → CreateCRMLeadIfConsultant listener → crm_leads INSERT

  Source C: Partner referral
    Partner → POST /api/v1/partners/referrals { ... }
    → E-PART-003 ReferralSubmitted → CreateCRMLead listener

STEP 2 — Lead Qualification
  CRM Staff → POST /api/v1/ai/crm/leads/{id}/qualify
  GeminiService (rate-limit check → budget check → Gemini API call)
  → ai_requests INSERT (tokens, cost, status)
  → crm_leads UPDATE (ai_qualification_score, ai_qualification_at)
  E-CRM-002 LeadQualified → UpdateLeadStage, NotifyRep, UpdateAnalytics

STEP 3 — Opportunity Creation
  CRM Staff → POST /api/v1/crm/opportunities
  → crm_opportunities INSERT (linked to crm_leads, crm_accounts)

STEP 4 — Proposal Generation
  CRM Staff → POST /api/v1/ai/crm/opportunities/{id}/proposal
  GeminiService → Gemini API call → draft text
  → crm_proposals INSERT (status=ai_draft)
  Staff reviews and approves → status=sent
  E-CRM-004 ProposalAccepted (if client accepts) → CreateProposalInvoice

STEP 5 — Contract & Close
  CRM Staff → crm_contracts INSERT
  → E-CRM-005 ContractSigned
  → TriggerClientOnboarding:
    - core_users: grant Client Admin role
    - E-CLIENT-001 ProjectCreated
    - GrantClientPortalAccess

STEP 6 — Invoice & Payment (Phase 2)
  BillingService::createConsultingDeposit()
  → billing_invoices INSERT | E-BILL-003 InvoiceCreated
  → SendInvoiceToRecipient (Brevo email with PDF attachment)
  Client pays → Paystack webhook → PaymentSucceeded
  → MarkInvoicePaid | UpdateRevenueAnalytics
```

---

## FLOW 4 — TRAINING ENROLLMENT & COMPLETION FLOW

⚠️ PII: Student identity, progress data

```
STEP 1 — Course Discovery
  Authenticated User → GET /api/v1/training/courses
  → training_courses WHERE status=published
  AI Recommendations → GET /api/v1/ai/training/recommendations
  → TrainingRecommendationService → Gemini API
  → Return ranked course list with rationale

STEP 2 — Enrollment
  User → POST /api/v1/training/courses/{id}/enroll
  EnrollmentService:
    → Check prerequisites
    → training_enrollments INSERT (status=active)
    → E-TRAIN-002 CourseEnrolled
    → SendEnrollmentConfirmation (Brevo email + in-app)
    → CreateCourseInvoice if paid (billing_invoices INSERT)
    → UpdateCourseStats (queue)

STEP 3 — Payment (paid courses — Phase 2)
  BillingService → Paystack: initializeTransaction()
  → Redirect user to Paystack checkout
  → Paystack processes payment
  → Paystack webhook POST /api/v1/billing/webhooks/paystack
  → Verify signature → billing_webhooks INSERT (log)
  → billing_payments INSERT
  → E-BILL-001 PaymentSucceeded → FulfilPurchase (unlock enrollment)

STEP 4 — Learning Progress
  Student → POST /api/v1/training/lessons/{id}/complete
  → training_lesson_progress UPDATE (status=completed)
  → E-TRAIN-003 LessonCompleted
  → UpdateCourseProgress (recalculate progress_percent)
  → UnlockNextLesson
  → CheckCourseCompletion

STEP 5 — Assessment
  Student → POST /api/v1/training/assessments/{id}/submit { answers[] }
  → training_assessment_submissions INSERT
  → E-TRAIN-004 AssessmentSubmitted
  → AutoGradeAssessment (MCQ/T-F) → score calculated
  → E-TRAIN-005 AssessmentGraded
  → SendGradeNotification (Brevo email)
  → CheckCourseCompletion if pass required

STEP 6 — Certificate Issuance
  E-TRAIN-006 CourseCompleted fired
  → IssueCertificate:
    - Generate unique certificate number
    - Render PDF via DOMPDF (Blade template)
    - Store: storage/app/private/certificates/{year}/{number}.pdf
    - training_certificates INSERT
  → E-TRAIN-007 CertificateIssued
  → SendCertificateDelivery (Brevo email with PDF)
  → UpdateTrainingAnalytics (queue)
```

---

## FLOW 5 — MARKETPLACE LISTING & APPLICATION FLOW

```
STEP 1 — Listing Submission
  Poster (ICS Admin / Partner / Gov) → POST /api/v1/marketplace/listings
  → marketplace_listings INSERT (status=pending_review)
  → E-MKT-001 ListingSubmitted → NotifyReviewers (in-app)

STEP 2 — Review & Approval
  ICS Admin → PUT /api/v1/marketplace/listings/{id}/approve
  → marketplace_listings UPDATE (status=published, published_at=now)
  → E-MKT-002 ListingApproved:
    → PublishListing
    → NotifySubmitterOfApproval (Brevo email + in-app)
    → TriggerAIOpportunityMatching (queue job)
    → UpdateMarketplaceAnalytics

STEP 3 — AI Opportunity Matching (background)
  OpportunityMatchingService:
    → Load active user profiles + preferences
    → Batch Gemini API calls (rate-limited, Tier 1)
    → Store matches in cache
    → Deliver as personalised feed to authenticated users

STEP 4 — Application Submission
  Applicant → POST /api/v1/marketplace/listings/{id}/apply
  → marketplace_applications INSERT (status=submitted)
  → E-MKT-005 ApplicationSubmitted:
    → NotifyListingOwner (Brevo email + in-app)
    → SendApplicationConfirmation to applicant
    → UpdateMarketplaceAnalytics

STEP 5 — Application Status Update
  Listing owner → PUT /api/v1/marketplace/applications/{id}
  → marketplace_applications UPDATE (status=shortlisted|accepted|rejected)
  → E-MKT-006 ApplicationStatusChanged
  → NotifyApplicant (Brevo email + in-app)
```

---

## FLOW 6 — PAYMENT & BILLING FLOW

⚠️ PII: Payment method details NEVER stored on platform — Paystack holds card data

```
STEP 1 — Invoice Creation
  BillingService::create*Invoice() called by any trigger:
    - CourseEnrolled (training)
    - ProposalAccepted (CRM)
    - ContractSigned (consulting deposit)
    - SubscriptionRenewal (auto-generated)
  → billing_invoice_sequences UPDATE (last_sequence++)
  → billing_invoices INSERT (INV-{YEAR}-{SEQ})
  → billing_invoice_items INSERT (line items)
  → Generate PDF: DOMPDF → store storage/app/private/invoices/
  → E-BILL-003 InvoiceCreated → SendInvoiceToRecipient (Brevo)

STEP 2 — Payment Initiation
  User → POST /api/v1/billing/pay { invoice_id }
  BillingService → PaystackGateway::initializeTransaction()
  → Paystack API: POST /transaction/initialize
  → Returns: authorization_url, reference
  → billing_payments INSERT (status=pending, gateway_transaction_ref)
  → Redirect user to Paystack checkout (external)

STEP 3 — Payment Processing (external — Paystack)
  User enters card/bank on Paystack hosted page
  Paystack processes payment
  Card data NEVER reaches ICS platform

STEP 4 — Webhook Callback
  Paystack → POST /api/v1/billing/webhooks/paystack
  BillingController:
    → Verify X-Paystack-Signature header
    → billing_webhooks INSERT (log raw payload)
    → Check billing_payments for duplicate gateway_transaction_id
    → If duplicate: return 200 (idempotency — no action)
    → billing_payments UPDATE (status=success, paid_at)
    → billing_invoices UPDATE (status=paid, paid_at)
    → E-BILL-001 PaymentSucceeded
    → FulfilPurchase (module-specific action)
    → SendPaymentReceipt (Brevo email)
    → UpdateRevenueAnalytics

STEP 5 — Fulfillment Actions (by type)
  Course: → training_enrollments UPDATE (status=active, unlocked)
  Subscription: → billing_subscriptions UPDATE (status=active)
                → E-BILL-005 SubscriptionActivated
                → GrantSubscriptionAccess (tier elevation)
  Consulting deposit: → crm_contracts UPDATE (deposit_paid=true)
```

---

## FLOW 7 — NOTIFICATION DELIVERY FLOW

```
STEP 1 — Trigger
  Any business event fires a Listener that creates a Notification:
  new WelcomeNotification($user)
  → Laravel dispatches notification via queue (sys_jobs)

STEP 2 — Channel Resolution
  NotificationService reads notify_preferences for user
  Notification class determines channels: mail, whatsapp, database

STEP 3A — Mail Channel (Brevo)
  MailChannel → Brevo SMTP/API
  → POST api.brevo.com/v3/smtp/email { to, subject, html }
  → Brevo delivers email
  → Delivery status tracked (webhook from Brevo — Phase 2)

STEP 3B — WhatsApp Channel
  WhatsAppChannel → WhatsApp Business API
  → POST graph.facebook.com/v*/PHONE_ID/messages
  → WhatsApp delivers message
  → Delivery receipt via webhook (Phase 2)

STEP 3C — Database Channel (in-app)
  DatabaseChannel → INSERT notifications table
  { id, type, notifiable_type, notifiable_id, data JSON, read_at NULL }
  → Frontend polls GET /api/v1/notifications/unread (every 30s)
  → Badge count updated in UI

STEP 4 — Push Notification (PWA)
  For high-priority notifications:
  NotificationService::pushWeb($userId, $payload)
  → Load notify_push_subscriptions
  → PHP Web Push library → VAPID encryption
  → POST subscription.endpoint (browser push service)
  → Browser receives push → service worker displays notification
```

---

## FLOW 8 — AI REQUEST FLOW

```
STEP 1 — Request Entry
  Authenticated user → POST /api/v1/ai/{module}/{use-case}
  AIController → {UseCase}Service::execute($input)

STEP 2 — Pre-Flight Checks
  BaseAIService::preFlightCheck():
    → Rate limit check: ai_requests COUNT WHERE user_id = ? AND created_at > 1hr ago
    → Tier limit check: count vs per-tier limit
    → Budget check: ai_requests SUM(total_tokens) WHERE DATE = today vs daily_cap
    → If any check fails: return 429 / budget exceeded error

STEP 3 — Cache Check
  BaseAIService::checkCache($cacheKey):
    → ai_cache SELECT WHERE key = ? AND expires_at > now()
    → If hit: return cached response (skip Gemini call)
    → If miss: proceed to Gemini

STEP 4 — Gemini API Call
  GeminiService::generate($prompt, $options):
    → Laravel HTTP Client: POST generativelanguage.googleapis.com/v1beta/models/...
    → Authorization: Bearer {GEMINI_API_KEY}
    → Request body: { contents, generationConfig }
    → Response: { candidates[0].content.parts[0].text, usageMetadata }

STEP 5 — Response Processing
  Extract text from response
  Store result in ai_cache (if cacheable, 24hr TTL)
  ai_requests INSERT:
    { user_id, module, use_case, prompt_tokens, response_tokens, cost_usd, status=success }

STEP 6 — E-AI-001 AIRequestCompleted
  → UpdateAIUsageAnalytics (queue)
  → Budget threshold check → E-AI-002 if >80%

STEP 7 — Failure Path
  On Gemini timeout / API error:
    ai_requests INSERT (status=failed)
    E-AI-004 AIRequestFailed
    → LogAIFailure
    → ServeCachedResponse (return stale cache if available)
    → If no cache: return degraded response (null + UI message)
    → Core workflow continues unblocked
```

---

## FLOW 9 — FILE STORAGE FLOW

```
UPLOAD PATH:
  Client → POST /api/v1/storage/upload { file, module, type }
  StorageService::store($file, $module, $visibility):
    → validate: extension whitelist check
    → validate: MIME type from file content (not extension)
    → validate: file size vs type limit
    → generate: $filename = Str::uuid() . '.' . $ext
    → store: Storage::disk($disk)->put($path, $contents)
    → return: { path, url, size_kb }
  E-CORE-011 FileUploaded → LogAuditEvent

  Phase 1 Disk Paths:
    Public: storage/app/public/{module}/ → accessible via /storage/{path}
    Private: storage/app/private/{module}/ → NOT web-accessible

PRIVATE FILE DELIVERY PATH:
  Authenticated User → GET /api/v1/storage/{uuid}
  StorageController:
    → Lookup file record (by uuid) in module table
    → Check permission (RBAC + access tier if Knowledge/Research)
    → If permitted: Storage::disk('private')->get($path)
    → Return file with appropriate Content-Type and Content-Disposition headers
    → E-KNOW-002 or E-RES-002 fired for analytics

PHASE 3 MIGRATION PATH:
  config/filesystems.php:
    FILESYSTEM_DISK=s3 (was: local)
  No application code changes required.
  All Storage::disk() calls transparently use S3 driver.
```

---

## FLOW 10 — ANALYTICS AGGREGATION FLOW

```
TIER 1 — MODULE ANALYTICS (Cron, every 15–60 min)

  Laravel Scheduler → Artisan commands:
    analytics:aggregate-crm → SELECT aggregates FROM crm_*
    → analytics_crm_pipeline UPSERT
    
    analytics:aggregate-training → SELECT aggregates FROM training_*
    → analytics_training_stats UPSERT
    
    (similar for: marketplace, partners, startups, content, revenue)

  Dashboard reads: GET /api/v1/analytics/executive-dashboard
    → SELECT from analytics_* tables (fast, pre-aggregated)
    → Chart.js renders in frontend

TIER 2 — DATA WAREHOUSE ETL (Cron, nightly 03:00–07:00 UTC)

  Laravel Scheduler → Artisan commands:
    dw:load-revenue → Extract billing_* | Transform to NGN | Load dw_fact_revenue
    dw:load-crm → Extract crm_* | Load dw_fact_crm
    dw:load-training → Extract training_* | Load dw_fact_training
    (similar for: marketplace, startups, partners, research, projects)
    dw:refresh-dimensions → Update SCD Type 2 dimension tables
    
    Each command writes to dw_etl_runs (status, rows_loaded, duration)

FUTURE BI INTEGRATION (Phase 2/3):
  Metabase → MySQL connector → SELECT from dw_* tables → BI dashboards
  No additional data flow changes required.
```

---

## FLOW 11 — PWA & OFFLINE FLOW

```
INSTALLATION:
  User visits platform → Browser detects manifest.json
  Browser prompts "Add to Home Screen"
  User accepts → PWA installed on device
  Service worker registered: /sw.js

FIRST LOAD (online):
  Service worker intercepts all requests
  Static assets (CSS, JS, fonts): cached in Cache Storage (CacheFirst strategy)
  API responses (GET): cached in Cache Storage (NetworkFirst strategy)
  Offline fallback page: pre-cached on service worker install

SUBSEQUENT LOADS (online):
  Static assets served from cache (instant load)
  API calls: NetworkFirst — attempt network, fall back to cache
  Forms: NetworkFirst — if offline, queue in sync queue (background sync)

OFFLINE MODE:
  User opens PWA with no connection
  → Previously viewed pages: served from cache
  → Dashboard: last cached snapshot displayed with "offline" banner
  → Forms: stored in IndexedDB queue
  → When connection restores: background sync fires queued requests
  → New data displays normally

PUSH NOTIFICATIONS:
  Server → notify_push_subscriptions lookup
  → PHP Web Push → VAPID signed request → Browser push service
  → Push service delivers to device (even if PWA not open)
  → Service worker receives push event
  → Service worker creates browser notification
  → User taps notification → PWA opens to relevant page
```

---

## PII DATA FLOW SUMMARY

⚠️ All PII flows are subject to NDPA and GDPR controls (D-006)

| Data Type | Entry Point | Storage | Accessible To | Deletion Path |
|---|---|---|---|---|
| Name, Email | Registration / inquiry | core_users, crm_contacts | ICS Staff + own user | Soft delete + nullify (D-006) |
| Phone | Profile / CRM | core_users, crm_contacts | ICS Staff | Nullify on deletion request |
| IP Address | Login, file download | core_audit_logs, knowledge_views | Super Admin, Platform Admin | Retained per audit policy |
| Payment method | Paystack checkout | Stored by Paystack only | NOT on ICS platform | N/A — not our data |
| Assessment responses | AI assessment forms | ai_assessments (aggregated) | ICS Staff, own user | Soft delete on request |
| Community profile | Self-created | community_profiles | Public (if set) | Soft delete + de-index |
| Document uploads | File upload | storage/app/ | Per module permission | Physical delete on request |

---

## EXTERNAL SYSTEM DEPENDENCIES

| System | Direction | Protocol | Authentication | Failure Impact |
|---|---|---|---|---|
| Brevo | Outbound | HTTPS REST / SMTP | API Key | Emails queued; retry |
| Paystack | Outbound + Inbound webhook | HTTPS REST | Secret Key + Sig | Revenue cannot be collected |
| WhatsApp Business API | Outbound | HTTPS REST | Bearer Token | WhatsApp notifications fail |
| Google Gemini API | Outbound | HTTPS REST | API Key | AI features degrade gracefully |
| Google Analytics | Outbound (JS) | HTTPS | Tracking ID | Analytics missing; no user impact |
| Google Search Console | Inbound (verification) | HTTPS | Site verification | SEO tracking only |
| HIBP (HaveIBeenPwned) | Outbound | HTTPS | None (public) | Password breach check skipped |

---

## SECURITY IMPLICATIONS

| Flow | Risk | Control |
|---|---|---|
| Authentication | Credential stuffing | Rate limiting + lockout (E-CORE-005) |
| File upload | Malicious file injection | Extension + MIME whitelist; webroot exclusion |
| Webhook receipt | Replay attacks | Signature verification + idempotency key |
| AI requests | Prompt injection | Input sanitization before Gemini API call |
| PII export (GDPR) | Data leakage | Authenticated endpoint + rate limited + audited |
| Payment flow | Man-in-the-middle | TLS 1.3 enforced; no card data on platform |
| Cross-module data | Unauthorized access | RBAC + Policy enforced on every API route |

---

## SCALABILITY NOTES

| Flow | Bottleneck | Mitigation |
|---|---|---|
| AI matching on listing approval | Fan-out to many users | Queue + batch in Phase 2 |
| Email delivery | Brevo rate limits | Queue + retry with exponential backoff |
| Analytics cron under heavy write load | Slow aggregation queries | Indexed aggregation columns; read replica Phase 2 |
| Push notifications at scale | VAPID signing overhead | Batch push service Phase 2 |
| DW nightly ETL | Long-running transaction | Delta loads; separate MySQL connection; Phase 2 dedicated DB |

---

## APPROVAL SECTION

| Role | Name | Signature | Date |
|---|---|---|---|
| Platform Owner | | | |
| Lead Architect | | | |
| Security Officer | | | |

**Status:** Awaiting Review and Approval
**Gate:** Data flow must be approved before integration development begins.
