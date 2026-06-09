# PROJECT MEMORY

---

## Current Phase

Phase 1 — Foundation Build IN PROGRESS
Gate 0 (Host Capability Review): CLOSED / APPROVED — Phase 1 authorized
Active: Sprint 1 (Core Platform) · Task 1 (Laravel 11 Scaffold) materialized

Sprint 1 progress:
- T-1 Laravel 11 Project Scaffold — overlay files created in repo root
  (composer.json, package.json, .env.example, .gitignore, config/ics.php,
  tailwind/vite/postcss config, pint.json, phpstan.neon, phpunit.xml,
  resources/css+js, lang/en/ics, README, dir placeholders).
  Approved: package list as specified; PHPUnit 11; "create real files".
  Remaining for execution: team/CI runs composer create-project + install + npm
  (network). No models/controllers/migrations yet (Tasks 3+ gated).
- Spec of record: SPRINT_1_TASK_1_PROJECT_SCAFFOLD.md
- T-1 reviewed: SCAFFOLD_REVIEW.md — verdict PASS; D-037/D-039 confirmed intact;
  no business logic; no new decisions.
- T-2 Environment Configuration — COMPLETE (mechanism). Files: scripts/ci/
  check-hardcoded-drivers.sh (T-2.2), .github/workflows/ci.yml (T-2.3),
  docker-compose.yml (T-2.4). T-2.1 satisfied by existing config/ics.php + .env.example.
  Record: SPRINT_1_TASK_2_ENVIRONMENT_CONFIG.md
- DB engine CONFIRMED: MySQL 8.x (pinned mysql:8.0; override to 8.4 if prod is 8.4).
  LIM-03 RESOLVED — matches D-002, no MariaDB compatibility concern. Engine-parity
  CI job enabled.
- T-2 COMPLETE (T-2.1–T-2.4). Record: SPRINT_1_TASK_2_ENVIRONMENT_CONFIG.md
- MySQL version baseline recorded: 8.0 (override to 8.4 if prod differs).
- T-3 Core Database Foundation COMPLETE — 11 migration files, 18 tables, in
  database/migrations/ (core_tenants, core_users, personal_access_tokens, Spatie
  permission tables, core_audit_logs, consent+retention, sys queue/cache/sessions,
  notifications+notify_*, i18n_translations). Migrations only — no models/
  controllers/services. Review: DATABASE_FOUNDATION_REVIEW.md (verdict PASS).
- Task 3 review findings dispositioned (D-041, D-042):
  F-1 approved (remove stock Laravel migrations at bootstrap); F-2 approved
  (driver→table config wiring deferred to Task 4); F-3 APPROVED as blueprint
  amendment D-041 (password_reset_tokens added to DATABASE_BLUEPRINT; migration in
  Task 4); F-5 approved binding (mfa_secret encrypted at model layer, no plaintext;
  column changed VARCHAR(64)→TEXT — Task 3 migration to be corrected at Task 4 start,
  finding AF-1); F-6 approved (audit trigger optional, app-layer immutability
  primary); F-7 approved (record MySQL minor when available, no delay).
- Task 4 PLANNING approved: AUTHENTICATION_ARCHITECTURE_REVIEW.md. AF-1/AF-2/AF-3
  approved. AF-1 (mfa_secret→TEXT) + AF-3 recovery codes (D-043, mfa_recovery_codes)
  applied to Task 3 migration; blueprint/decision log updated.
- Task 4 IMPLEMENTED (Authentication Foundation): config (auth/session/cache/queue/
  mail-failover), models (Core\User encrypted mfa_secret + hashed recovery codes,
  Core\Tenant), services (PasswordRules+HIBP, LockoutService, MfaService),
  AccountLocked event, immediate ResetPasswordNotification (AF-2), RequireMfaForAdmins
  middleware, controllers (Auth/PasswordReset/Mfa/DataPrivacy-GDPR), routes/auth.php,
  password_reset_tokens migration. Review: TASK_4_IMPLEMENTATION_REVIEW.md (PASS).
- Companion wiring pending at integration (C-1..C-5): register routes/auth.php +
  middleware alias in bootstrap/app.php; remove stock Laravel migrations (F-1);
  confirm BCRYPT_ROUNDS/sanctum expiration; provide fallback SMTP.
- Deferred to later tasks: auth audit listeners (Task 6), role seeders (Task 5),
  web Blade views, security test suite (T-10.1).
- D-043 (MFA recovery code architecture) APPROVED.
- AUTHORIZATION_SECURITY_AUDIT.md generated (pre-Task-5). Verdict: CONDITIONAL PASS.
  TWO BLOCKERS before Task 5 (doc/decision only, no code):
  * AUTH-AUDIT-01 — permission naming inconsistency: PERMISSION_MATRIX uses
    module.resource.action but USER_ROLE_MATRIX §6.3 + Blueprint §6.3 state
    action.module.scope. Reconcile to ONE canonical form (recommend
    module.resource.action) before seeding.
  * AUTH-AUDIT-02 — role-assignment escalation gap: nothing prevents a Platform
    Admin granting Super Admin. Need policy guard (only Super Admin assigns Super
    Admin; no grant above own level) + audit.
  Other: org-owned Policies are the SOLE Phase 1 isolation control (TenantScope
  deferred) — must be rigorous; EP-1 (CRM read.all) + EP-2 (Gov Tier-4 knowledge)
  flagged for scoping decisions.
- Audit blockers RESOLVED (D-044):
  * AUTH-AUDIT-01 → canonical permission naming = {module}.{resource}.{action}
    (Blueprint §6.3 reconciled to PERMISSION_MATRIX).
  * AUTH-AUDIT-02 → role-assignment escalation guard with FOUR-EYES: only Super
    Admin assigns Super Admin AND a second Super Admin must approve; no grant above
    own level; all assignments audited. (Task 5 implements.)
  * EP-2 → Gov Agency Rep Tier-4 knowledge REMOVED (now Tier 1+2). Matrices updated.
  Carried into Task 5: R-3 org-owned ownership policies (sole Phase 1 isolation),
  R-4 Gate::before Super-Admin-only + default-deny, EP-1 CRM scoping (refinement).
- Task 5 IMPLEMENTED (RBAC, policies, escalation guard). D-045 approved
  (core_role_escalation_approvals — four-eyes storage; single-purpose; immutable
  trail via core_audit_logs). Files: migration 000013; Authorization\Roles +
  EscalationReasonCode; RoleEscalationApproval model; RoleAssignmentService
  (level guard + four-eyes); AuthServiceProvider (Gate::before Super-Admin-only);
  BasePolicy (default-deny + ownership helpers); UserPolicy; PermissionSeeder
  (~150 perms, canonical module.resource.action); RoleSeeder (13 roles);
  RolePermissionSeeder (mapping); DatabaseSeeder. Review: TASK_5_IMPLEMENTATION_REVIEW.md (PASS).
- Companion wiring pending: register AuthServiceProvider in bootstrap/providers.php;
  run db:seed; escalation HTTP endpoints (Task 7); route escalation audit via
  AuditService (Task 6); matrix-conformance + default-deny tests (T-10.1).
- Key risk carried: org-ownership policies are the SOLE Phase 1 isolation control
  (TenantScope deferred) — rigorous tests required as module models land.
- Task 5 extras delivered: RBAC_AUDIT_REPORT.md (sound) + RBAC_CONFORMANCE_TEST_SPEC.md
  (parity/escalation/four-eyes tests for T-10.1).
- Task 6 IMPLEMENTED (Audit Logging & Events). D-046 approved: core_audit_logs +
  category + sensitivity; ALL Super Admin actions = high-sensitivity; 6 sensitive
  categories. Files: AuditCategory/AuditSensitivity; AuditLog (append-only model);
  AuditRepository (write-only); AuditService (sensitivity rule + hashing); 9 core
  events (E-CORE-*) + AccountLocked; AuditEventSubscriber (synchronous);
  EventServiceProvider. Refactor: RoleAssignmentService now audits via AuditService
  (escalation_request/approval, high) + emits RoleAssigned. Wired: AuthController
  (UserLoggedIn/Out), PasswordResetController (PasswordChanged), DataPrivacyController
  (DataExportRequested/AccountDeletionRequested). Review: TASK_6_IMPLEMENTATION_REVIEW.md (PASS).
- Companion wiring: register EventServiceProvider + AuthServiceProvider in
  bootstrap/providers.php; optional audit DB trigger (F-6); off-box export (later);
  UserRegistered/AccountDeactivated/RoleRevoked dispatchers arrive in Task 7;
  audit immutability + high-sensitivity tests in T-10.1.
- USER_LIFECYCLE_GOVERNANCE_REVIEW.md generated (pre-Task-7). 5 control gaps to
  build into Task 7:
  * ULC-01 (HIGH): no approval/'pending' account state (status enum lacks it) →
    R-1, may need schema amendment.
  * ULC-02 (HIGH): role change doesn't revoke tokens/sessions → stale-privilege
    window → R-2.
  * ULC-03 (HIGH): no last-Super-Admin protection (deactivate/delete/revoke) → R-3.
  * ULC-04 (MED): reactivation could restore Super Admin, bypassing four-eyes → R-4.
  * ULC-05 (MED): self-registration role selection needs a whitelist → R-5.
  Plus: AccountSuspended/Reactivated events + wire dormant dispatchers (R-6);
  security alerting (R-7); break-glass/MFA-recovery runbooks (R-9).
- Lifecycle dispositions APPROVED (D-047): R-1 'pending' status (schema amended in
  blueprint + Task 3 core_users migration now; login denied unless active); R-2 token
  revoke on role change; R-3 last-Super-Admin protection; R-4 reactivation excludes
  Super Admin (four-eyes re-grant); R-5 self-register role whitelist; R-6 lifecycle
  events (AccountSuspended/Reactivated) + wire dormant dispatchers; R-7 security
  alerting. R-8/R-9/R-10/R-11 deferred.
- Task 7 IMPLEMENTED (User Management). Files: SuperAdminGuard; events
  AccountApproved/Suspended/Reactivated; UserLifecycleService (approve/suspend/
  reactivate/deactivate/delete + self-action + last-Super-Admin + R-4 reactivation
  guard); RegistrationService (R-5 whitelist); RoleAssignmentService edits (R-2
  token revoke, revokeRole + R-3, Super Admin grant alert); UserPolicy (approve/
  suspend/reactivate + self-action + R-3 at policy layer); SecurityAlertNotification
  + SecurityAlertSubscriber (R-7); AuditEventSubscriber (+3 events); EventServiceProvider
  (+security subscriber); UserManagementController, RoleManagementController,
  RegistrationController; routes/auth.php (register + /api/v1/admin); config/ics.php
  + .env (alert recipients). Verified intact: D-037, D-039, D-045, D-046; D-047 fully
  implemented. Review: TASK_7_IMPLEMENTATION_REVIEW.md (PASS). Also delivered:
  USER_LIFECYCLE_GOVERNANCE_REVIEW.md finalized + USER_MANAGEMENT_TEST_SPEC.md.
- Companion wiring: register routes/auth.php + 'mfa.admin' alias + providers in
  bootstrap; set ICS_SECURITY_ALERT_RECIPIENTS + MAIL_FALLBACK_*; tests in T-10.1.
- T7-1 logged as Sprint 1 Integration Verification Item (register 'mfa.admin' alias).
- LOCALIZATION_ARCHITECTURE_REVIEW.md generated (pre-Task-8). Verdict SOUND.
  CORRECTION: the i18n decision is D-014 (not D-012, which is CRM Scope) — request
  mis-referenced; validated D-014. Key items for Task 8: locale detection middleware
  (R-1); config-driven available_locales (R-2, D-037); <html lang/dir> for WCAG
  3.1.1 (R-3/LOC-6, D-028); accessible language switcher (R-4); date/time helper
  (R-5); currency helper (R-6). Phase 2/3: HasTranslations trait + caching (R-7),
  content fallback chain (R-8), translation workflow (R-9), per-content status (R-10).
- Task 8 IMPLEMENTED (Localization Foundation). Files: config/locales.php (registry,
  available en/fr/ar + active env-driven); LocaleRegistry; SetLocale middleware (R-1
  detection); resources/views/layouts/app.blade.php (<html lang/dir> — WCAG 3.1.1,
  LOC-6 RESOLVED); components/language-switcher.blade.php (R-4 accessible);
  DateTimeFormatter (R-5); CurrencyFormatter (R-6, intl + fallback); lang/fr + lang/ar
  dormant files; .env APP_ACTIVE_LOCALES=en. English default; fr/ar dormant-but-
  supported; RTL wired (dir=rtl) without activating Arabic. Verified intact: D-014,
  D-037; D-028 LOC-6 gap closed. Review: LOCALIZATION_IMPLEMENTATION_REVIEW.md (PASS).
  Also: LOCALIZATION_TEST_SPEC.md.
- Companion wiring: register SetLocale in bootstrap/app.php (web); ensure intl ext;
  npm build; tests in T-10.1.
- Phase 2/3 reserved: HasTranslations trait + caching (R-7), content fallback chain
  (R-8), translation workflow (R-9), per-content status (R-10).
- Sprint 1 Integration/Env Verification Items: T7-1 (register mfa.admin alias);
  C-1 SetLocale registration; C-2 intl extension.
- Task 9 IMPLEMENTED (Security Middleware). Files: config/security.php (headers +
  rate limits + trusted proxies, all env-driven); SecurityHeaders middleware (HSTS,
  CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy;
  strips X-Powered-By/Server); RateLimitServiceProvider (login/password-reset/mfa/
  public-forms/api named limiters); routes/auth.php (named limiters applied); .env
  (SECURITY_*, RL_*, TRUSTED_PROXIES). Session/cookie hardening already in
  config/session.php (Secure/HttpOnly/SameSite=Strict). Verified: D-039 maintained,
  D-037 preserved, D-028 unaffected. Review: SECURITY_MIDDLEWARE_REVIEW.md (PASS).
  Also: SECURITY_TEST_SPEC.md.
- FINDING T9-1 (MEDIUM): strict CSP blocks Alpine.js standard build → switch to
  @alpinejs/csp build (recommended) to keep CSP strict. Companion wiring: register
  SecurityHeaders (global) + RateLimitServiceProvider + trustProxies in bootstrap.
- T9-1 RESOLVED (D-048): strict CSP + @alpinejs/csp (package.json + app.js); reject
  unsafe-eval. T9-2 HSTS preload disabled till prod review. T9-3 explicit Cloudflare
  ranges before prod (integration item).
- Task 10 IMPLEMENTED (Quality & Delivery). Integration wiring consolidated:
  bootstrap/app.php (SecurityHeaders global, SetLocale web, mfa.admin alias,
  trustProxies, routes/auth.php load), bootstrap/providers.php (App/Auth/Event/
  RateLimit providers), AppServiceProvider, routes/web.php. Resolves prior companion
  items C-1/T7-1/security wiring IN CODE (host verification pending). Conformance
  tests: tests/TestCase + RbacConformanceTest, EscalationGuardTest, UserLifecycleTest,
  AuditImmutabilityTest, LocalizationTest, SecurityHeadersTest. CI already runs them.
- SPRINT 1 COMPLETE (T-1…T-10; D-001…D-048). Reports: SPRINT_1_COMPLETION_REPORT.md,
  SPRINT_1_GO_LIVE_CHECKLIST.md, SPRINT_2_READINESS_REVIEW.md (recommendation:
  CONDITIONAL GO).
- KEY CAVEAT: codebase is a complete reviewed OVERLAY; must be bootstrapped
  (create-project + install + npm + migrate + seed) and run GREEN in CI, and host
  capability spike executed, before Sprint 1 is operationally done (R-012/R-013).
- SPRINT 1 ACCEPTED COMPLETE (D-049). Sprint 2 CONDITIONAL GO: planning authorized;
  full module implementation gated behind 6 validation gates (bootstrap, DB, conformance,
  CI, host, go-live checklist signed).
- SPRINT_2_EXECUTION_PLAN.md delivered. Build order (waves): W1 Org Ownership framework
  + Unified Content Engine + CMS + CRM; W2 Client/Partner Portal; W3 Knowledge/Research;
  W4 Marketplace/Community/Training. Key risks: S2-1 org isolation is the SOLE Phase 1
  control (build ownership policies + isolation tests FIRST); S2-2 needs
  core_users.account_id linkage (schema amendment — sign off at Sprint 2 start).
- SPRINT_2_EXECUTION_PLAN approved. S2-2 accepted → D-050 (core_users.account_id,
  nullable FK→crm_accounts; column Wave 1a, FK Wave 1d; backward-compatible; supports
  TenantScope/BasePolicy/AccountScope). Blueprint core_users updated.
- SPRINT_2_WAVE_1_ARCHITECTURE_REVIEW.md generated (SOUND). Key clarification: org-owned
  data (Client/Partner portals) = ACCOUNT-scoped (AccountScope + ownership policies, two
  layers); content (CMS/Knowledge/Research) = TIER-scoped (ContentAccessService) —
  different mechanisms. crm_accounts = org anchor (FK target). Findings: W1-1 every
  org-owned model MUST use BelongsToAccount+Policy+isolation test (enforce via
  lint/checklist); W1-2 FK sequencing; W1-3 content≠org isolation.
- Wave 1 review APPROVED. WAVE 1a IMPLEMENTED (Org Ownership framework). Files:
  migration add_account_id_to_core_users (nullable + index; FK deferred to 1d);
  Roles::ICS_INTERNAL; AccountScope (Layer 1 global scope, cross-guard resolution,
  console-safe, acrossAccounts() escape hatch); BelongsToAccount trait (scope +
  create-stamping); OrgOwnedPolicy (Layer 2, accessible()=staff-bypass OR sameAccount);
  isolation harness (IsoFixture + IsoFixturePolicy + AssertsOrgIsolation trait +
  AccountIsolationTest covering enumeration/direct/null/staff/create-stamp). Content
  boundary preserved (AccountScope ≠ ContentAccessService). Review:
  WAVE_1A_IMPLEMENTATION_REVIEW.md (PASS). Finding W1a-2: enforce W1-1 via CI/larastan
  rule (table w/ account_id ⇒ trait+policy+test).
- Wave 1a APPROVED. WAVE_1B_ARCHITECTURE_REVIEW.md generated (DESIGN — content engine
  not yet implemented; interpretation: deliverable named ARCHITECTURE_REVIEW + "wait
  for approval after Wave 1b review" ⇒ design first, implement after approval).
  Engine design (D-038): HasContentLifecycle (draft→review→published→archived, slug,
  SEO, human-approval publish), HasFullTextSearch (MySQL FULLTEXT P1, config-driven
  Meilisearch swap P2), ContentAccessService (ONE service, hierarchical[Research/D-034]
  + lateral[Knowledge/D-036], separate from AccountScope), content_engagement_events
  (unified append-only — SUPERSEDES knowledge_views/knowledge_downloads/research_downloads,
  D-038 — blueprint reconciliation at impl, finding W1b-1). Seams: CMS (tier-1 public +
  lifecycle), Knowledge (lateral), Research (hierarchical). Translation-ready
  (HasTranslations/i18n_translations + fallback chain, Phase 2). Verdict SOUND.
- Wave 1b APPROVED for implementation. WAVE 1b IMPLEMENTED (Unified Content Engine,
  D-051). Files: AccessStrategy + ContentAccessible interface; strategy contract +
  HierarchicalAccessStrategy (D-034) + LateralAccessStrategy (D-036, Gov capped per
  D-044); ContentAccessService (strategy-driven, separate from AccountScope);
  HasContentLifecycle + HasFullTextSearch traits; ContentPublished/ContentArchived
  events; content_engagement_events migration + ContentEngagementEvent (append-only) +
  EngagementRecorder; config ics.search.driver. D-051: content_engagement_events
  SUPERSEDES knowledge_views/knowledge_downloads/research_downloads; ContentAccessService
  RETIRES Knowledge/ResearchAccessService. Blueprints reconciled. Review:
  WAVE_1B_IMPLEMENTATION_REVIEW.md (PASS). Finding W1b-i3: each searchable model's
  migration must add the FULLTEXT index.
- Wave 1b APPROVED. W1b-i3 ESCALATED → HIGH IMPLEMENTATION REMINDER: every searchable
  content model MUST declare FULLTEXT index in its migration (verify during CMS impl).
- WAVE_1C_ARCHITECTURE_REVIEW.md generated (DESIGN — CMS not implemented; deliverable
  named ARCHITECTURE_REVIEW + "wait for approval before implementation"). CMS =
  content_pages/articles/media; PUBLIC tier-1, NOT org-owned (no AccountScope); reuses
  HasContentLifecycle + HasFullTextSearch + ContentAccessService. Features: page/article
  mgmt, media library (alt_text REQUIRED — WCAG 1.1.1), SEO, slug mgmt, draft/review/
  publish (permission-gated). Audit-ready publish: wire ContentPublished/Archived →
  audit (new content_management category, W1c-4). Findings: W1c-1 FULLTEXT (HIGH),
  W1c-2 media alt_text required. Verdict SOUND.
- WAVE_1C_ARCHITECTURE_REVIEW APPROVED + added requirement D-052 (publication
  traceability: created_by/updated_by/published_by/published_at on CMS publishable
  content). WAVE 1c IMPLEMENTED (CMS = first content-engine consumer). Files: migrations
  content_pages/content_articles/content_media (4-state status, FULLTEXT title+body
  MySQL-guarded [W1c-1], D-052 columns, media alt_text); models Page/Article (engine
  traits + ContentAccessible tier-1 LATERAL 'cms') + Media; HasAuthorship concern
  (stamps created_by/updated_by); CmsService (publish stamps published_by then
  $model->publish()); Admin\Cms PageController/ArticleController/MediaController +
  Content\PublicContentController (read+search+view via EngagementRecorder); routes/cms.php
  registered in bootstrap/app.php (api group); AuditCategory::CONTENT_MANAGEMENT +
  AuditEventSubscriber handlers for ContentPublished/ContentArchived (module 'cms');
  config ics.media.{disk,path,max_kb}. CMS is PUBLIC tier-1, ContentAccessService-driven,
  AccountScope UNTOUCHED. Corrections (self-flagged): permission names realigned to
  canonical seeded set (cms.pages.update / cms.articles.update / cms.media.upload — none
  invented); Media uses uploaded_by (NOT HasAuthorship — those columns don't exist on
  content_media); DATABASE_BLUEPRINT reconciled (4-state + updated_by/published_by +
  view_count + FULLTEXT). Review: WAVE_1C_IMPLEMENTATION_REVIEW.md (SOUND; 6 validation
  sections: CMS Architecture, Search, Accessibility, SEO, Audit, Performance).
- WAVE 1C IMPLEMENTATION APPROVED (D-052 accepted; Unified Content Engine validated).
  Standing mandates reaffirmed: W1c-1 FULLTEXT mandatory for ALL future searchable
  content models; W1c-2 accessibility (alt_text) mandatory; W1c-4 content audit accepted.
- WAVE_1D_CRM_ARCHITECTURE_REVIEW.md generated (DESIGN — CRM NOT implemented; "wait for
  approval after the CRM architecture review"). Scope: crm_accounts/contacts/leads/
  opportunities/activities/notes. KEY FINDING W1d-1 (CRITICAL): CRM is INTERNAL-ONLY
  (D-012), visibility is ASSIGNMENT-scoped (assigned_to), NOT org-scoped — CRM must NOT
  use AccountScope/BelongsToAccount. crm_*.account_id is a SUBJECT pointer ("which account
  this record is about"), semantically DIFFERENT from core_users.account_id (D-050, "which
  org the viewing user belongs to") though both FK crm_accounts(id). CRM = a THIRD
  orthogonal control (permission + assignment), separate from BOTH AccountScope and
  ContentAccessService (both left untouched/unmixed). W1d-2 (CONFLICT): crm_notes is in
  scope but ABSENT from blueprint (notes currently = crm_activities type='note') — Option A
  (no new table, recommended) vs Option B (dedicated polymorphic crm_notes). W1d-3: activate
  D-050 FK core_users.account_id→crm_accounts ON DELETE SET NULL (crm_accounts-first
  ordering). W1d-4: resolve EP-1 (add crm.*.read.own assignment scope vs read.all). W1d-5:
  propose AuditCategory::CRM_MANAGEMENT. W1d-6: crm_proposals/crm_contracts in blueprint but
  OUT of Wave 1d scope (deferred). AI hooks (D-029 ai_qualification_score, qualify.ai,
  proposals) deferred to AI sprint. Validations PASS: D-050, Wave 1a isolation (untouched),
  D-037 TenantScope-ready (tenant_id present, additive, no schema change), D-012, D-025
  analytics. Proposed decisions on sign-off: D-053 (CRM assignment-scoped, not
  account-scoped), D-054 (CRM_MANAGEMENT audit category), + W1d-2 crm_notes choice.
- WAVE 1D CRM ARCHITECTURE REVIEW APPROVED. D-053 (CRM internal-only; permission +
  assignment access; NO AccountScope/BelongsToAccount/ContentAccessService). D-054
  (AuditCategory::CRM_MANAGEMENT). W1d-2 (crm_notes NOT created; notes = crm_activities
  type='note'). W1d-4 (crm.*.read.own + read.all across CRM entities). W1d-6 (defer
  crm_proposals/crm_contracts).
- WAVE 1D CRM IMPLEMENTED. Migrations: crm_accounts/contacts/leads/opportunities/activities
  (+ D-050 FK activation core_users.account_id→crm_accounts ON DELETE SET NULL, MySQL-
  guarded, migration 000006 AFTER crm_accounts 000001). Models Crm\Account/Contact/Lead/
  Opportunity/Activity. NEW concern HasAssignmentVisibility (scopeVisibleTo/visibleToUser —
  the THIRD isolation control: filters assigned_to/created_by, NEVER account_id; read.all
  bypasses). CrmService (changeLeadStage/changeOpportunityStage/assign/convertLead — atomic,
  fires events). CrmPipelineAggregator (D-025 hook, scheduled snapshot: pipeline value by
  stage, leads by source, win/loss, conversion). Events Crm\{LeadStageChanged,
  OpportunityStageChanged,CrmRecordAssigned,LeadConverted,CrmAccountDeleted} → 5 audit
  handlers under crm_management. Controllers Crm\{Account,Contact,Lead,Opportunity,Activity,
  CrmReport}; routes/crm.php (auth:sanctum, registered bootstrap/app.php). crm.*.read.own
  perms added to PermissionSeeder + ICS_CRM grant. Self-flagged: AI columns inert (D-029
  deferred); stage NOT mass-assignable (only via audited changeStage); Activity subject_type
  whitelisted (slug→class map); CrmAccountDeleted fired pre-delete. Blueprint reconciled
  (CRM module note; crm_activities +deleted_at/+assigned index W1d-7). Review:
  WAVE_1D_IMPLEMENTATION_REVIEW.md (SOUND; 6 sections: CRM Architecture, Access Control,
  Audit, Analytics, Isolation, Future TenantScope). THREE isolation mechanisms now distinct:
  AccountScope (org-owned portals) · ContentAccessService (content tiers) · assignment (CRM).
- WAVE 1D CRM IMPLEMENTATION APPROVED. D-050/D-053/D-054 validated. Three architectural
  boundaries now PROVEN and MUST remain separate: (1) AccountScope, (2) ContentAccessService,
  (3) HasAssignmentVisibility. WAVE 1 COMPLETE: 1a Ownership Framework, 1b Unified Content
  Engine, 1c CMS, 1d CRM.
- WAVE_2_ARCHITECTURE_REVIEW.md generated (DESIGN — Wave 2 NOT implemented; "wait for
  approval after the architecture review"). Scope: Client Portal (client_projects/
  milestones/deliverables/tickets/ticket_replies) + Partner Portal (partner_tiers/profiles/
  referrals/agreements). PIVOTAL: unlike CRM (D-053, account_id = subject, AccountScope
  FORBIDDEN), client_projects.account_id IS an OWNERSHIP key → Wave 2 is the FIRST real
  consumer of the Wave 1a ownership framework (D-050 "first full enforcement: Wave 2").
  Portals = org-owned via BelongsToAccount + AccountScope + OrgOwnedPolicy. Findings: W2-1
  (HIGH) child rows (milestones/deliverables/replies) have NO account_id → isolate via
  PARENT (Option A parent-scoped, recommended, not denormalise); W2-2 (HIGH) partner data
  keys on partner_id/user_id, account_id nullable → proposed D-055 (provision crm_account
  per partner incl. individuals; denormalise account_id onto partner_referrals/agreements
  so AccountScope is the SINGLE portal isolation mechanism); W2-3 (CRITICAL) partner must
  NEVER see the internal crm_lead from their referral — referral side (partner_referrals)
  and CRM side (crm_leads, D-053) stay separate, lead_id ICS-only; W2-4 (HIGH)
  client_ticket_replies.is_internal hidden from clients; W2-5 (MED) deliverable/agreement
  files policy-gated/signed, never public; W2-6 (MED) proposed D-056 AuditCategory::
  PORTAL_MANAGEMENT (commission/agreements = high-sensitivity, D-031/D-046); W2-9 (HIGH)
  mandatory per-model isolation tests (W1-1). Referral architecture: partner submits →
  ICS qualifies → crm_lead created+linked → commission from tier rate (D-031). Validations
  PASS: Wave 1a framework, D-050, D-037 TenantScope (tenant>account>user, additive), D-025
  analytics (per-org aggregates), D-046 audit. Proposed on sign-off: D-055 (partner
  account_id unification), D-056 (PORTAL_MANAGEMENT category), W2-1 (parent-scoped children).
- WAVE 2 ARCHITECTURE REVIEW APPROVED. D-055 (every partner gets a crm_account;
  partner_referrals/agreements.account_id REQUIRED; AccountScope = sole portal isolation).
  D-056 (AuditCategory::PORTAL_MANAGEMENT; agreement/commission/suspension = HIGH). W2-1
  (parent-based isolation; children never queried independently). W2-3 (Partner Portal never
  exposes crm_leads/CRM assignment/CRM workflow). W2-4 (internal ticket replies filtered at
  query+policy+resource layers).
- WAVE 2 IMPLEMENTED (Client Portal + Partner Portal — FIRST consumer of the Wave 1a
  ownership framework). Migrations: client_projects/milestones/deliverables/tickets/
  ticket_replies + partner_tiers/profiles/referrals/agreements (referrals & agreements got
  account_id per D-055). Org-owned models (BelongsToAccount): ClientProject, Ticket,
  PartnerProfile, PartnerReferral, PartnerAgreement. Children (parent-isolated, NO
  BelongsToAccount): ProjectMilestone, Deliverable, TicketReply. Policies (extend
  OrgOwnedPolicy, registered AuthServiceProvider): ClientProjectPolicy, TicketPolicy,
  PartnerProfilePolicy, PartnerReferralPolicy, PartnerAgreementPolicy. Services:
  ClientPortalService (project/deliverable/ticket transitions), PartnerPortalService
  (onboardPartner provisions crm_account D-055; qualifyReferral creates internal crm_lead
  W2-3 seam; commission/agreement/profile lifecycle). Aggregators: ClientPortalAggregator,
  PartnerPortalAggregator (D-025). 8 Portal events → AuditEventSubscriber under
  portal_management; AuditService gained $forceSensitivity override (agreement/commission/
  suspension forced HIGH, D-056). Controllers: Client\{Project,Milestone,Deliverable,Ticket,
  TicketReply}, Partner\{PartnerProfile,Referral,Agreement,PartnerDashboard}. routes/portal.php
  (auth:sanctum, nested + scopeBindings for W2-1), registered bootstrap/app.php. Defence in
  depth: lead_id $hidden + omitted from selects (W2-3); internal replies 3-layer filter
  (W2-4); deliverable/agreement files streamed behind policy+status (W2-5); status/stage
  never mass-assignable (only via audited services). partner.profiles.approve granted to
  ICS_CRM. Blueprint reconciled (D-055 account_id columns + Client/Partner module notes).
  Review: WAVE_2_IMPLEMENTATION_REVIEW.md (SOUND; 7 sections). Three isolation mechanisms
  remain distinct and now ALL live: AccountScope (portals) · ContentAccessService (content)
  · HasAssignmentVisibility (CRM).
- WAVE 2 IMPLEMENTATION APPROVED. D-055/D-056 validated. Three mechanisms proven + must stay
  independent: AccountScope, ContentAccessService, HasAssignmentVisibility. Wave 2 complete
  (Client Portal, Partner Portal, portal audit, parent-isolation model).
- WAVE_3_ARCHITECTURE_REVIEW.md generated (DESIGN — Wave 3 NOT implemented; "wait for
  approval after the architecture review"). Scope: Knowledge Center (D-036 LATERAL: tiers
  1 public/2 member/3 CLIENT/4 PARTNER/5 internal) + Research Center (D-034 HIERARCHICAL:
  1 public/2 member/3 partner/4 internal/5 admin, user_tier>=tier). DEFINING POINT: Wave 3
  proves the D-051 consolidation against BOTH access patterns at once — both reuse the ONE
  ContentAccessService via strategy flag; HierarchicalAccessStrategy + LateralAccessStrategy
  already exist (Wave 1b) and are tier-correct; NO third access service; retired
  Knowledge/ResearchAccessService stay retired. Both reuse HasContentLifecycle +
  HasFullTextSearch (K ft=title,excerpt,body; R ft=title,abstract). Engagement → unified
  content_engagement_events (view/download/citation) — now 3 consumers (CMS/Knowledge/
  Research). Findings: W3-1 (MED) access_tier is STRATEGY-RELATIVE (3=CLIENT in K, PARTNER
  in R) — never cross-compare; W3-2 (HIGH, real bug) AuditEventSubscriber handleContent
  Published/Archived hard-code module='cms' → must derive from $content->contentModule() so
  Knowledge/Research publish audits under correct module; W3-3 (MED) public teaser
  (title/excerpt/abstract/SEO always public) vs gated body/file — enforce at SERIALISER,
  never weaken ContentAccessService; downloads gated (canDownload + streamed like W2-5);
  W3-4 two citation concepts (research_citations graph vs engagement citation count); W3-5
  FULLTEXT columns differ per model (W1c-1); W3-6 bookmarks/ratings = user-state tables NOT
  append-only events; W3-7 Knowledge vs Research type separation (D-033, no type in both);
  W3-8 research_authors may be external (user_id NULL, ORCID). Validations PASS: D-034,
  D-036, D-038, D-051, D-025, D-014 (translation Phase 2 no schema change), D-029 (AI seams
  ai_suggested/metadata/abstract; implementation DEFERRED). NO new decisions required (clean
  reuse). Carry into impl: W3-2 audit module fix, W3-3 teaser projection.
- WAVE 3 ARCHITECTURE REVIEW APPROVED. D-034/D-036/D-038/D-051/D-025/D-014/D-029 validated.
  W3-1 (access tiers strategy-relative; NO cross-module tier comparison). W3-2 (audit must
  derive module from $content->contentModule(); do not hardcode 'cms'). W3-3 (public:
  title/excerpt/abstract/SEO; gated: body/files/protected assets; enforce in serializers/
  resources; do not weaken ContentAccessService).
- WAVE 3 IMPLEMENTED (Knowledge Center + Research Center — proves D-051 engine consolidation
  against BOTH access patterns via ONE ContentAccessService). W3-2 FIX applied:
  AuditEventSubscriber handleContentPublished/Archived now derive module via contentModuleOf()
  ($content->contentModule()) → 'knowledge'/'research'/'cms' (CMS unchanged). Migrations:
  knowledge_categories/articles/resources (FULLTEXT: articles title+excerpt+body, resources
  title+description) + research_categories/authors/publications/publication_authors (FULLTEXT
  title+abstract). Models implement ContentAccessible: KnowledgeArticle+KnowledgeResource
  (LATERAL D-036, accessTier from row), ResearchPublication (HIERARCHICAL D-034). W3-3 teaser
  enforced in JsonResources (KnowledgeArticleResource/KnowledgeResourceResource/
  ResearchPublicationResource): controllers compute entitled=ContentAccessService::canAccess
  and pass it; body/file only when entitled; file_path never serialised (only `downloadable`
  bool); gated streamed download endpoints (W2-5). Engagement view/download → EngagementRecorder
  → content_engagement_events (now 3 consumers CMS/Knowledge/Research); cached counters. Analytics
  hooks: Knowledge/ResearchAnalyticsAggregator (D-025). Controllers: public KnowledgeCenter/
  ResearchCenter + category + report; admin KnowledgeArticle/KnowledgeResource/Research
  Publication(+author sync)/ResearchAuthor. routes/library.php registered. RECONCILIATION
  (self-flagged): knowledge_resources NOT in original blueprint (was a knowledge_articles.type)
  → split into dedicated file-centric table REUSING the engine (no duplication, D-038); blueprint
  updated. research citation_count cached counter added; research_citations graph + knowledge
  tags/bookmarks/ratings/related DEFERRED (not in Wave 3 scope). AI (D-029) seams only
  (metadata JSON, abstract for embeddings); no AI calls. Review: WAVE_3_IMPLEMENTATION_REVIEW.md
  (SOUND; 7 sections). Three isolation mechanisms still distinct.
- WAVE 3 IMPLEMENTATION APPROVED. D-034/D-036/D-038/D-051/D-025/D-014/D-029 validated.
  Proven components: ContentAccessService, LateralAccessStrategy, HierarchicalAccessStrategy,
  Unified Content Engine, content_engagement_events.
- WAVE_4_ARCHITECTURE_REVIEW.md generated (DESIGN — Wave 4 NOT implemented; "wait for
  approval after the architecture review"). Scope: Training Institute + Community Platform
  (D-035) + Opportunity Marketplace (D-011). GOVERNING TENSION: Wave 4 = highest access-
  control proliferation risk — each module needs a DIFFERENT access rule and NONE of the 3
  proven mechanisms (AccountScope/ContentAccessService/HasAssignmentVisibility) fits.
  NEW module-local access rules (W4-1): Training=ENROLLMENT-gated (enrolled→lessons,
  is_preview public; policy check, not a scope); Community=VISIBILITY-scoped (public/
  authenticated, owner=user_id UNIQUE; NOT ContentAccessService — identity data not tiered
  content, W4-2); Marketplace=LISTING-STATUS+REVIEW (draft→pending_review→published public/
  expired/rejected + marketplace_listing_reviews; owner-draft/ICS-review/public-publish;
  distinct from HasContentLifecycle → MarketplaceListingService, W4-3). All permission-gated;
  proven three untouched. Findings: W4-4 cross-module community links (consultant→CRM lead,
  partner→partner_profiles, researcher→research_authors, trainer→instructors, founder/startup
  →startup_profiles) ONE-WAY, no CRM/portal internal leakage (W2-3 spirit); W4-5 (HIGH)
  enrollment-gated lessons + correct_answer NEVER sent to learners + certificate tamper-
  evident (unique cert number + public verification_url); W4-6 paid courses → Billing D-031
  (free enroll now, paid=invoice seam, payment exec deferred); W4-7 application/listing files
  gated+streamed (W2-5); W4-8 instructor approval + profile verification staff-only audited;
  W4-9 Training/Community/Marketplace use OWN counters+aggregators NOT content_engagement_events
  (not ContentAccessible); W4-10 LMS SCORM/xAPI Phase 2 reserved. Community = D-035 class-table-
  inheritance (community_profiles base + 6 type extensions). Validations PASS: D-035, D-025,
  D-029 (recommend/match seams deferred), D-037 (tenant_id present, additive), D-046.
  Proposed on sign-off: D-057 (Wave 4 access model — enrollment/visibility/listing-status;
  none use the 3 mechanisms; Community/Marketplace NOT in content engine), D-058 (audit
  categories TRAINING_MANAGEMENT/COMMUNITY_MANAGEMENT/MARKETPLACE_MANAGEMENT; CertificateIssued
  = HIGH).
- WAVE 4 ARCHITECTURE REVIEW APPROVED. D-057 (Wave 4 access model: Training enrollment-gated,
  Community visibility+owner-scoped, Marketplace listing-status+review+owner/applicant; NONE
  use AccountScope/ContentAccessService/HasAssignmentVisibility; Community+Marketplace NOT
  ContentAccessible). D-058 (audit categories TRAINING/COMMUNITY/MARKETPLACE_MANAGEMENT;
  CertificateIssued=HIGH). Build order: 4a Training → 4b Community → 4c Marketplace. Before
  4a: TRAINING_CERTIFICATION_GOVERNANCE_REVIEW.md (done → D-059).
- D-059 (Training Certificate Governance): numbering ICS-CERT-{YYYY}-{NNNNNN} via per-year
  sequence; verification_hash (SHA-256, immutable facts); status valid/expired/revoked/
  superseded; expires_at from course.validity_months; revoke (staff, terminal); reissue (new
  number, supersede, lineage); public minimal-disclosure verification. training_certificates
  + training_courses.validity_months + training_certificate_sequences (blueprint amended).
- WAVE 4a TRAINING IMPLEMENTED. 12 migrations (categories/instructors/courses/sections/
  lessons/enrollments/lesson_progress/assessments/questions/submissions/cert_sequences/
  certificates). Models in App\Models\Training. ACCESS = TrainingAccessService (enrollment-
  gated D-057: is_preview OR active enrollment OR staff/instructor; NOT a scope, NOT the 3
  mechanisms). Services: EnrollmentService (enrol free=active/paid=402 Billing seam W4-6;
  recordLessonProgress→complete→issue cert), AssessmentService (server-side autograde,
  attempts capped, correct_answer never sent W4-5), CertificateService (D-059 number/hash/
  verify/revoke/reissue), TrainingAnalyticsAggregator (D-025, own counters NOT
  content_engagement_events W4-9). AuditCategory TRAINING/COMMUNITY/MARKETPLACE_MANAGEMENT
  added; 6 Training events wired; CertificateIssued+Revoked forced HIGH (D-058). Controllers:
  public CourseCatalog + cert verify (throttled); learner Enrollment/Assessment/Certificate;
  admin Course(+addLesson,publish)/Instructor(apply,approve); TrainingReport. routes/training.php
  registered. AssessmentQuestion.correct_answer in $hidden (W4-5 defence-in-depth). Course
  publish = plain status (NOT ContentAccessible/HasContentLifecycle — avoids W3-2 mis-audit).
  Review: WAVE_4A_TRAINING_IMPLEMENTATION_REVIEW.md (SOUND; 6 sections). Three proven
  mechanisms untouched; Training adds a 4th module-local rule (enrollment).
- WAVE 4a TRAINING IMPLEMENTATION APPROVED. D-057/D-058/D-059 validated. FOUR module access
  mechanisms now proven + must NEVER merge: AccountScope, ContentAccessService,
  HasAssignmentVisibility, TrainingAccessService.
- WAVE_4B_ARCHITECTURE_REVIEW.md generated (DESIGN — Community NOT implemented; "wait for
  approval"). Scope: Community Platform (D-035 CTI: community_profiles base + 6 type
  extensions founder/startup/consultant/trainer/partner/researcher + skills/profile_skills/
  endorsements). Access = VISIBILITY (public/authenticated) + OWNER (user_id UNIQUE) — a 5th
  module-local rule; NOT ContentAccessible (D-057); four proven mechanisms untouched. CRITICAL
  FOCUS = cross-module link security (Community is D-035 connective tissue → CRM/Partner/
  Training/Research/Startup). Findings: W4b-1 (CRITICAL) cross-module links surface ONLY
  public extension fields, NEVER leak partner referrals/commissions/agreements/account_id,
  CRM leads/assignment, learner data, or restricted research (W2-3/W4-4 spirit; serializers
  whitelist, no cross-module lazy-load); W4b-2 (HIGH) link integrity — user may link ONLY to
  module records they own OR ICS-verified (anti-impersonation); W4b-3 consultant→CRM lead
  ONE-WAY (D-053, consultant never sees lead); W4b-4 CTI integrity (exactly one extension per
  profile matching profile_type, transactional); W4b-5 visibility module-local NOT
  ContentAccessService; W4b-6 endorsements/views/follows NOT audited (analytics; avoid
  flooding) — only verify/suspend audited under COMMUNITY_MANAGEMENT; W4b-8 own aggregator
  (W4-9); W4b-9 mentorship/collaboration/forums reserved D-035 Phase 2. Validations PASS:
  D-035, D-025, D-029 (matching seams deferred), D-037 (tenant_id present, additive), D-046.
  NO new decisions required (COMMUNITY_MANAGEMENT already added D-058; visibility=D-057;
  CTI=D-035). Carry into impl: W4b-1 (public-only projection), W4b-2 (link ownership/verify).
- WAVE 4b COMMUNITY ARCHITECTURE APPROVED with mandatory impl requirements: W4b-1 (serializers
  expose only public profile data — no CRM/partner commissions/agreements/portal ownership/
  learner/restricted research), W4b-2 (cross-module links require ownership OR ICS verification),
  W4b-3 (consultant→CRM one-way, no CRM data back), W4b-6 (views/follows/endorsements/searches =
  analytics; only governance uses COMMUNITY_MANAGEMENT audit).
- WAVE 4b COMMUNITY IMPLEMENTED. Migrations: community_profiles (CTI base, FULLTEXT
  display_name/tagline/bio) + 6 extensions (founder/startup/consultant/trainer/partner/
  researcher) + skills/profile_skills/endorsements. Models in App\Models\Community (base
  CommunityProfile w/ scopeVisibleTo + EXTENSIONS map + extension(); 6 extension models each
  with publicFields() whitelist; Skill/ProfileSkill/Endorsement). Partner extension model =
  PartnerCommunityProfile (avoids collision w/ Partner\PartnerProfile). ACCESS = visibility
  (public/authenticated)+owner+status, module-local (D-057), 5th mechanism; NOT
  ContentAccessible; 4 proven mechanisms untouched. W4b-1: CommunityProfileResource exposes
  base public fields + extension.publicFields() ONLY — link pointers (startup_id/instructor_id/
  partner_id/author_id) NEVER serialised, no join into linked modules. W4b-2:
  CommunityProfileService.assertLinksOwned rejects (422) partner_id/instructor_id/author_id not
  owned by user. W4b-3: ConsultantProfileCreated → Crm\CaptureConsultantLead (one-way, internal
  crm_lead source='community'; registered EventServiceProvider $listen since discovery off).
  W4b-6: view_count++ and Endorsement insert fire NO audit; only ProfileVerified +
  CommunityProfileStatusChanged → COMMUNITY_MANAGEMENT (suspend/hide HIGH). Analytics:
  CommunityAnalyticsAggregator (own, NOT content_engagement_events). Controllers:
  CommunityDirectory (public visibleTo+FULLTEXT search), CommunityProfile (owner store/update/
  mine/endorse), Admin\CommunityModeration (verify/status), CommunityReport. routes/community.php
  registered. Mentorship/collaboration/forums reserved (D-035 Phase 2). Review:
  WAVE_4B_IMPLEMENTATION_REVIEW.md (SOUND; 7 sections).
- WAVE 4b COMMUNITY IMPLEMENTATION APPROVED. D-035/D-057/D-025/D-029/D-037/D-046 validated.
  Confirmed: CTI, visibility+owner access, link ownership verification, one-way CRM lead
  capture, analytics-only engagement, COMMUNITY_MANAGEMENT audit boundaries.
- WAVE_4C_ARCHITECTURE_REVIEW.md generated (DESIGN — Marketplace NOT implemented; "wait for
  approval"). Scope: Opportunity Marketplace (marketplace_categories/listings/applications/
  listing_reviews + D-011 workflow Submission→Review→Approval→Publication). DEFINING CONCERN =
  trust/fraud (first PUBLIC user-generated published surface). Access = LISTING-STATUS+REVIEW+
  OWNER/APPLICANT (D-057, 6th module-local rule; published=public, pre-publish=owner+reviewer,
  applications=applicant+poster+ICS); NOT ContentAccessible; organisation_id is INFORMATIONAL
  provenance NOT an isolation key (W4c-1, AccountScope NOT applied — same discipline as D-053).
  Lifecycle via MarketplaceListingService (NOT HasContentLifecycle, W4-3). KEY GAP W4c-2
  (HIGH): blueprint has NO abuse-reporting table → cannot build reporting → proposed D-060 adds
  marketplace_listing_reports. TRUST MODEL (mandatory): listing verification (restricted posting
  rights ICS/approved partners/orgs + mandatory pre-publication review, no auto-publish);
  application verification (auth-only, unique per listing, attachments gated/streamed W4-7/W2-5);
  duplicate detection (unique application constraint + listing similarity flag to reviewer);
  spam prevention (rate limits + review gate + poster reputation); expiry (scheduled job
  deadline→expired + lazy scope filter); moderation workflow (reviewer queue + post-publish
  unpublish/remove + report-threshold auto-hide fail-safe); abuse reporting (D-060 table).
  Audit MARKETPLACE_MANAGEMENT (approve/reject/remove + application decisions + report
  resolution; views/applications/report-creation = analytics NOT audit). Analytics own
  aggregator (W4-9). AI match/dedup seams deferred (D-029). Validations PASS: D-025, D-029,
  D-037, D-046, D-057. Proposed on sign-off: D-060 (Marketplace Trust Model + marketplace_
  listing_reports abuse table).
- WAVE 4c MARKETPLACE ARCHITECTURE APPROVED. D-060 APPROVED (Marketplace Trust Model + NEW
  marketplace_listing_reports table). Principles: organisation_id=provenance not isolation;
  NO AccountScope; NOT ContentAccessible; published listings public; applications private.
- WAVE 4c MARKETPLACE IMPLEMENTED. Migrations: marketplace_categories/listings/applications/
  listing_reviews/listing_reports (D-060). Models in App\Models\Marketplace. ACCESS = listing-
  status+review+owner/applicant (D-057, 6th module-local rule); MarketplaceListing.scopePublicVisible
  (published + non-expired); NOT ContentAccessible, NO AccountScope; organisation_id provenance
  only. Services: MarketplaceListingService (submit→pending_review NO auto-publish; approve/
  reject/remove + ListingReview record + ListingReviewed event; expireOverdue; duplicate_suspected
  flag), ApplicationService (apply — DB unique listing+applicant prevents dup → 422;
  changeStatus→ApplicationStatusChanged), ReportService (report; autoHideIfOverThreshold ≥
  config ics.marketplace.report_autohide_threshold → pending_review fail-safe; resolve→
  ListingReportResolved), MarketplaceAnalyticsAggregator (own, W4-9). Events → MARKETPLACE_
  MANAGEMENT audit (3 handlers; remove=HIGH; report RESOLUTION audited, report/application/view
  CREATION = analytics). Controllers: public Marketplace (publicVisible+FULLTEXT); auth Listing
  (store/submit/mine), Application (apply/mine/forListing/changeStatus/downloadAttachment streamed
  gated W4-7/W2-5), Report (report/index/resolve), Admin\Moderation (queue/approve/reject/remove),
  MarketplaceReport. submit/apply/report throttled. routes/marketplace.php + routes/console.php
  (NEW — daily marketplace:expire-listings schedule) registered. Blueprint: +marketplace_listing_
  reports, +removed status, governance note. Review: WAVE_4C_IMPLEMENTATION_REVIEW.md (SOUND; 6
  sections). WAVE 4 COMPLETE (4a Training, 4b Community, 4c Marketplace). SIX module access
  mechanisms now exist & must stay separate: AccountScope, ContentAccessService,
  HasAssignmentVisibility, TrainingAccessService, Community visibility, Marketplace listing-status.
- WAVE 4c MARKETPLACE IMPLEMENTATION APPROVED (D-060 validated). WAVE 4 fully complete.
- ROADMAP REVIEW PHASE (no code): producing ECOSYSTEM_ROADMAP_REVIEW.md (6 future modules:
  Startup Hub, Incubator, Accelerator, Investment Network, Membership System, Franchise
  Operations), ACCESS_CONTROL_CONSOLIDATION_REVIEW.md (inventory all access mechanisms), and
  WAVE_5_ARCHITECTURE_PLAN.md (sequence Startup Hub→Incubator→Accelerator→Investment Network).
  Architecture-review mode ONLY; no migrations/models/controllers/services. Validated against
  D-037/D-038/D-050/D-053/D-055/D-057/D-060. Conclusions: NO future module needs a brand-new
  access mechanism FAMILY — Startup Hub/Incubator/Accelerator = MEMBERSHIP/participation family
  (same as TrainingAccessService, thin per-module services, NOT merged); Investment Network =
  membership/grant (data-room) + a NDA/financial SENSITIVITY compliance overlay (not a new
  mechanism); Membership System = ContentAccessService tier-elevation hook + Billing (D-031);
  Franchise Operations = activate the RESERVED TenantScope (Phase 3, D-037/D-004/D-019;
  tenant_id already on all tables; nests ABOVE AccountScope per D-050 #4). Recommendation: 6
  mechanisms stay SEPARATE (no merge — avoids coupling); optionally share a ParticipationGate
  conformance contract for the membership family. Franchise = highest governance (tenant
  isolation load-bearing); Investment Network = securities/financial compliance (B-1).
- WAVE 4c + ECOSYSTEM ROADMAP REVIEW APPROVED. Accepted conclusions: no future module needs a
  new access-mechanism FAMILY; six mechanisms stay SEPARATE; Startup Hub/Incubator/Accelerator/
  Investment = membership/participation family; Membership = ContentAccessService tier hook +
  Billing; Franchise = activate reserved TenantScope. Wave 5 order: 5a Startup Hub → 5b Incubator
  → 5c Accelerator → 5d Investment Network (5d behind INVESTMENT_GOVERNANCE_REVIEW).
- WAVE_5A_ARCHITECTURE_REVIEW.md generated (DESIGN — Startup Hub NOT implemented). Confirmed:
  REUSE participation-family access (founder-owner + team membership + program participation;
  thin StartupAccessService); NO new mechanism. Startup Hub schema exists (startup_profiles
  founder_id-owned, team_members, milestones, mentors, programs, program_enrollments). KEY
  FINDINGS: (CRITICAL) ownership-percentages/cap-table = sensitive financial → DEFER to
  Investment Network data-room (5d) or heavily gate; never public/Community (leakage). (HIGH)
  lifecycle reconciliation — requested 7-stage lifecycle (idea/registered/validation/incubation/
  acceleration/investment_ready/alumni) doesn't map to existing status/stage/program_type enums;
  propose single lifecycle_stage, avoid 3 overlapping enums. (HIGH) founder departure/ownership
  transfer = governance-sensitive → audited HIGH; explicit transfer (reassign founder_id, never
  orphan). (HIGH) startup_profiles ≠ crm_accounts — founder-owned NOT account-owned; CRM link
  ONE-WAY (D-053); no AccountScope (D-050). (MED) founder invitation flow; advisory board =
  extend startup_mentors type (not new table); public vs internal data (Community public-only
  W4b-1). Audit: propose STARTUP_MANAGEMENT (founder/ownership transfer HIGH). Reuse Training
  cert governance (D-059) for program certs; Marketplace (D-060) for opportunity applications.
  TenantScope-ready (Franchise/multi-region). Verdict: SOUND WITH CONDITIONS. Proposed: D-061
  (Startup access participation-family), D-062 (STARTUP_MANAGEMENT audit), D-063 (lifecycle
  model + cap-table deferral to Investment Network).
- WAVE 5A ARCHITECTURE APPROVED. D-061 (Startup participation access), D-062 (STARTUP_MANAGEMENT
  audit), D-063 (lifecycle_stage authority), D-064 (governance protection). Dispositions: C-1
  (cap-table=Investment Network data, gated, 5d system of record), H-1 (one lifecycle_stage),
  H-2 (founder transfer/orphan guard/immutable history), H-3 (founder-owned ≠ crm_accounts).
- WAVE 5A STARTUP HUB IMPLEMENTED. Migrations: startup_profiles (D-063: lifecycle_stage
  authoritative, stage=product, status narrowed, program_type REMOVED; founder_id-owned, no
  account_id/AccountScope H-3), startup_team_members (role enum M-4 + gated ownership_percent
  $hidden C-1), startup_team_invitations (M-2), startup_ownership_transfers (immutable H-2),
  startup_milestones, startup_mentors (+type mentor/advisor M-3), startup_programs +
  enrollments. Models in App\Models\Startup (OwnershipTransfer append-only throws). ACCESS =
  StartupAccessService (participation family D-061: isFounder/isTeamMember/canManage/
  canViewOwnership; staff/owner bypass; NOT AccountScope/ContentAccessible — 6 mechanisms still
  separate, no new one). Services: FounderService (transferOwnership immutable+HIGH audit;
  removeMember orphan-guard — primary founder removal blocked until transfer, ≥1 active founder),
  OwnershipService (D-064: ≤100% non-negative; OwnershipChanged HIGH, amounts NOT recorded C-1),
  StartupGovernanceService (verify/suspend/reactivate/graduate/setLifecycle; StartupStatusChanged
  verify/suspend/reactivate HIGH), StartupAnalyticsAggregator (own, W4-9, NO ownership data C-1).
  Events→STARTUP_MANAGEMENT (D-062). CRM one-way: StartupCreated→Crm\CaptureStartupLead
  (EventServiceProvider $listen; D-053/H-3). StartupPublicResource (public projection C-1/M-1 —
  excludes ownership/milestones/mentor notes). Controllers: Startup (public dir + create/update/
  mine), Team (invite/accept/remove/transfer), Ownership (gated show/set), Milestone, Program,
  Admin\StartupGovernance, StartupReport. routes/startup.php registered. Blueprint reconciled.
  Review: WAVE_5A_IMPLEMENTATION_REVIEW.md (SOUND; 8 sections).
- WAVE 5A STARTUP HUB IMPLEMENTATION APPROVED. D-061/D-062/D-063/D-064 validated; 8 validation
  results confirmed (no new access family, participation reuse, founder-owned, CRM/Community
  boundaries, cap-table confidentiality, lifecycle authority, governance controls).
- WAVE_5B_ARCHITECTURE_REVIEW.md generated (DESIGN — Incubator NOT implemented). Incubator EXTENDS
  Startup Hub via startup_programs(type=incubator)+enrollments. MOST IMPORTANT FINDING (item 11):
  Accelerator = OPTION B (specialization of the SAME Program Architecture, NOT separate) — D-038
  no-duplication → build a GENERIC Program Architecture in 5B that 5c specializes. Access (item 7):
  OPTION B recommended = thin ProgramParticipationService (participation family, composes with
  StartupAccessService; reused by Accelerator) — NOT overload StartupAccessService; NO new family.
  Audit (item 8): recommend ONE PROGRAM_MANAGEMENT category (NOT INCUBATOR_MANAGEMENT) since
  accelerator specializes same arch (avoid duplication); forced removal/termination + fee events
  HIGH. Findings: H-1 (build GENERIC program arch not incubator-specific), H-2 (single
  PROGRAM_MANAGEMENT), H-3 (program transitions WRITE to single lifecycle authority D-063, no
  parallel state), M-1 (governed intake applied→accepted→active), M-2 (ProgramParticipationService),
  M-3 (completion thresholds via Training D-059, no parallel LMS), M-4 (cohort modelling), M-5
  (coordinator assignment is program concern NOT CRM HasAssignmentVisibility). Validations PASS:
  D-019, D-025, D-029, D-037 (Franchise/regional/multi-country additive), D-038, D-053, D-057,
  D-059, D-061..064. Verdict SOUND WITH CONDITIONS. Proposed: D-065 (Generic Program Architecture;
  Incubator+Accelerator = type specializations; ProgramParticipationService), D-066
  (PROGRAM_MANAGEMENT audit category).
- WAVE 5B ARCHITECTURE APPROVED. D-065 (Generic Program Architecture — ONE arch; Incubator+
  Accelerator share programs/cohorts/intake/applications/enrollments/participation/progression/
  graduation/analytics/audit; Accelerator adds specialized features only). D-066 (PROGRAM_
  MANAGEMENT replaces INCUBATOR_/ACCELERATOR_; type as context; forced removal/termination/
  suspension/reinstatement/fees/graduation-reversals HIGH). H-3 (lifecycle routes through D-063,
  no parallel). M-1 (governed intake applied→under_review→accepted→active→graduated→withdrawn,
  no bypass). M-2 (coordinators = program concern, NOT CRM/HasAssignmentVisibility). D-067
  (no double cohort entry; no conflicting active states; graduation completion-validated;
  withdrawal/removal reason mandatory; cohort closure + program archival audited).
- WAVE 5B GENERIC PROGRAM ARCHITECTURE IMPLEMENTED (Incubator instantiates type=incubator).
  Migrations: program_cohorts (intake cycles), program_coordinators (M-2), extend
  startup_program_enrollments (cohort_id + M-1 status flow + decision/reason fields + UNIQUE
  startup+cohort D-067), widen startup_programs.status (suspended/terminated/archived). Models:
  ProgramCohort, ProgramCoordinator; extended ProgramEnrollment (ACTIVE_STATES guard) +
  StartupProgram. ProgramParticipationService (participation family D-065; composes with
  StartupAccessService — NOT overload, NOT new mechanism). Services: IntakeService (apply/review/
  accept/reject; D-067 no-double-entry + no-conflicting-active guards; accept routes lifecycle via
  StartupGovernanceService H-3 → incubation/acceleration by type), ProgramEnrollmentService
  (graduate[CompletionValidator D-067]/withdraw[reason]/forceRemove[reason]/reverseGraduation),
  ProgramGovernanceService (cohort close/archive; program suspend/reinstate/terminate/archive),
  ProgramAnalyticsAggregator (generic, snapshot(type), W4-9). Events Program\{ParticipationChanged,
  ProgramGovernanceChanged} → PROGRAM_MANAGEMENT (removed/graduation_reversed/suspend/reinstate/
  terminate HIGH). Controllers Program\{Cohort,Intake,Participation,ProgramGovernance}.
  routes/program.php registered (GENERIC — 5c reuses). Blueprint reconciled. Review:
  WAVE_5B_IMPLEMENTATION_REVIEW.md (SOUND; 7 sections incl Accelerator Compatibility CONFIRMED).
- WAVE 5B IMPLEMENTATION APPROVED. D-065/D-066/D-067 validated; 8-point summary confirmed
  (generic arch; Incubator=configuration not separate platform; ProgramParticipationService
  extends participation family; lifecycle centralized D-063; no CRM-assignment/Startup-ownership
  duplication; governance/cohort protections; Accelerator specialization path preserved).
- WAVE_5C_ARCHITECTURE_REVIEW.md generated (DESIGN — Accelerator NOT implemented). PRIMARY
  OBJECTIVE met: Accelerator = THIN SPECIALIZATION (type='accelerator') of the Generic Program
  Architecture; estimated reuse ~85% (>80%). New surface ONLY = generic program_events
  (demo_day/pitch/showcase/readiness_review/graduation_showcase types) + judges + scores +
  readiness signal (M-1, one mechanism not 5 subsystems). Reuses programs/cohorts/intake/
  enrollment/participation/lifecycle/governance + ProgramParticipationService + PROGRAM_MANAGEMENT
  audit + ProgramAnalyticsAggregator. CRITICAL GUARDRAIL CG-1: Accelerator PREPARES, Investment
  Network (5d) EXECUTES — Accelerator must NOT build investor registry / fundraising / cap-table
  store / due-diligence (= governance violation; none proposed). H-1 Investor Showcase =
  EXPOSURE ONLY (curated/public, no data-room/cap-table, W4b-1/C-1/M-1); H-2 investors reference
  existing Community/5d identities (no duplicate registry); H-3 readiness/demo-day data
  NON-financial. M-2 graduation gated by readiness via CompletionValidator (reuse D-067). M-4
  judge scoring integrity. Audit reuses PROGRAM_MANAGEMENT (+score/readiness override HIGH).
  Validations PASS: D-025/D-029/D-037 (regional/multi-country/franchise)/D-046/D-053/D-057/D-059/
  D-061/D-063/D-065/D-066/D-067. Verdict SOUND WITH CONDITIONS. Proposed: D-068 (Accelerator thin
  specialization), D-069 (Accelerator↔Investment Network PREPARE-vs-EXECUTE boundary).
- WAVE 5C ARCHITECTURE APPROVED. D-068 (Accelerator thin specialization), D-069 (Accelerator↔
  Investment Network PREPARE-vs-EXECUTE boundary). Conditions ratified CG-1 (Accelerator
  PROHIBITED from investor registry/fundraising/due-diligence/transaction/cap-table/deal-room/
  matching — future attempt = governance violation), H-1 (Showcase = exposure/discovery/readiness
  signal only), H-2 (reference existing investor identities, no duplicate registry), H-3 (readiness
  = operational maturity only; no valuation/equity/financial), M-1 (generic program_events, one
  arch), M-2 (CompletionValidator = graduation authority). DIRECTIVE: Program Events layer =
  reusable ecosystem infra but LIGHTWEIGHT (no orchestration/workflow states/process engine);
  before 5D validate whether Investment Network/Community/Marketplace can consume it without
  turning it into a workflow engine.
- WAVE 5C ACCELERATOR IMPLEMENTED (thin specialization, ~85% reuse). New surface ONLY: migrations
  program_events (types demo_day/pitch_event/showcase/readiness_review/graduation_showcase;
  finalized_at lock, NO workflow engine), program_event_judges (existing users referenced, H-2),
  program_event_scores (unique judge×startup×criterion M-4; maturity-only H-3). Models
  ProgramEvent/ProgramEventJudge/ProgramEventScore. EventService (create/assignJudge/submitScore/
  finalize/ranking[derived L-1]/showcaseExposure[curated public H-1]). ReadinessCalculator
  (avg of finalized readiness_review scores; GRADUATION_THRESHOLD=70). CompletionValidator EXTENDED:
  accelerator graduation gated by readiness threshold (M-2, single authority, no parallel engine).
  Audit: Program\EventActivity → PROGRAM_MANAGEMENT (override/revoke HIGH); NO new category.
  Controllers Program\{Event,Showcase,Readiness}; routes added to GENERIC routes/program.php (not
  an accelerator silo). REUSED UNCHANGED: ProgramParticipationService, IntakeService,
  ProgramEnrollmentService, StartupGovernanceService, ProgramGovernanceService,
  ProgramAnalyticsAggregator, StartupAccessService. NO investment/cap-table/investor-registry/
  fundraising (D-069 boundary intact). Blueprint reconciled. Review: WAVE_5C_IMPLEMENTATION_REVIEW.md
  (SOUND; 10 mandatory validations pass). Startup Hub program family (5a Startup Hub, 5b Incubator/
  Generic Program Arch, 5c Accelerator) complete; NEXT = Wave 5d Investment Network (behind
  INVESTMENT_GOVERNANCE_REVIEW).
- WAVE 5C IMPLEMENTATION APPROVED. D-068/D-069 validated; 10-point implementation validation
  confirmed (thin specialization; reuse >80%; ProgramParticipationService unchanged;
  CompletionValidator sole graduation authority; PROGRAM_MANAGEMENT only category; no investor
  registry/fundraising/cap-table/investment execution; D-069 boundary intact).
- PRE-5D VALIDATION DONE (Program Events consumption): Program Events CAN be consumed by Investment
  Network/Community/Marketplace SAFELY but ONLY READ-ONLY (reference/signal). Standing rule:
  Program Events stays lightweight (append/finalize only); NO module may push workflow states/
  orchestration/process behavior into it; each consuming module keeps its OWN workflow records.
- WAVE_5D_ARCHITECTURE_REVIEW.md generated (DESIGN — Investment Network NOT implemented; highest-
  governance/securities module). 14 areas + Tests A-E + risk analysis. Test A: REUSE participation/
  GRANT family (DataRoomAccessService) + NDA + financial-confidentiality OVERLAY — NO new access
  family. Test B: ALL financial/cap-table/valuation/fundraising/DD data ISOLATED inside ONE
  NDA-gated, encrypted, per-document-audited data room (system of record, C-1); every other module
  holds public projections only (W4b-1/H-1) — SATISFIED. Test C: D-063 lifecycle + D-064 founder
  governance INTACT (data room authoritative for full cap table; Startup Hub ownership_percent =
  governance subset reconciled). Test D: D-069 Prepare-vs-Execute ENFORCEABLE (Investment Network =
  EXECUTE side). Test E: TenantScope wraps grants, additive. Investor identity 2-layer: Community
  'investor' public profile (NEW D-035 extension) + investment_investor_profiles regulated (KYC/
  accreditation/mandate, gated) — no duplicate registry (H-2). FINDINGS: 5D-C1 CRITICAL securities/
  KYC/AML/accredited-investor multi-jurisdiction compliance → MANDATORY legal governance review
  (proposed D-075) HARD PREREQUISITE; 5D-C2 CRITICAL data room sole/encrypted/gated store; 5D-H1
  NDA precondition; 5D-H2 no duplicate investor registry; 5D-H3 cap-table authority reconciliation;
  5D-H4 Program Events read-only. DD is Investment-specific (own records, NOT Program Events).
  Audit: propose INVESTMENT_MANAGEMENT (per-document access logged; financial events HIGH).
  Analytics aggregate-only (no PII/financials). Billing fees recorded here, executed by Billing
  (D-031). MISSING: entire investment_* schema is NEW (no investment module in blueprint).
  Verdict SOUND WITH CONDITIONS (gated on D-075 legal review + D-070..D-074). Proposed: D-070
  (grant-family access+NDA overlay), D-071 (INVESTMENT_MANAGEMENT audit), D-072 (data room sole
  store), D-073 (investor identity 2-layer), D-074 (cap-table authority), D-075 (mandatory
  INVESTMENT_GOVERNANCE_REVIEW gate).
- WAVE 5D ARCHITECTURE CONDITIONALLY APPROVED: ARCHITECTURALLY APPROVED, IMPLEMENTATION NOT
  APPROVED (denied pending D-075). Ratified: D-070 (grant-family access + NDA overlay), D-071
  (INVESTMENT_MANAGEMENT audit + per-document access logging), D-072 (data room sole encrypted
  isolated financial store), D-073 (two-layer investor identity), D-074 (cap-table authority +
  D-064 reconciliation). D-075 (mandatory legal/compliance governance review) OPEN/BLOCKING.
- INVESTMENT_GOVERNANCE_REVIEW.md produced (D-075 gate, NOT legal advice — requires qualified local
  counsel sign-off). 15 sections: regulatory classification (informational→facilitation→brokerage→
  crowdfunding spectrum; lines = no public solicitation / no execution-custody / no contingent fees /
  no public crowdfund), jurisdictions (Nigeria SEC+CBN/NDPA2023; Ghana SEC/Act929/DPA2012; Kenya
  CMA crowdfunding-2022/DPA2019; SA FSCA/FAIS/Companies Act/FICA/POPIA; cross-border = highest
  complexity → phased single-jurisdiction Nigeria-first), investor tiers (public/verified/accredited/
  institutional → grant+redaction levels), startup stages (idea/early/revenue/growth gate disclosure),
  KYC/AML/NDA/data-room/financial/cap-table/DD governance, audit+retention (5-7yr), privacy+residency
  (B-2). RISK REGISTER: IR-1 unlicensed brokerage / IR-2 public solicitation / IR-3 crowdfunding-
  without-license / IR-5 financial data breach = CRITICAL. MANDATORY QUESTION → RECOMMEND OPTION C
  (Investment Facilitation: structured deal support + DD + data rooms, NO execution/custody) with
  hard guardrails (no execution/custody, no public solicitation, no contingent fees, no transacting
  matching engine, phased single-jurisdiction); Option D (Investment Marketplace/direct fundraising)
  = NO GO without securities/crowdfunding licensing. OUTCOME: CONDITIONAL GO (under Option C) — NOT
  full GO (needs external counsel sign-off), NOT NO GO. D-075 seven conditions: (1) qualified legal
  counsel sign-off per jurisdiction, (2) KYC/AML program, (3) counsel-validated e-NDA, (4) data-
  residency/privacy/DPIA, (5) D-072 data-room controls, (6) guardrail enforcement, (7) phased rollout.
- HOLDING after INVESTMENT_GOVERNANCE_REVIEW: STOP — Wave 5D implementation DENIED pending D-075
  closure (external legal sign-off + 7 conditions). Still gated: D-049 (bootstrap + GREEN CI,
  R-012/R-013).
- D-075 STATUS ACKNOWLEDGED: remains OPEN/BLOCKING; Wave 5D (Investment Network) FROZEN until
  external legal/compliance review + 7 closure conditions satisfied. No Wave 5D work authorized.
  ROADMAP ADJUSTMENT: pivot to the next INDEPENDENT stream → Franchise Operations / TenantScope
  activation (the reserved scope from D-004/D-019/D-037/D-050). Architecture review only.
- FRANCHISE_TENANTSCOPE_ARCHITECTURE_REVIEW.md produced (DESIGN — activation of RESERVED TenantScope;
  no code). core_tenants EXISTS since Sprint 1 (name/slug/domain/status/settings); tenant_id ALREADY
  on all 38 owned-parent tables; children inherit via parent (W2-1 pattern). Validation A–E PASS:
  (A) TenantScope ADDITIVE tenant>account>user (D-050#4), does NOT replace AccountScope; (B) all
  modules compatible WITHOUT redesign; (C) D-037 config-only TRUE (columns exist, .env flip, additive
  backfill not redesign); (D) D-050 account_id correct; (E) D-053 CRM assignment unaffected. F = 38
  parent tables with tenant_id. G = (a) ~22 child/pivot inherit via parent (NO change); (b) reference
  tables partner_tiers/training_course_categories/marketplace_categories/community_skills need
  global-vs-per-tenant DECISION (D-078); (c) analytics aggregation tables need tenant dimension;
  (d) user-scoped (notifications/escalations/consent) inherit via user; (e) system/RBAC/i18n/sys_*
  stay tenant-AGNOSTIC (must NOT scope). Activation = BelongsToTenant trait + TenantScope global
  scope (mirrors BelongsToAccount/AccountScope), config-gated ics.tenancy.enabled, bypass
  super-admin>tenant + ICS_INTERNAL within tenant. Risk FT-1 cross-tenant leakage CRITICAL
  (exhaustive isolation tests = release gate); residency B-2 (multi-country → regional VPS). OUTCOME:
  CONDITIONAL GO. Proposed: D-076 (activation model+bypass), D-077 (default-tenant+backfill config-
  only), D-078 (reference-data tenancy), D-079 (Franchise Admin role + core_tenants ext parent_tenant_
  id/country/residency); optional TENANT_MANAGEMENT audit category. Sequencing: ratify D-076-079 →
  backfill default tenant → TenantScope + isolation tests GREEN → pilot 2nd tenant → multi-country later.
- FRANCHISE/TENANTSCOPE CONDITIONAL GO APPROVED. D-076 (TenantScope activation model + bypass
  hierarchy), D-077 (default-tenant + reversible backfill, config-only), D-078 (reference-data
  tenancy policy — global vs tenant-owned, no hybrid), D-079 (Franchise Admin role + core_tenants
  ext parent_tenant_id/country/residency/owner), TENANT_MANAGEMENT audit category (all tenant
  mutations HIGH). Wave FT-1 authorized.
- WAVE FT-1 TENANTSCOPE ACTIVATED (additive; NO redesign; NO access-family modified). Phase 1:
  TenantContext (LAZY resolution — works regardless of middleware order; runAsSuperTenant explicit
  HQ bypass), TenantScope global scope (FAIL-CLOSED: enabled+unresolved+not-super → whereRaw 1=0;
  bypass console/disabled/super-tenant), BelongsToTenant trait + acrossTenants(), TenantResolver +
  ResolveTenant middleware (optional, NOT global-registered), TenancyServiceProvider (CENTRAL
  REGISTRY of 33 finding-F parent models → addGlobalScope(TenantScope) + tenant-stamp; NO per-model
  edits), config ics.tenancy (enabled/default_tenant_id/resolver), Core\Tenant extended.
  DELIBERATELY NOT auto-scoped: core_users (auth runs pre-resolution → would fail-close login),
  core_audit_logs (forensic append-only), core_tenants (IS the tenant) — enforced explicitly.
  Composes ABOVE AccountScope (tenant>account>user D-050#4). Phase 2: migration extend core_tenants
  (D-079 cols + seed root tenant) + backfill tenant_id=default on ~34 tables (ADDITIVE+REVERSIBLE
  D-077; down() nulls only default backfill; root tenant kept) + guarded tenant indexes. Tenant
  admin (D-079/req6): Roles::FRANCHISE_ADMIN (level 80, MFA), TenantService (create/suspend/activate/
  transferOwnership/elevateAdmin/changeResidency)→TenantLifecycleChanged→TENANT_MANAGEMENT audit ALL
  HIGH; Admin\TenantAdminController (HQ super/platform admin, mfa.admin); routes/tenant.php registered.
  Phase 3: analytics inherit per-tenant scope via scoped models; HQ roll-up via runAsSuperTenant.
  Phase 4: tests/Feature/Tenancy/CrossTenantIsolationTest (isolation/fail-closed/super-tenant/disabled-
  noop) = GREEN-CI RELEASE GATE. D-078 reference tables (partner_tiers/training_course_categories/
  marketplace_categories/community_skills) default GLOBAL (not in registry); franchise.* RBAC seed +
  Franchise Admin permission map = seeder follow-up. Blueprint reconciled. Review:
  TENANTSCOPE_IMPLEMENTATION_REVIEW.md (SOUND; 7 mandatory validations pass).
- WAVE FT-1 APPROVED WITH CONTROLLED ENABLEMENT. D-076/D-077/D-078/D-079 validated; deliberate
  exclusions (core_users/core_audit_logs/core_tenants) accepted. 7-stage production enablement
  sequence (false → backfill/verify → tests GREEN → pilot tenant → enable pilot only → observe → GA).
  OPEN follow-ups before production: D-078-A (Reference-Data Classification Matrix — every shared
  reference table GLOBAL or TENANT_OWNED, none unresolved), D-078-B (Tenant Analytics Dimension
  Verification — executive/tenant rollups + warehouse aggregation consistent totals). Membership may
  proceed (architecture review); Wave 5D BLOCKED by D-075.
- MEMBERSHIP_SYSTEM_ARCHITECTURE_REVIEW.md generated (DESIGN — not implemented). KEY: Membership is
  NOT a new module — it CONSUMES the EXISTING Billing substrate already in the blueprint: billing_plans
  (module='membership', knowledge_tier_grant, research_tier_grant) + billing_subscriptions. The
  ContentAccessService tier-elevation hook is PRE-MODELED (the *_tier_grant columns). Membership =
  active billing_subscription to a module='membership' plan; an active sub ELEVATES the user's content
  tier via a thin MembershipTierResolver that ContentAccessService consults (effectiveTier =
  max(roleTier, membershipTier)). MANDATORY MECHANISM TEST: reuses CONTENT-TIERING family
  (ContentAccessService) — NO new access mechanism; NO new core schema. The ONE controlled extension =
  ContentAccessService strategies consult the resolver (ELEVATE-ONLY, live-status) — the pre-designed
  hook; decision point C-1. Findings: C-1 (CRITICAL) elevate-only/live-status/regression-proof
  extension of the proven mechanism; C-2 (HIGH) maps to CONTENT tiers ONLY (knowledge_tier_grant→
  Lateral D-036, research_tier_grant→Hierarchical D-034) — never lateral org/CRM/portal; C-3 (HIGH)
  IMMEDIATE revocation on cancel/expire/refund/past_due (entitlement = live sub status, no stale);
  C-4 (HIGH) billing models join TenantScope registry (per-tenant plans); D-1 (HIGH dependency)
  Billing subscription substrate must EXIST first (plans/subscriptions/webhook status — payment
  execution may be sandboxed). Audit: propose MEMBERSHIP_MANAGEMENT (refund-revocation HIGH).
  Analytics per-tenant (MRR/churn/tier dist). Validations PASS: D-019/D-025/D-029/D-031/D-034/D-036/
  D-037/D-038/D-051/D-076. Verdict SOUND WITH CONDITIONS. Proposed: D-080 (Membership=Billing module
  + ContentAccessService elevate-only hook), D-081 (MEMBERSHIP_MANAGEMENT + immediate revocation),
  D-082 (tier mapping content-only + per-tenant + TenantScope registry), D-083 (Billing substrate
  dependency).
- MEMBERSHIP APPROVED WITH CONDITIONS. D-080 (Membership=plan+subscription→content-tier entitlement;
  no separate permission engine/family), D-081 (MEMBERSHIP_MANAGEMENT audit; manual grant/removal/
  override/tenant-policy HIGH), D-082 (elevates ONLY Knowledge+Research content; NOT CRM/portal/
  account/tenant ownership/community-moderation/marketplace-moderation/admin), D-083 (Billing
  dependency required first). Guardrails C-1 (ContentAccessService elevate-only/non-destructive/
  regression-tested; role baseline authoritative), C-2 (content tiers only), C-3 (live status,
  immediate revocation on cancel/expire/refund/charge-failure/admin-term; no cached grants), C-4
  (billing plans+subs in TenantScope).
- BILLING_SUBSCRIPTION_ARCHITECTURE_REVIEW.md generated (DESIGN — not implemented). KEY: the entire
  Billing schema is ALREADY pre-modeled in the blueprint (billing_plans w/ knowledge_tier_grant/
  research_tier_grant + gateway_plan_id; billing_subscriptions w/ status trial/active/past_due/
  cancelled/expired + gateway_subscription_id; billing_invoices/items/sequences INV-YYYY-NNNNNN;
  billing_payments w/ gateway_transaction_id UNIQUE = idempotency; billing_webhooks w/ gateway_event_id
  idempotency + signature_valid + processed). MIN SUBSTRATE for Membership (D-083) = billing_plans +
  billing_subscriptions + billing_webhooks (state machine) + invoices/payments. Webhook-driven,
  signature-verified-first, idempotent (gateway_event_id + processed → duplicate no-op). Subscription
  state machine: trial→active→(past_due→)cancelled/expired; entitlement LIVE for {trial,active} ONLY →
  refund/cancel/expire/fail = IMMEDIATE removal (C-3, no cache). MembershipTierResolver reads active
  subs → tier grants → ContentAccessService elevate-only (C-1). Payment execution sandboxable (Paystack
  TEST MODE; D-083 lifecycle required, execution deferrable). Mandatory tests A-E PASS: A D-031
  authoritative, B immediate revocation, C TenantScope (add billing models to registry; webhooks
  reconcile to sub's tenant; sequences per tenant+year), D ContentAccessService integration, E webhook
  idempotency. NO schema change. Verdict SOUND. Proposed: D-084 (billing substrate = plans+subs+webhooks
  +invoices/payments, webhook-driven/signed/idempotent), D-085 (BILLING_MANAGEMENT audit; refund/override
  HIGH), D-086 (billing models join TenantScope; webhook tenant reconciliation; sequences per tenant+year).
  Sequencing: Billing substrate → Membership entitlement.
- BILLING SUBSTRATE APPROVED. D-084 (webhook-driven/signed/idempotent/lifecycle; gateway authoritative
  only after verify+reconcile; immediate revocation; reconciliation never grants), D-085
  (BILLING_MANAGEMENT audit; override/refund/chargeback/admin-cancel/reactivate/invoice-adjust/recon-
  override HIGH), D-086 (billing in TenantScope; INV-{TENANT}-{YYYY}-{NNNNNN}; webhook tenant
  reconciliation). WAVE BILLING authorized (Membership = separate gate, do NOT implement).
- WAVE BILLING IMPLEMENTED (substrate only; Membership NOT implemented). Migrations: billing_plans
  (knowledge_tier_grant/research_tier_grant hook), billing_subscriptions, billing_invoices/items/
  sequences, billing_payments (gateway_transaction_id UNIQUE), billing_webhooks ((gateway,event_id)
  UNIQUE + signature_valid + processed). Models Billing\* — plans/subs/invoices/payments use
  BelongsToTenant (D-086, join TenantScope). BillingSubscription.isEntitling() = status ∈
  {trial,active} + period not lapsed (LIVE, no stored/cached grant → immediate revocation C-3).
  Gateway: PaymentGateway contract + PaystackGateway (HMAC-SHA512 verify; sandbox init) bound in
  AppServiceProvider (config-only D-037). Services: InvoiceNumberAllocator (per tenant+year, row-lock),
  SubscriptionService (state machine startTrialOrActive/activate/markPastDue/expire/cancel/reactivate/
  override; admin actions HIGH), PaymentService (record idempotent firstOrCreate; refund→cancel+removal),
  WebhookProcessor (verify→idempotency→DB txn→tenant resolve via acrossTenants→apply→processed; replay-
  safe; duplicate=no-op), ReconciliationService (expireLapsed DOWNGRADE-ONLY, never grants). HOOK:
  Billing\MembershipTierResolver (READ-ONLY; reads active membership subs→tier grants; ContentAccessService
  NOT modified). Events: SubscriptionStateChanged→BILLING_MANAGEMENT audit (high flag for override/refund/
  chargeback/admin). Controllers: Plan(public), Subscription(subscribe[paid-no-trial→past_due non-entitling
  until charge.success; free→active; trial→trial]/mine/cancel), Webhook(PUBLIC signature-verified),
  Admin\BillingAdmin(override/reactivate/adminCancel/refund HQ HIGH). routes/billing.php + console
  billing:reconcile hourly registered. paid-no-trial reuses past_due as pre-payment non-entitling state
  (documented; fails safe). VERIFICATION A-G authored: tests/Feature/Billing/BillingSubstrateTest
  (webhook idempotency/signature/immediate-revocation/tenant-isolation/invoice-uniqueness/duplicate-
  payment/membership-hook). Deliverables: BILLING_IMPLEMENTATION_REVIEW.md (SOUND), BILLING_TEST_SPEC.md,
  BILLING_STATE_MACHINE_VALIDATION.md. Blueprint reconciled.
- WAVE BILLING IMPLEMENTATION REVIEW — ACCEPTED (2026-06-05). Billing substrate COMPLETE AND ACCEPTED.
  Verification A-G stand as MANDATORY release criteria (production billing conditional on GREEN CI).
  Membership gate OPENED (D-087 — authorized to begin).
- WAVE MEMBERSHIP IMPLEMENTED (D-087). Membership = CONSUMER of Billing — NO new module, NO new access
  family, NO new schema (typed use of module='membership' plans). HOOK ACTIVATED: ContentAccessService
  now injects MembershipTierResolver and computes membershipTierFor() (Knowledge/Research ONLY, guests/
  CMS → 0, clamped to ics.membership.max_grant_tier default 3) → passed to strategy. AccessStrategyContract
  gained `int $membershipTier = 0` (default preserves pre-Membership behaviour — regression-safe, C-1).
  HierarchicalAccessStrategy (Research) = max(roleTier, membershipTier) >= tier (genuine elevation, stacked).
  LateralAccessStrategy (Knowledge) = membership confers MEMBER dimension (tier 2) ONLY; org tiers 3
  (CLIENT)/4 (PARTNER)/5 remain role-only (C-2 by construction). ContentAccessService PUBLIC API
  unchanged (canAccess 2-arg) → zero caller impact; only callers are content controllers via DI.
  Services\Membership\MembershipService = entitlement PROJECTION (activeMembershipsFor/isMember/
  entitlementFor live-derived; grantManual/revokeManual = manual admin entitlement, fire
  MembershipEntitlementChanged HIGH). MembershipAnalyticsService = per-tenant active/trialing/MRR/tier-
  distribution/churn (financial aggregates only, no PII). Audit: AuditCategory::MEMBERSHIP_MANAGEMENT;
  Events\Membership\MembershipEntitlementChanged + AuditEventSubscriber.handleMembershipEntitlementChanged
  (manual grant/removal HIGH); plan tier-grant policy changes HIGH-logged inline in admin controller.
  Controllers: Membership\MembershipController (status projection + plan catalogue), Membership\Admin\
  MembershipAdminController (storePlan/updatePlan module forced 'membership' + tier grants validated ≤
  max_grant_tier; grant/revoke; analytics; gated Super/Platform/FRANCHISE admin — tenant-aware C-4).
  Subscribe/cancel REUSE Billing endpoints (no parallel payment path). routes/membership.php registered
  in bootstrap/app.php; config ics.membership.max_grant_tier added. Entitlement = LIVE isEntitling()
  (no cached grant → immediate revocation C-3). Resolver consumed ONLY in content eval → structurally
  cannot grant CRM/portal/marketplace-mod/startup-gov/account/tenant/admin (D-082). VALIDATIONS 1-8
  authored: tests/Feature/Membership/MembershipEntitlementTest (activation/revocation/knowledge-elev/
  research-elev/no-portal-esc/no-crm-esc/tenant-scope/billing-integrity). Deliverables:
  MEMBERSHIP_IMPLEMENTATION_REVIEW.md (SOUND), MEMBERSHIP_TEST_SPEC.md, MEMBERSHIP_ENTITLEMENT_VALIDATION.md.
  Blueprint MODULE 13 reconciled. Self-flag M-DN-1: genuine premium elevation demonstrable in Research
  (stacked); Knowledge confers member dimension only (org tiers reserved) — faithful to D-082.
- WAVE MEMBERSHIP IMPLEMENTATION REVIEW — ACCEPTED (2026-06-05). Membership COMPLETE AND ACCEPTED.
  M-DN-1 accepted. Validations 1-8 = formal release criteria; Membership production CONDITIONAL on GREEN CI.
  ALL 15 platform modules now implementation-complete (Core/CMS/CRM/Client+Partner Portals/Knowledge/
  Research/Training/Community/Marketplace/Startup/Incubator/Accelerator/Billing/Membership).
- DIRECTIVE (2026-06-05): NO new module development before PLATFORM_READINESS_REVIEW completes. Remaining
  major streams: (1) TenantScope production enablement, (2) Investment Network post-D-075, (3) production
  bootstrap verification, (4) GREEN CI verification, (5) production readiness certification.
- HOLDING after WAVE MEMBERSHIP: produced PLATFORM_READINESS_REVIEW.md. Central finding — the repo is a
  complete reviewed OVERLAY that has NEVER been executed here (no vendor/, no PHP runtime): all quality
  gates are AUTHORED + wired (CI: composer/audit/driver-gate/Pint/Larastan/PHPUnit/gitleaks/MySQL engine-
  parity; 80 migrations; 10 test suites incl. Billing A-G + Membership 1-8) but ZERO confirmed GREEN runs.
  Production blocked by D-049 items 1-4 (bootstrap, DB migrate/seed, conformance, CI). Wave 5D BLOCKED by
  D-075. TenantScope production gated by D-078-A/B + GREEN isolation tests. Still gated: D-049.
- BOOTSTRAP & GREEN-CI VERIFICATION EXECUTED (2026-06-05) — actual commands. VERDICT: ⛔ NO GO. This box
  HAS PHP 8.2.12 (XAMPP) + Composer 2.9.5 + git + MariaDB 10.4.32, but NO node/npm, NO mysql8, NO ext-intl.
  composer validate PASSED; `composer install` FAILED (exit 2): (a) PHP 8.2.12 < required ^8.3, (b)
  laravel/framework ^11.0 blocked by security advisories, (c) no composer.lock. SKELETON MISSING — artisan,
  public/, public/index.php absent (overlay = app/config/database/routes/tests/bootstrap only; NOT a runnable
  Laravel root). Therefore DB/conformance/Billing-A-G/Membership-1-8/isolation/Pint/Larastan/PHPUnit/gitleaks/
  engine-parity = NOT EXECUTED; Hostinger spike = NOT EXECUTED (no host access). NO test passed. R-012/R-013
  CONFIRMED OPEN. NEW: dependency-security — raise framework floor to patched 11.x + commit lock before
  composer audit GREEN. Reports: BOOTSTRAP_EXECUTION_REPORT.md, CI_VERIFICATION_REPORT.md,
  HOSTINGER_CAPABILITY_RESULTS.md, PRODUCTION_READINESS_CERTIFICATION.md. Path to GO: PHP8.3+intl+MySQL8+Node;
  generate skeleton+merge overlay; framework floor+lock; install/build/migrate/seed/test GREEN on CI; Hostinger
  spike; sign go-live. STOP — no new module/architecture/Investment work (directive).
- ENVIRONMENT REMEDIATION & BOOTSTRAP RECOVERY ANALYSIS (2026-06-05, planning only). Deliverables:
  ENVIRONMENT_REMEDIATION_PLAN.md (runtime reqs MANDATORY/RECOMMENDED/OPTIONAL — only intl missing among
  mandatory exts), BOOTSTRAP_RECOVERY_PLAN.md (ADDITIVE skeleton-into-overlay: create-project Laravel 11 →
  copy ONLY missing files, overlay never overwritten; dependency resolution on 8.3 → audit triage → raise
  framework floor or documented ignore → commit composer.lock), GREEN_CI_EXECUTION_PLAN.md (expected failure
  order: audit→driver→Pint→Larastan[most iterations]→PHPUnit[first run]→engine-parity), BLOCKER_RESOLUTION_
  MATRIX.md (12 blockers w/ root cause/impact/resolution/verify/pass/risk). Classification: 9 RESOLVABLE NOW,
  B9 REQUIRES HOSTINGER, B6 REQUIRES DECISION(+host), B2 REQUIRES DECISION(audit policy). NEW: B11 missing
  database/factories (tests use User::factory() → author UserFactory for core_users), B12 missing standard
  config set (database/app/filesystems/logging/services + sanctum/permission publish). KEY: ci.yml has NO npm
  step → Node not on first-GREEN path; GitHub Actions runner already gives PHP8.3+intl+MySQL8 (removes
  B1/B4/B6 on CI) → fastest first-GREEN is RESOLVABLE NOW, no external dependency. Production: NO GO →
  CONDITIONAL GO after GREEN CI + Hostinger spike. STOP after analysis.
- BOOTSTRAP RECOVERY EXECUTED (2026-06-05) — app is now RUNNABLE; vendor/ + composer.lock exist. Env here:
  PHP 8.2.12 (XAMPP, NOT 8.3), Composer 2.9.5, MariaDB 10.4 (NOT MySQL8), no node/npm, no ext-intl. Ran via
  --ignore-platform-req=php (L11 supports 8.2); CI must run on 8.3. Phase1: Laravel v11.6.1 skeleton additive
  merge (overlay preserved) + recreated missing app/Http/Controllers/Controller.php (AuthorizesRequests+
  ValidatesRequests) + renamed ParticipationController private authorize()→authorizeManagement() (trait
  collision). Phase2: B2 root cause = Composer 2.9.5 audit.block-insecure default ON; set false (TEMP) →
  install OK; ONLY advisory = CVE-2026-48019 (Laravel CRLF default email rule) affecting ALL 11.x, fix only
  in L12.60+/13.10+ → REQUIRES DECISION (accept+ignore vs major upgrade; NOT done). Phase3: UserFactory→
  core_users + User HasFactory (B11); config/{app,database,filesystems,logging,services} + sanctum/permission
  published (B12). Phase4: artisan boots L11.54.0, 266 routes, ALL 80 migrations apply on sqlite (exit 0).
  Phase5 CI: validate✓ audit=1adv driver✓ Pint✗(~36 cosmetic) Larastan✗(113 property.notFound) PHPUnit
  47pass/5fail engine-parity NOT-EXECUTED(no MySQL8). Bugs fixed (surfaced by run): SubscriptionService
  null-actor optional()->first() → $actor?-> (Billing a/c/g GREEN); RBAC stale 13→count(Roles::ALL);
  Membership-7 FK test setup. MEMBERSHIP 1-8 ALL GREEN; BILLING A/B/C/E/F/G GREEN. 5 remaining failures =
  ONE architectural cause: TenantScope::apply() bypasses on runningInConsole() → isolation never engages
  under PHPUnit + NOT in queue/scheduled console (async cross-tenant exposure risk). FLAGGED not fixed.
  Reports: BOOTSTRAP_MERGE/DEPENDENCY_RECOVERY/CONFIGURATION_RECOVERY/APPLICATION_BOOT/FIRST_GREEN_CI_ATTEMPT.
  TWO DECISIONS RAISED: TenantScope console-bypass (D-088 cand.); CVE-2026-48019 accept vs L12 upgrade (D-089
  cand.). Production NO GO; CONDITIONAL GO to remediation. STOP after first CI attempt (per directive).
- POST-BOOTSTRAP SECURITY & GREEN-CI REMEDIATION REVIEW (2026-06-05, analysis only). Deliverables:
  TENANTSCOPE_ASYNC_SECURITY_REVIEW.md, LARAVEL_SECURITY_ADVISORY_REVIEW.md, FINAL_GREEN_CI_EXECUTION_PLAN.md.
  D-088 cand (TenantScope async): RECOMMEND OPTION B = context-aware tenancy (drop runningInConsole blanket
  bypass; maintenance/super ctx for migrate/seed; queue middleware carries+restores tenant_id; fail-closed in
  async; explicit acrossTenants for cross-tenant jobs — reconciliation already complies). Why bypass exists:
  console has no request → resolver null → would fail-closed 1=0 → break migrate/seed/queue. Risk today LOW
  (only explicit downgrade-only cross-tenant console jobs) but MEDIUM→HIGH latent footgun; no DB migration.
  D-089 cand (CVE-2026-48019 Laravel CRLF default email rule, ALL 11.x, fix only L12.60+/13.10+): RECOMMEND
  OPTION A now (audit.ignore + re-enable block-insecure + email hardening; residual LOW — Symfony Mailer
  header-safe, no raw-header sink) + OPTION B (L12 upgrade) MANDATORY before prod. Effort to GREEN CI ~2-3d;
  to prod-ready ~1-2wk. Platform CONDITIONAL GO (remediation); production NO GO. STOP after review (directive).
- D-088/D-089 IMPLEMENTED + GREEN-CI EXECUTED (2026-06-05). D-088 (context-aware tenancy) APPROVED+DONE:
  removed runningInConsole() bypass from TenantScope + AccountScope (null-actor guard preserves system ctx);
  added TenantContext::runForTenant() + TenancyQueueMiddleware + TenantAware trait. Fail-closed now in async;
  explicit acrossTenants/runAsSuperTenant unchanged; migrate/seed run tenancy-disabled. D-089 (CVE acceptance)
  APPROVED+DONE OPTION A: block-insecure re-enabled + audit.ignore[PKSA-mdq4-51ck-6kdq/GHSA-5vg9-5847-vvmq/
  CVE-2026-48019]; verified NO raw email-header sink + Symfony Mailer header-safe → residual LOW; SEC-EXC-001
  registered (exit=Laravel12.60+ before prod, NOT done). GREEN-CI: validate✓ audit✓(1 ignored) driver✓
  Pint✓(36 files) Larastan✓(114 baselined→phpstan-baseline.neon) PHPUnit 57/0 on sqlite AND MariaDB 10.4
  (real-server parity; FK enforcement surfaced+fixed AssertsOrgIsolation fixture gap that sqlite hid). MySQL 8
  authoritative run + GitHub Actions + gitleaks + PHP 8.3 = CARRIED to runner (no git repo / no MySQL8 here;
  not defects). 11 deliverable reports written. Platform CONDITIONAL GO (GREEN-CI baseline achieved);
  production gated by Laravel 12 upgrade + Hostinger spike + MySQL8 CI + D-078-A/B. Non-blocking backlog: no
  dedicated CRM/Portal test files; add FULLTEXT search test on MySQL8. NOTE: started XAMPP MariaDB (port 3306)
  for parity; composer.json now has audit block + vendor/ + composer.lock present.

Phase 0 — Architecture Complete + Independently Reviewed (ARCHITECTURE_REVIEW_REPORT.md)

## Deployment Doctrine (D-037)

VPS-Ready Architecture, Shared-Hosting-First Deployment.
Same code + same schema in both; environment chosen by .env only.
Migration to VPS = configuration changes only (no DB/app/code redesign).
Architecture intact: Data Warehouse, i18n, tenant-ready all BUILT in Phase 1,
runtime-gated by env feature flags on shared hosting.
See VPS_MIGRATION_CHECKLIST.md.

---

## Organizational Identity

ICS is a Technology, Consulting, Capacity Development, and Innovation Organization.
ICS is NOT a web agency.

Strategic Mission:
Become Africa's leading digital transformation, technology consulting, innovation,
and capacity development ecosystem.

Competitive Set:
Consulting firms, technology integrators, digital transformation firms,
professional training institutes, innovation hubs.

Primary Audience (Priority Order):
1. Government Agencies
2. International Organizations
3. Corporate Enterprises
4. NGOs
5. SMEs
6. Startups
7. Individuals

---

## Approved Platform Modules — Phase 1

1.  Corporate Website (public-facing, government-credibility standard)
2.  CRM (Internal Enterprise — ICS staff only)
3.  Client Portal (authenticated client view)
4.  Startup Hub (startup lifecycle management)
5.  Partner Portal (approved partners)
6.  Training Institute (LMS, professional certifications)
7.  Opportunity Marketplace (grants, tenders, jobs, internships, etc.)
8.  Knowledge Center (D-033 + D-036 — fully approved)
    Purpose: Primary public learning, resource, and authority platform
    Categories: 15 (Digital Transformation, AI, Cybersecurity, Data Analytics,
    Gov Tech, Business Growth, Entrepreneurship, Startups, Capacity Dev,
    Project Mgmt, Digital Marketing, Cloud, Innovation, Research Methods, M&E)
    Content Types: Articles, News, Guides, White Papers, Templates, Toolkits,
    SOPs, Checklists, Case Studies, Training Resources, Video Content, Downloads,
    Resource Collections, Client Documentation, Internal Knowledge Base
    Features: Full Search, AI Search, Ratings, Bookmarks, Downloads,
    Related Content Engine, Content Analytics
    Access Model: 5-Tier with Lateral Tiers 3+4 (D-036)
      Tier 1 Public: Articles, News, Public Guides, Case Studies, Basic Templates
      Tier 2 Members: Premium Guides, Toolkits, Download Libraries, Training Resources
      Tier 3 Clients: Client Libraries, Project Resources, Client Documentation
      Tier 4 Partners: Partner Resources, Joint Publications, Partner Toolkits
      Tier 5 Internal: Drafts, SOPs, Internal KB, Operational Docs
    Future Monetization: Membership, Premium Content, Resource Subscriptions,
    Enterprise Packages (reserved — no redesign required)
9.  Research Center (D-030 + D-034 — fully approved)
    Purpose: Thought leadership — Industry Reports, White Papers, Research Publications,
    Technology Trends, Digital Economy, Government Tech, AI Adoption, Capacity Dev Reports
    Features: Downloadable Reports, Citation Support, Research Library, Author Profiles,
    Research Categories, Research Analytics
    Access Model: 5-Tier (D-034)
      Tier 1 Public: Summaries, Executive Briefs, Public Reports, Industry Insights
      Tier 2 Members: Full Reports, Templates, Resource Libraries, Archives
      Tier 3 Partners: Partner Research, Collaborative Studies, Restricted Publications
      Tier 4 ICS Internal: Drafts, Working Papers, Internal Reports, Pipelines
      Tier 5 Super Admin: Full access
    Future Monetization: Premium Reports, Subscription Library, Corporate Membership
      (reserved in architecture — no redesign required)
10. AI Services (Gemini AI — architecture D-026, use cases D-029 — 10 approved)
    Phase 1: Website Assistant, Lead Qualification, Proposal Generation,
    Training Recommendations, Knowledge Search, Research Assistant,
    Opportunity Matching, Startup Readiness Assessment,
    Digital Maturity Assessment, Content Drafting
    Future: Business Advisory Assistant, Executive Dashboard Insights
11. Analytics Layer — Two-Tier (D-025 + D-032)
    Tier 1: analytics_ tables — module-level aggregations, cron-updated
    Tier 2: dw_ tables — star schema Data Warehouse, nightly ETL
    Sources: CRM, Projects, Training, Research, Startups, Partners, Marketplace, Finance
    Future: Metabase / Power BI / BigQuery BI integration via star schema
12. Subscription Module (Phase 2 — D-031 approved)
13. Community Module (D-035 — new scope addition)
    Profile Types: Founder, Startup, Consultant, Trainer, Partner, Researcher
    Pattern: Class Table Inheritance (base + type extension tables)
    Future: Discussion Forums, Mentorship Matching, Event Registration,
    Collaboration Requests, Opportunity Sharing (architecture reserved)
    Gateway: Paystack (primary) → Flutterwave / Stripe (future)
    Capabilities: Course Payments, Membership, Events, Subscriptions,
    Marketplace Fees, Consulting Deposits, Proposal Acceptance Payments
    Architectures: Subscription, Invoice, Revenue Reporting (Blueprint §18)

## Future Modules — Reserved Architecture (D-019)

13. LMS (standalone or Training Institute extension)
14. Vendor Marketplace
15. Membership System (extends Subscription Module)
16. Incubator Program (extends Startup Hub)
17. Accelerator Program (extends Startup Hub)
18. Investment Network (investor-startup — legal review required before scoping)
19. Franchise Operations (requires tenant-aware schema from day one)

---

## Finalized Technology Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 |
| Language | PHP 8.3 |
| Database | MySQL 8+ |
| Templating | Laravel Blade |
| CSS | Tailwind CSS |
| JavaScript | Alpine.js + Vanilla JS |
| API Auth | Laravel Sanctum |
| RBAC | Spatie Laravel-Permission |
| Storage | Laravel Flysystem (local Phase 1, S3 Phase 3) |
| Notifications | Laravel Notifications (Mail + WhatsApp + In-App) |
| Email Provider | Brevo SMTP/API |
| Messaging | WhatsApp Business API |
| AI | Google Gemini API |
| Analytics UI | Chart.js |
| Mobile | Progressive Web App (PWA) |
| Hosting | Hostinger Shared (P1) → VPS (P2) → Cloud (P3) |
| Deployment | Git-based, Staging + Production |

---

## Approved Architectural Patterns

- Modular Monolith → Microservices upgrade path
- API-First (/api/v1/)
- Event-Driven (Laravel Events — sync P1, async P2)
- RBAC + Policy (Spatie + Laravel Gates)
- Tenant-Aware Schema (tenant_id on all core tables)
- i18n-First (all strings through translation layer)
- Queue-First (all background work via job queue)

---

## Compliance Requirements

- Nigeria Data Protection Act (NDPA)
- GDPR Ready
- ISO 27001 (Basic Alignment)
- OWASP Security Principles
- WCAG 2.1 Level AA — Approved (D-028) — Mandatory across all frontend modules

---

## Pending Decisions

| ID | Subject | Priority |
|---|---|---|
| — | All open decisions resolved | — |

---

## Active Risks

### R-001 — MITIGATED (D-037)
Shared hosting has no persistent background processes.
Affects: Email queuing, WhatsApp notifications, async processing.
Mitigation: Config-driven runtime (D-037) — DB/cron queue on shared, Redis+Horizon
on VPS via .env flip only. Heavy listeners implement ShouldQueue. Auth-critical mail
sent synchronously (D-039 SPOF-04). Resolved fully on VPS migration.

### R-002 — MEDIUM
No Redis on shared hosting (Phase 1).
Affects: Session management, application-level caching.
Mitigation: MySQL session and cache tables. APCu if host permits.

### R-003 — MEDIUM
Knowledge Center, Research Center, AI use cases still unscoped.
Cannot model or build without definition.
Mitigation: OD-CONTENT-001, OD-CONTENT-002, OD-AI-001 must be resolved
before those modules enter development.

### R-004 — MEDIUM
Subscription Module required (D-008) but billing provider unselected.
Affects: Revenue flow and Subscription Module development.
Mitigation: OD-BILLING-001 must be resolved before Phase 2 module build begins.

### R-005 — LOW
Arabic (Phase 3) requires RTL layout support. Must be planned in CSS now.
Mitigation: Tailwind RTL plugin configured from initial build. Logical CSS
properties (ms/me, ps/pe) used throughout. No physical left/right hardcoding.

### R-006 — ACCEPTED + SCHEDULED (D-037)
99.9% SLO (D-009) cannot be guaranteed on Phase 1 shared hosting.
Mitigation: Phase 1 is a documented pre-SLO / best-effort period. The 99.9% SLO is
a VPS-tier capability and a defined VPS migration trigger (VPS_MIGRATION_CHECKLIST
Part D). Do not promise 99.9% to government clients while on shared hosting.

### R-010 — HIGH (NEW — Architecture Review)
Confidential CRM/PII/contract/payment data on shared hosting has weak process
isolation (SEC-01). Audit-log immutability may be unenforceable if host denies
MySQL TRIGGER (SEC-03). PII sent to Gemini may breach EU residency (SEC-04).
Mitigation: D-039 hardening baseline — .env off web root, app-layer audit
immutability + off-box export, Gemini DPA + PII redaction, Cloudflare WAF.
Host capability spike (VPS_MIGRATION_CHECKLIST Part A.4) runs before build.
Strongest residual risk is shared-tenancy isolation — a VPS migration trigger.

### R-011 — MEDIUM (NEW — Architecture Review)
Public/guest AI Website Assistant is an uncapped billable endpoint (COST-01),
vulnerable to bot-driven token spend and prompt injection (SEC-05).
Mitigation: hard per-IP/per-session caps, global daily kill-switch, challenge/auth
before use, prompt-injection hardening in BaseAIService. ICS_AI_HIGH_VOLUME=false
on shared hosting.

### R-007 — RESOLVED
WCAG 2.1 AA approved as D-028. Mandatory across all frontend modules.
No longer a risk — now a governed standard.

### R-008 — HIGH
Franchise Operations (D-019) requires tenant-aware database schema.
Mitigation: tenant_id column on all core tables from first migration, per D-021 rules.

### R-009 — MEDIUM
Investment Network may require financial/securities regulatory compliance.
Mitigation: Legal review required before Investment Network is scoped or built.

---

## Architecture Documents

- PROJECT_CONSTITUTION.md — Governing document (Parts I–VII, principles P-1…P-10)
- DECISION_LOG.md — All decisions D-001 through D-040
- ENTERPRISE_ARCHITECTURE_BLUEPRINT.md — Full technical blueprint (20 sections)
- BUSINESS_CAPABILITY_MAP.md — 112 capabilities, 13 domains
- USER_ROLE_MATRIX.md — 14 roles, full attributes
- PERMISSION_MATRIX.md — every permission × role × module
- EVENT_CATALOG.md — 62 events, 124 listeners (satisfies D-027)
- MODULE_DEPENDENCY_DIAGRAM.md — dependency levels + build order
- DATA_FLOW_DIAGRAM.md — 11 flows, external systems, PII map
- DATABASE_BLUEPRINT.md — ~119 tables, full DDL
- ARCHITECTURE_REVIEW_REPORT.md — independent critical review (33 findings)
- VPS_MIGRATION_CHECKLIST.md — deployment strategy + config-only migration (D-037)
- PROJECT_MEMORY.md — This file

---

## Resolved Items

- Platform pattern: Modular Monolith → Microservices upgrade path
- Multi-tenancy: Single platform, tenant-aware schema, not SaaS (yet)
- CRM: Internal only
- Marketplace: ICS/Partners/Orgs post; Submission → Review → Approval → Publication
- Analytics: Centralized cross-module, executive dashboard
- Framework: Laravel 11 + PHP 8.3
- Auth: RBAC via Spatie + Sanctum
- Notifications: Mail + WhatsApp + In-App via Laravel Notifications
- API: /api/v1/ versioned, API Resources, Sanctum auth
- Storage: Hostinger Phase 1, Flysystem S3 swap in Phase 3
- Events: Laravel Events/Listeners (event catalog required)
- AI: Gemini architecture approved, use cases TBD
- i18n: EN (P1) → FR (P2) → AR/RTL (P3), translation layer from day one
- Accessibility: WCAG 2.1 AA mandatory across all frontend modules (D-028)
