# DATABASE BLUEPRINT
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: Awaiting Approval
Author: Chief Enterprise Architect

Decision References: D-002 (MySQL 8+), D-004 (Tenant-Aware), D-014 (i18n),
D-021 (RBAC), D-024 (Storage), D-031 (Billing), D-032 (Data Warehouse),
D-033/D-034/D-036 (Content Tiers)

---

## EXECUTIVE SUMMARY

This document is the authoritative schema reference for all database tables across
the platform. Every table, column, data type, constraint, index, and foreign key
relationship is defined here. This document governs all migration files.

No migration may define a table, column, or index that contradicts this blueprint.
Any discrepancy between a migration and this document must be resolved in favor
of this document, with a formal amendment to both.

Database: MySQL 8.0+
Charset: utf8mb4
Collation: utf8mb4_unicode_ci
Engine: InnoDB (all tables)
Mode: STRICT_TRANS_TABLES

Total Tables: 119
Table Prefixes: core_, crm_, training_, marketplace_, partner_, startup_,
  client_, content_, knowledge_, research_, community_, ai_, notify_,
  billing_, analytics_, dw_, sys_, i18n_

---

## UNIVERSAL COLUMN CONVENTIONS

Every business table carries these columns:

```
id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
tenant_id    BIGINT UNSIGNED NULL DEFAULT NULL
             FK → core_tenants(id) ON DELETE SET NULL
             INDEX: idx_{table}_tenant
created_at   TIMESTAMP NULL DEFAULT NULL
updated_at   TIMESTAMP NULL DEFAULT NULL
deleted_at   TIMESTAMP NULL DEFAULT NULL   -- SoftDeletes (Eloquent)
```

Exceptions:
- Pure pivot tables: id, created_at only
- System/infrastructure tables: as defined
- Append-only log tables: no deleted_at, no updated_at

---

## MODULE 1 — CORE PLATFORM

### core_tenants

> **Wave FT-1 — TenantScope ACTIVATION (D-076/D-077/D-079):** the reserved tenant axis is activated.
> TenantScope is a NEW global scope composing ABOVE AccountScope (tenant > account > user, D-050#4),
> applied centrally via TenancyServiceProvider to the finding-F parent models (children inherit via
> parent). Config-gated (ics.tenancy.enabled); single-tenant default (root tenant) when disabled;
> FAIL-CLOSED when enabled+unresolved; EXPLICIT audited super-tenant (HQ) bypass. core_users/
> core_audit_logs/core_tenants are deliberately NOT auto-scoped (auth-path/forensic). **D-079
> extension:** core_tenants gains `parent_tenant_id` (regional hierarchy), `country_code`,
> `residency_region`, `owner_user_id`; Franchise Admin role added. Tenant lifecycle (create/suspend/
> activate/ownership-transfer/admin-elevation/residency-change) audited HIGH under TENANT_MANAGEMENT.
> Backfill assigns existing rows to the root tenant (additive + reversible, D-077).

```sql
CREATE TABLE core_tenants (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_tenant_id BIGINT UNSIGNED NULL COMMENT 'D-079 regional hierarchy',
  name             VARCHAR(255) NOT NULL,
  slug             VARCHAR(100) NOT NULL UNIQUE,
  domain           VARCHAR(255) NULL,
  status           ENUM('active','suspended','trial') NOT NULL DEFAULT 'active',
  settings         JSON NULL COMMENT 'Tenant-level config overrides',
  country_code     CHAR(2) NULL,                 -- D-079
  residency_region VARCHAR(50) NULL,             -- D-079 data residency
  owner_user_id    BIGINT UNSIGNED NULL,         -- D-079
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  deleted_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_core_tenants_slug (slug),
  KEY idx_core_tenants_status (status),
  KEY idx_core_tenants_parent (parent_tenant_id)
);
```

---

### core_users

```sql
CREATE TABLE core_users (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id           BIGINT UNSIGNED NULL,
  account_id          BIGINT UNSIGNED NULL COMMENT 'D-050/S2-2: org linkage → crm_accounts. NULL for ICS staff/Super Admin/individuals. FK added in Wave 1d (when crm_accounts exists). Basis for AccountScope + BasePolicy::sameAccount.',
  name                VARCHAR(255) NOT NULL,
  email               VARCHAR(255) NOT NULL,
  email_verified_at   TIMESTAMP NULL,
  password            VARCHAR(255) NOT NULL COMMENT 'bcrypt hash, cost 12',
  locale              VARCHAR(10) NOT NULL DEFAULT 'en',
  timezone            VARCHAR(50) NOT NULL DEFAULT 'UTC',
  last_login_at       TIMESTAMP NULL,
  last_login_ip       VARCHAR(45) NULL,
  status              ENUM('active','pending','suspended','deactivated') NOT NULL DEFAULT 'active' COMMENT 'D-047: pending = approval-gated registration (login denied until approved)',
  mfa_secret          TEXT NULL COMMENT 'TOTP secret — ENCRYPTED at rest via model encrypted cast (D-042/F-5). TEXT (not VARCHAR(64)): ciphertext exceeds 64 chars. No plaintext ever.',
  mfa_enabled         TINYINT(1) NOT NULL DEFAULT 0,
  mfa_recovery_codes  TEXT NULL COMMENT 'MFA recovery codes — JSON array of HASHED (bcrypt) codes (D-043/AF-3). No plaintext, no reversible encryption. Single-use.',
  remember_token      VARCHAR(100) NULL,
  created_at          TIMESTAMP NULL,
  updated_at          TIMESTAMP NULL,
  deleted_at          TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_core_users_email (email),
  KEY idx_core_users_tenant (tenant_id),
  KEY idx_core_users_status (status),
  CONSTRAINT fk_core_users_tenant FOREIGN KEY (tenant_id)
    REFERENCES core_tenants(id) ON DELETE SET NULL
);
```

---

### core_audit_logs

```sql
CREATE TABLE core_audit_logs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  actor_id        BIGINT UNSIGNED NULL COMMENT 'NULL = system action',
  actor_role      VARCHAR(100) NULL,
  action          VARCHAR(50) NOT NULL COMMENT 'CREATE|UPDATE|DELETE|LOGIN|etc.',
  module          VARCHAR(50) NOT NULL,
  category        VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT 'D-046: user_management|role_assignment|permission_change|escalation_request|escalation_approval|security_config|authentication|data_privacy|general',
  sensitivity     VARCHAR(10) NOT NULL DEFAULT 'normal' COMMENT 'D-046: normal|high. ALL Super Admin actions + the high-sensitivity categories = high.',
  record_type     VARCHAR(100) NULL COMMENT 'Model class',
  record_id       BIGINT UNSIGNED NULL,
  before_hash     CHAR(64) NULL COMMENT 'SHA-256 of serialised before-state',
  after_hash      CHAR(64) NULL,
  ip_address      VARCHAR(45) NULL,
  user_agent      TEXT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_tenant (tenant_id),
  KEY idx_audit_actor (actor_id),
  KEY idx_audit_module (module),
  KEY idx_audit_created (created_at)
) COMMENT 'Append-only. No UPDATE or DELETE permitted.';
```

---

### core_consent_logs

```sql
CREATE TABLE core_consent_logs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  consent_type    VARCHAR(100) NOT NULL COMMENT 'registration|marketing|data_processing',
  policy_version  VARCHAR(20) NOT NULL,
  consented_at    TIMESTAMP NOT NULL,
  withdrawn_at    TIMESTAMP NULL,
  ip_address      VARCHAR(45) NULL,
  created_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_consent_user (user_id),
  KEY idx_consent_type (consent_type),
  CONSTRAINT fk_consent_user FOREIGN KEY (user_id)
    REFERENCES core_users(id) ON DELETE CASCADE
);
```

---

### core_role_escalation_approvals  (D-045 — four-eyes Super Admin escalation)

```sql
CREATE TABLE core_role_escalation_approvals (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  requester_id    BIGINT UNSIGNED NOT NULL,           -- FK core_users (initiating Super Admin)
  target_user_id  BIGINT UNSIGNED NOT NULL,           -- FK core_users (receives the role)
  approver_id     BIGINT UNSIGNED NULL,               -- FK core_users (second Super Admin)
  requested_role  VARCHAR(255) NOT NULL,
  previous_role   VARCHAR(255) NULL,
  reason_code     VARCHAR(50) NOT NULL,
  status          VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|approved|rejected|expired
  requester_ip    VARCHAR(45) NULL,
  approver_ip     VARCHAR(45) NULL,
  decided_at      TIMESTAMP NULL,
  expires_at      TIMESTAMP NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_escalation_status (status),
  KEY idx_escalation_target (target_user_id),
  KEY idx_escalation_requester (requester_id),
  CONSTRAINT fk_escalation_requester FOREIGN KEY (requester_id) REFERENCES core_users(id),
  CONSTRAINT fk_escalation_target FOREIGN KEY (target_user_id) REFERENCES core_users(id),
  CONSTRAINT fk_escalation_approver FOREIGN KEY (approver_id) REFERENCES core_users(id)
) COMMENT 'Single-purpose four-eyes role-escalation record (D-044/D-045). Decided once; every transition mirrored to core_audit_logs (immutable trail).';
```

---

### core_retention_policies

```sql
CREATE TABLE core_retention_policies (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  module          VARCHAR(50) NOT NULL,
  record_type     VARCHAR(100) NOT NULL,
  retention_days  INT NOT NULL,
  auto_purge      TINYINT(1) NOT NULL DEFAULT 0,
  description     TEXT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_retention_module_type (module, record_type)
);
```

---

### Spatie Laravel-Permission Tables

```sql
CREATE TABLE roles (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(255) NOT NULL,
  guard_name   VARCHAR(255) NOT NULL DEFAULT 'web',
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_roles_name_guard (name, guard_name)
);

CREATE TABLE permissions (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(255) NOT NULL,
  guard_name   VARCHAR(255) NOT NULL DEFAULT 'web',
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_permissions_name_guard (name, guard_name)
);

CREATE TABLE model_has_roles (
  role_id      BIGINT UNSIGNED NOT NULL,
  model_type   VARCHAR(255) NOT NULL,
  model_id     BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, model_id, model_type),
  KEY idx_model_has_roles_model (model_id, model_type),
  CONSTRAINT fk_mhr_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE model_has_permissions (
  permission_id BIGINT UNSIGNED NOT NULL,
  model_type    VARCHAR(255) NOT NULL,
  model_id      BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (permission_id, model_id, model_type),
  KEY idx_model_has_perms_model (model_id, model_type),
  CONSTRAINT fk_mhp_perm FOREIGN KEY (permission_id)
    REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE role_has_permissions (
  permission_id BIGINT UNSIGNED NOT NULL,
  role_id       BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (permission_id, role_id),
  CONSTRAINT fk_rhp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
  CONSTRAINT fk_rhp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

---

### personal_access_tokens (Sanctum)

```sql
CREATE TABLE personal_access_tokens (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tokenable_type  VARCHAR(255) NOT NULL,
  tokenable_id    BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(255) NOT NULL,
  token           VARCHAR(64) NOT NULL UNIQUE,
  abilities       TEXT NULL,
  last_used_at    TIMESTAMP NULL,
  expires_at      TIMESTAMP NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_pat_tokenable (tokenable_type, tokenable_id)
);
```

---

### password_reset_tokens  (D-041 / F-3 — added by amendment)

```sql
CREATE TABLE password_reset_tokens (
  email       VARCHAR(255) NOT NULL,
  token       VARCHAR(255) NOT NULL COMMENT 'HASHED reset token (Laravel password broker)',
  created_at  TIMESTAMP NULL COMMENT 'expiry computed from this (auth.passwords.*.expire)',
  PRIMARY KEY (email)
);
```
Purpose: password recovery architecture. One active token per email (PK on email);
token stored hashed; expiry + throttle enforced by the Laravel password broker.
Migration authored in Task 4 (Authentication), not Task 3.

---

### notifications (Laravel default)

```sql
CREATE TABLE notifications (
  id              CHAR(36) NOT NULL,
  type            VARCHAR(255) NOT NULL,
  notifiable_type VARCHAR(255) NOT NULL,
  notifiable_id   BIGINT UNSIGNED NOT NULL,
  data            TEXT NOT NULL,
  read_at         TIMESTAMP NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_notifications_notifiable (notifiable_type, notifiable_id),
  KEY idx_notifications_read (read_at)
);
```

---

## MODULE 2 — CRM

> **D-053 / Wave 1d governance:** CRM is INTERNAL-ONLY (D-012). `crm_*.account_id` is a
> SUBJECT pointer (which account the record is *about*), NOT an ownership key — CRM tables
> do **not** use AccountScope/BelongsToAccount. Visibility is **assignment-scoped**
> (`assigned_to`/`created_by`) via the `HasAssignmentVisibility` concern + `crm.*.read.own`
> vs `crm.*.read.all` (W1d-4). Lifecycle/assignment/stage changes are audited under
> `crm_management` (D-054). `crm_notes` is NOT a table — notes are `crm_activities.type='note'`
> (W1d-2). `crm_proposals`/`crm_contracts` are deferred (W1d-6). The
> `core_users.account_id → crm_accounts(id)` FK is activated in Wave 1d (D-050 step 2,
> ON DELETE SET NULL).

### crm_accounts

```sql
CREATE TABLE crm_accounts (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  name            VARCHAR(255) NOT NULL,
  type            ENUM('client','prospect','partner','government','ngo','sme','startup') NOT NULL,
  industry        VARCHAR(100) NULL,
  website         VARCHAR(255) NULL,
  country_code    CHAR(2) NULL,
  phone           VARCHAR(50) NULL,
  address         TEXT NULL,
  status          ENUM('active','inactive','prospect') NOT NULL DEFAULT 'prospect',
  assigned_to     BIGINT UNSIGNED NULL,
  created_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_crm_accounts_tenant (tenant_id),
  KEY idx_crm_accounts_type (type),
  KEY idx_crm_accounts_status (status),
  KEY idx_crm_accounts_assigned (assigned_to),
  CONSTRAINT fk_crm_accounts_assigned FOREIGN KEY (assigned_to)
    REFERENCES core_users(id) ON DELETE SET NULL
);
```

---

### crm_contacts

```sql
CREATE TABLE crm_contacts (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  account_id      BIGINT UNSIGNED NULL,
  first_name      VARCHAR(100) NOT NULL,
  last_name       VARCHAR(100) NOT NULL,
  email           VARCHAR(255) NULL,
  phone           VARCHAR(50) NULL,
  job_title       VARCHAR(150) NULL,
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  assigned_to     BIGINT UNSIGNED NULL,
  created_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_crm_contacts_tenant (tenant_id),
  KEY idx_crm_contacts_account (account_id),
  KEY idx_crm_contacts_email (email),
  CONSTRAINT fk_crm_contacts_account FOREIGN KEY (account_id)
    REFERENCES crm_accounts(id) ON DELETE SET NULL
);
```

---

### crm_leads

```sql
CREATE TABLE crm_leads (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id               BIGINT UNSIGNED NULL,
  contact_id              BIGINT UNSIGNED NULL,
  account_id              BIGINT UNSIGNED NULL,
  source                  VARCHAR(100) NOT NULL COMMENT 'website|referral|community|manual|event',
  source_detail           VARCHAR(255) NULL,
  title                   VARCHAR(255) NOT NULL,
  value                   DECIMAL(14,2) NULL,
  currency                CHAR(3) NULL DEFAULT 'NGN',
  stage                   ENUM('new','contacted','qualified','proposal','negotiation','closed_won','closed_lost')
                          NOT NULL DEFAULT 'new',
  probability             TINYINT UNSIGNED NULL DEFAULT 20,
  expected_close_date     DATE NULL,
  assigned_to             BIGINT UNSIGNED NULL,
  ai_qualification_score  DECIMAL(5,2) NULL,
  ai_qualification_at     TIMESTAMP NULL,
  notes                   TEXT NULL,
  created_by              BIGINT UNSIGNED NULL,
  created_at              TIMESTAMP NULL,
  updated_at              TIMESTAMP NULL,
  deleted_at              TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_crm_leads_tenant (tenant_id),
  KEY idx_crm_leads_stage (stage),
  KEY idx_crm_leads_assigned (assigned_to),
  KEY idx_crm_leads_account (account_id),
  CONSTRAINT fk_crm_leads_contact FOREIGN KEY (contact_id)
    REFERENCES crm_contacts(id) ON DELETE SET NULL,
  CONSTRAINT fk_crm_leads_account FOREIGN KEY (account_id)
    REFERENCES crm_accounts(id) ON DELETE SET NULL
);
```

---

### crm_opportunities

```sql
CREATE TABLE crm_opportunities (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  account_id      BIGINT UNSIGNED NULL,
  lead_id         BIGINT UNSIGNED NULL,
  title           VARCHAR(255) NOT NULL,
  value           DECIMAL(14,2) NOT NULL DEFAULT 0,
  currency        CHAR(3) NOT NULL DEFAULT 'NGN',
  stage           ENUM('qualification','proposal','negotiation','closed_won','closed_lost')
                  NOT NULL DEFAULT 'qualification',
  close_date      DATE NULL,
  probability     TINYINT UNSIGNED NULL DEFAULT 20,
  description     TEXT NULL,
  assigned_to     BIGINT UNSIGNED NULL,
  created_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_crm_opp_tenant (tenant_id),
  KEY idx_crm_opp_account (account_id),
  KEY idx_crm_opp_stage (stage),
  CONSTRAINT fk_crm_opp_account FOREIGN KEY (account_id)
    REFERENCES crm_accounts(id) ON DELETE SET NULL,
  CONSTRAINT fk_crm_opp_lead FOREIGN KEY (lead_id)
    REFERENCES crm_leads(id) ON DELETE SET NULL
);
```

---

### crm_proposals

```sql
CREATE TABLE crm_proposals (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  opportunity_id  BIGINT UNSIGNED NULL,
  title           VARCHAR(255) NOT NULL,
  status          ENUM('draft','ai_draft','under_review','sent','accepted','rejected')
                  NOT NULL DEFAULT 'draft',
  file_path       VARCHAR(500) NULL,
  ai_request_id   BIGINT UNSIGNED NULL,
  notes           TEXT NULL,
  sent_at         TIMESTAMP NULL,
  accepted_at     TIMESTAMP NULL,
  created_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_crm_proposals_opp (opportunity_id),
  KEY idx_crm_proposals_status (status),
  CONSTRAINT fk_crm_proposals_opp FOREIGN KEY (opportunity_id)
    REFERENCES crm_opportunities(id) ON DELETE SET NULL
);
```

---

### crm_contracts

```sql
CREATE TABLE crm_contracts (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  account_id      BIGINT UNSIGNED NOT NULL,
  opportunity_id  BIGINT UNSIGNED NULL,
  title           VARCHAR(255) NOT NULL,
  value           DECIMAL(14,2) NOT NULL DEFAULT 0,
  currency        CHAR(3) NOT NULL DEFAULT 'NGN',
  start_date      DATE NULL,
  end_date        DATE NULL,
  renewal_date    DATE NULL,
  status          ENUM('draft','active','expired','terminated','renewed')
                  NOT NULL DEFAULT 'draft',
  signed_at       TIMESTAMP NULL,
  file_path       VARCHAR(500) NULL,
  deposit_paid    TINYINT(1) NOT NULL DEFAULT 0,
  created_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_crm_contracts_account (account_id),
  KEY idx_crm_contracts_renewal (renewal_date),
  KEY idx_crm_contracts_status (status),
  CONSTRAINT fk_crm_contracts_account FOREIGN KEY (account_id)
    REFERENCES crm_accounts(id)
);
```

---

### crm_activities

```sql
CREATE TABLE crm_activities (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  subject_type    VARCHAR(100) NOT NULL COMMENT 'polymorphic: Lead|Opportunity|Account|Contract',
  subject_id      BIGINT UNSIGNED NOT NULL,
  type            ENUM('call','email','meeting','note','task','demo') NOT NULL,
  title           VARCHAR(255) NOT NULL,
  description     TEXT NULL,
  due_at          TIMESTAMP NULL,
  completed_at    TIMESTAMP NULL,
  created_by      BIGINT UNSIGNED NULL,
  assigned_to     BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,                  -- W1d-7 (soft deletes)
  PRIMARY KEY (id),
  KEY idx_crm_activities_subject (subject_type, subject_id),
  KEY idx_crm_activities_due (due_at),
  KEY idx_crm_activities_assigned (assigned_to),   -- W1d-7 (assignment-scope)
  KEY idx_crm_activities_type (type)
);
```

> **Wave 1d note:** `type='note'` realises crm_notes (W1d-2). `assigned_to`/`created_by` +
> soft-deletes added for assignment-scoping and recoverability (W1d-7). Stage/assignment
> changes on leads/opportunities are audited (D-054).

---

## MODULE 3 — TRAINING INSTITUTE

> **Wave 4a governance (D-057/D-059):** access is ENROLLMENT-gated (training_enrollments) —
> NOT AccountScope/ContentAccessService/HasAssignmentVisibility; courses are NOT
> ContentAccessible. is_preview lessons are public; all other lesson content requires an
> active enrollment (TrainingAccessService). Assessment `correct_answer` is server-side only
> (W4-5, model $hidden). **Certificate amendment (D-059):** training_certificates gains
> `status` (valid/expired/revoked/superseded), `expires_at`, `revoked_at/by`,
> `revocation_reason`, `reissued_from_id`, `verification_hash`; training_courses gains
> `validity_months` (NULL = no expiry); numbering ICS-CERT-{YYYY}-{NNNNNN} via the new
> `training_certificate_sequences` table. Verification is public + minimal-disclosure; revoke/
> reissue staff-only, audited HIGH (D-058). Lifecycle events → TRAINING_MANAGEMENT. Analytics
> use own counters/aggregator (NOT content_engagement_events, W4-9).

### training_course_categories

```sql
CREATE TABLE training_course_categories (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(150) NOT NULL,
  slug         VARCHAR(150) NOT NULL UNIQUE,
  parent_id    BIGINT UNSIGNED NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_training_cats_parent (parent_id)
);
```

---

### training_courses

```sql
CREATE TABLE training_courses (
  id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id                 BIGINT UNSIGNED NULL,
  instructor_id             BIGINT UNSIGNED NULL,
  category_id               BIGINT UNSIGNED NULL,
  title                     VARCHAR(255) NOT NULL,
  slug                      VARCHAR(255) NOT NULL UNIQUE,
  description               TEXT NULL,
  price                     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  currency                  CHAR(3) NOT NULL DEFAULT 'NGN',
  is_paid                   TINYINT(1) NOT NULL DEFAULT 0,
  level                     ENUM('beginner','intermediate','advanced','all') NOT NULL DEFAULT 'all',
  delivery_mode             ENUM('online','in_person','hybrid') NOT NULL DEFAULT 'online',
  duration_hours            DECIMAL(6,1) NULL,
  thumbnail_path            VARCHAR(500) NULL,
  certificate_template_path VARCHAR(500) NULL,
  status                    ENUM('draft','under_review','published','archived')
                            NOT NULL DEFAULT 'draft',
  published_at              TIMESTAMP NULL,
  enrollment_count          INT UNSIGNED NOT NULL DEFAULT 0,
  completion_count          INT UNSIGNED NOT NULL DEFAULT 0,
  created_by                BIGINT UNSIGNED NULL,
  created_at                TIMESTAMP NULL,
  updated_at                TIMESTAMP NULL,
  deleted_at                TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_training_courses_tenant (tenant_id),
  KEY idx_training_courses_status (status),
  KEY idx_training_courses_instructor (instructor_id),
  FULLTEXT KEY ft_training_courses (title, description)
);
```

---

### training_course_sections

```sql
CREATE TABLE training_course_sections (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id    BIGINT UNSIGNED NOT NULL,
  title        VARCHAR(255) NOT NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_training_sections_course (course_id),
  CONSTRAINT fk_training_sections_course FOREIGN KEY (course_id)
    REFERENCES training_courses(id) ON DELETE CASCADE
);
```

---

### training_lessons

```sql
CREATE TABLE training_lessons (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  course_id        BIGINT UNSIGNED NOT NULL,
  section_id       BIGINT UNSIGNED NULL,
  title            VARCHAR(255) NOT NULL,
  type             ENUM('video','pdf','text','quiz','assignment') NOT NULL,
  content          LONGTEXT NULL COMMENT 'HTML body for text lessons',
  video_embed_url  VARCHAR(500) NULL,
  file_path        VARCHAR(500) NULL,
  sort_order       INT NOT NULL DEFAULT 0,
  duration_minutes INT UNSIGNED NULL,
  is_preview       TINYINT(1) NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_training_lessons_course (course_id),
  KEY idx_training_lessons_section (section_id),
  CONSTRAINT fk_training_lessons_course FOREIGN KEY (course_id)
    REFERENCES training_courses(id) ON DELETE CASCADE
);
```

---

### training_enrollments

```sql
CREATE TABLE training_enrollments (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  course_id        BIGINT UNSIGNED NOT NULL,
  user_id          BIGINT UNSIGNED NOT NULL,
  status           ENUM('active','completed','cancelled','suspended')
                   NOT NULL DEFAULT 'active',
  enrolled_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at     TIMESTAMP NULL,
  progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_accessed_at TIMESTAMP NULL,
  invoice_id       BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_enrollment_user_course (user_id, course_id),
  KEY idx_training_enroll_tenant (tenant_id),
  KEY idx_training_enroll_user (user_id),
  KEY idx_training_enroll_course (course_id),
  CONSTRAINT fk_training_enroll_course FOREIGN KEY (course_id)
    REFERENCES training_courses(id),
  CONSTRAINT fk_training_enroll_user FOREIGN KEY (user_id)
    REFERENCES core_users(id)
);
```

---

### training_lesson_progress

```sql
CREATE TABLE training_lesson_progress (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  enrollment_id    BIGINT UNSIGNED NOT NULL,
  lesson_id        BIGINT UNSIGNED NOT NULL,
  status           ENUM('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  started_at       TIMESTAMP NULL,
  completed_at     TIMESTAMP NULL,
  time_spent_sec   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_lesson_progress (enrollment_id, lesson_id),
  KEY idx_lesson_progress_enrollment (enrollment_id),
  CONSTRAINT fk_lesson_progress_enrollment FOREIGN KEY (enrollment_id)
    REFERENCES training_enrollments(id) ON DELETE CASCADE
);
```

---

### training_assessments

```sql
CREATE TABLE training_assessments (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id        BIGINT UNSIGNED NOT NULL,
  lesson_id        BIGINT UNSIGNED NULL COMMENT 'NULL = course-level assessment',
  title            VARCHAR(255) NOT NULL,
  type             ENUM('quiz','assignment','exam') NOT NULL DEFAULT 'quiz',
  pass_score       TINYINT UNSIGNED NOT NULL DEFAULT 70,
  max_attempts     TINYINT UNSIGNED NOT NULL DEFAULT 3,
  time_limit_min   SMALLINT UNSIGNED NULL COMMENT 'NULL = no limit',
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_training_assess_course (course_id)
);
```

---

### training_assessment_questions

```sql
CREATE TABLE training_assessment_questions (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  assessment_id    BIGINT UNSIGNED NOT NULL,
  question_text    TEXT NOT NULL,
  type             ENUM('mcq','true_false','short_answer') NOT NULL,
  options          JSON NULL COMMENT 'Array of option objects for MCQ',
  correct_answer   TEXT NOT NULL,
  marks            TINYINT UNSIGNED NOT NULL DEFAULT 1,
  sort_order       INT NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_training_questions_assess (assessment_id),
  CONSTRAINT fk_training_questions_assess FOREIGN KEY (assessment_id)
    REFERENCES training_assessments(id) ON DELETE CASCADE
);
```

---

### training_assessment_submissions

```sql
CREATE TABLE training_assessment_submissions (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  enrollment_id    BIGINT UNSIGNED NOT NULL,
  assessment_id    BIGINT UNSIGNED NOT NULL,
  attempt_number   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  answers          JSON NOT NULL,
  score            DECIMAL(5,2) NULL,
  passed           TINYINT(1) NULL,
  submitted_at     TIMESTAMP NOT NULL,
  graded_at        TIMESTAMP NULL,
  graded_by        BIGINT UNSIGNED NULL COMMENT 'NULL = auto-graded',
  feedback         TEXT NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_training_submissions_enrollment (enrollment_id),
  KEY idx_training_submissions_assessment (assessment_id)
);
```

---

### training_certificates

```sql
CREATE TABLE training_certificates (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id          BIGINT UNSIGNED NULL,
  enrollment_id      BIGINT UNSIGNED NOT NULL,
  user_id            BIGINT UNSIGNED NOT NULL,
  course_id          BIGINT UNSIGNED NOT NULL,
  certificate_number VARCHAR(50) NOT NULL UNIQUE,
  issued_at          TIMESTAMP NOT NULL,
  pdf_path           VARCHAR(500) NOT NULL,
  verification_url   VARCHAR(500) NULL,
  created_at         TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_training_cert_number (certificate_number),
  KEY idx_training_certs_user (user_id),
  KEY idx_training_certs_course (course_id)
);
```

---

### training_instructors

```sql
CREATE TABLE training_instructors (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  user_id          BIGINT UNSIGNED NOT NULL UNIQUE,
  bio              TEXT NULL,
  specializations  JSON NULL,
  status           ENUM('active','inactive','pending') NOT NULL DEFAULT 'pending',
  approved_by      BIGINT UNSIGNED NULL,
  approved_at      TIMESTAMP NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_training_instructors_user (user_id),
  CONSTRAINT fk_training_instructors_user FOREIGN KEY (user_id)
    REFERENCES core_users(id)
);
```

---

## MODULE 4 — OPPORTUNITY MARKETPLACE

> **Wave 4c governance (D-011/D-057/D-060):** access = LISTING-STATUS + REVIEW + OWNER/
> APPLICANT — NOT AccountScope, NOT ContentAccessible. `organisation_id` is PROVENANCE, not an
> isolation key (D-060 #1). Mandatory pre-publication ICS review (no auto-publish; recorded in
> marketplace_listing_reviews + audited). Published listings are public (publicVisible scope =
> published + non-expired); applications are PRIVATE (applicant + poster + ICS; attachments
> streamed/gated, W4-7/W2-5). Duplicate applications prevented by UNIQUE(listing_id,applicant_id).
> Status machine gains `removed` (post-publication moderation). NEW table
> **marketplace_listing_reports** (D-060 abuse reporting): report creation = analytics; OPEN
> reports ≥ ics.marketplace.report_autohide_threshold auto-hide the listing (→ pending_review,
> fail-safe); report RESOLUTION audited under MARKETPLACE_MANAGEMENT. Auto-expiry via scheduled
> sweep (routes/console.php) + lazy scope filter. Analytics use a dedicated aggregator (W4-9).

### marketplace_listing_reports  (Wave 4c / D-060 — abuse reporting)

```sql
CREATE TABLE marketplace_listing_reports (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id     BIGINT UNSIGNED NULL,
  listing_id    BIGINT UNSIGNED NOT NULL,
  reporter_id   BIGINT UNSIGNED NOT NULL,
  reason        ENUM('spam','scam','inappropriate','duplicate','other') NOT NULL,
  details       TEXT NULL,
  status        ENUM('open','reviewed','dismissed','actioned') NOT NULL DEFAULT 'open',
  reviewed_by   BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mkt_reports (listing_id, reporter_id),
  KEY idx_mkt_reports_status (status),
  CONSTRAINT fk_mkt_reports_listing FOREIGN KEY (listing_id)
    REFERENCES marketplace_listings(id) ON DELETE CASCADE
);
```

### marketplace_categories

```sql
CREATE TABLE marketplace_categories (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(150) NOT NULL,
  slug         VARCHAR(150) NOT NULL UNIQUE,
  listing_type ENUM('grant','tender','job','internship','scholarship','fellowship','accelerator')
               NOT NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### marketplace_listings

```sql
CREATE TABLE marketplace_listings (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id             BIGINT UNSIGNED NULL,
  posted_by_id          BIGINT UNSIGNED NOT NULL,
  organisation_id       BIGINT UNSIGNED NULL COMMENT 'FK crm_accounts',
  category_id           BIGINT UNSIGNED NULL,
  title                 VARCHAR(255) NOT NULL,
  description           LONGTEXT NOT NULL,
  type                  ENUM('grant','tender','job','internship','scholarship','fellowship','accelerator')
                        NOT NULL,
  deadline              DATE NULL,
  value                 DECIMAL(14,2) NULL,
  currency              CHAR(3) NULL,
  requirements          TEXT NULL,
  location              VARCHAR(150) NULL,
  is_remote             TINYINT(1) NOT NULL DEFAULT 0,
  status                ENUM('draft','pending_review','published','expired','rejected')
                        NOT NULL DEFAULT 'draft',
  published_at          TIMESTAMP NULL,
  application_count     INT UNSIGNED NOT NULL DEFAULT 0,
  shared_by_profile_id  BIGINT UNSIGNED NULL COMMENT 'FK community_profiles',
  created_at            TIMESTAMP NULL,
  updated_at            TIMESTAMP NULL,
  deleted_at            TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_mkt_listings_tenant (tenant_id),
  KEY idx_mkt_listings_status (status),
  KEY idx_mkt_listings_type (type),
  KEY idx_mkt_listings_deadline (deadline),
  FULLTEXT KEY ft_mkt_listings (title, description)
);
```

---

### marketplace_applications

```sql
CREATE TABLE marketplace_applications (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  listing_id       BIGINT UNSIGNED NOT NULL,
  applicant_id     BIGINT UNSIGNED NOT NULL,
  cover_letter     TEXT NULL,
  attachments      JSON NULL COMMENT 'Array of file paths',
  status           ENUM('submitted','under_review','shortlisted','accepted','rejected')
                   NOT NULL DEFAULT 'submitted',
  submitted_at     TIMESTAMP NOT NULL,
  reviewed_at      TIMESTAMP NULL,
  reviewed_by      BIGINT UNSIGNED NULL,
  notes            TEXT NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mkt_applications (listing_id, applicant_id),
  KEY idx_mkt_applications_listing (listing_id),
  KEY idx_mkt_applications_applicant (applicant_id),
  CONSTRAINT fk_mkt_applications_listing FOREIGN KEY (listing_id)
    REFERENCES marketplace_listings(id)
);
```

---

### marketplace_listing_reviews

```sql
CREATE TABLE marketplace_listing_reviews (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id    BIGINT UNSIGNED NOT NULL,
  reviewed_by   BIGINT UNSIGNED NOT NULL,
  decision      ENUM('approve','reject') NOT NULL,
  notes         TEXT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mkt_reviews_listing (listing_id)
);
```

---

## MODULE 5 — PARTNER PORTAL

> **D-055 / Wave 2 governance:** every partner (org OR individual) receives a `crm_account`;
> `partner_profiles.account_id`, `partner_referrals.account_id`, and
> `partner_agreements.account_id` are REQUIRED, making **AccountScope** (BelongsToAccount +
> OrgOwnedPolicy) the SOLE portal isolation mechanism. W2-3: `partner_referrals.lead_id`
> links to the internal crm_lead but is ICS-ONLY — never serialised to a partner.
> Agreement/commission/suspension events are audited HIGH under `portal_management` (D-056).

### partner_tiers

```sql
CREATE TABLE partner_tiers (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(100) NOT NULL,
  slug            VARCHAR(100) NOT NULL UNIQUE,
  benefits        JSON NULL,
  min_referrals   INT UNSIGNED NOT NULL DEFAULT 0,
  commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  sort_order      INT NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### partner_profiles

```sql
CREATE TABLE partner_profiles (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id          BIGINT UNSIGNED NULL,
  user_id            BIGINT UNSIGNED NOT NULL,
  account_id         BIGINT UNSIGNED NULL,
  tier_id            BIGINT UNSIGNED NULL,
  organisation_name  VARCHAR(255) NOT NULL,
  status             ENUM('pending','active','suspended','terminated') NOT NULL DEFAULT 'pending',
  approved_at        TIMESTAMP NULL,
  approved_by        BIGINT UNSIGNED NULL,
  agreement_signed_at TIMESTAMP NULL,
  created_at         TIMESTAMP NULL,
  updated_at         TIMESTAMP NULL,
  deleted_at         TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_partner_profiles_user (user_id),
  KEY idx_partner_profiles_tier (tier_id),
  CONSTRAINT fk_partner_profiles_user FOREIGN KEY (user_id)
    REFERENCES core_users(id)
);
```

---

### partner_referrals

```sql
CREATE TABLE partner_referrals (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id            BIGINT UNSIGNED NULL,
  account_id           BIGINT UNSIGNED NOT NULL,   -- D-055 ownership key (AccountScope)
  partner_id           BIGINT UNSIGNED NOT NULL,
  referred_org_name    VARCHAR(255) NOT NULL,
  referred_contact     VARCHAR(255) NULL,
  referred_email       VARCHAR(255) NULL,
  stage                ENUM('submitted','qualified','converted','lost') NOT NULL DEFAULT 'submitted',
  lead_id              BIGINT UNSIGNED NULL,
  commission_amount    DECIMAL(12,2) NULL,
  commission_currency  CHAR(3) NULL DEFAULT 'NGN',
  commission_paid_at   TIMESTAMP NULL,
  notes                TEXT NULL,
  created_at           TIMESTAMP NULL,
  updated_at           TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_partner_referrals_partner (partner_id),
  KEY idx_partner_referrals_lead (lead_id)
);
```

---

### partner_agreements

```sql
CREATE TABLE partner_agreements (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  account_id      BIGINT UNSIGNED NOT NULL,   -- D-055 ownership key (AccountScope)
  partner_id      BIGINT UNSIGNED NOT NULL,
  title           VARCHAR(255) NOT NULL,
  type            VARCHAR(100) NOT NULL,
  effective_date  DATE NULL,
  expiry_date     DATE NULL,
  signed_at       TIMESTAMP NULL,
  file_path       VARCHAR(500) NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_partner_agreements_partner (partner_id),
  KEY idx_partner_agreements_expiry (expiry_date)
);
```

---

## MODULE 6 — STARTUP HUB

> **Wave 5A governance (D-061/D-062/D-063/D-064/C-1):** FOUNDER-OWNED (startup_profiles.founder_id)
> — NOT account-owned (H-3): no account_id, no AccountScope; access = participation family
> (StartupAccessService); CRM link one-way (D-053). **D-063 lifecycle reconciliation:**
> `lifecycle_stage` (idea/registered/validation/incubation/acceleration/investment_ready/alumni)
> is the AUTHORITATIVE journey; `stage` kept ONLY as product maturity; `status` narrowed to
> active/suspended/inactive; **`program_type` REMOVED** (derives from startup_program_enrollments).
> **C-1:** cap-table/ownership/valuation/fundraising/investor-docs are INVESTMENT NETWORK (5d)
> data-room data (system of record); Wave 5A holds only a MINIMAL gated `startup_team_members.
> ownership_percent` (founder/admin/staff/granted-investor only; $hidden; excluded from all
> public/community/marketplace/analytics). **D-064:** ownership totals ≤100% non-negative;
> founder-ownership/verify/suspend = HIGH audit; ≥1 active founder always; ownership transfer
> mandatory before founder removal (orphan-blocked); transfer history immutable. New tables:
> **startup_team_invitations** (M-2 invite flow), **startup_ownership_transfers** (immutable, H-2).
> startup_mentors gains `type` (mentor/advisor, M-3). Audited under STARTUP_MANAGEMENT (D-062).

### startup_profiles

```sql
CREATE TABLE startup_profiles (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  founder_id       BIGINT UNSIGNED NOT NULL,
  name             VARCHAR(255) NOT NULL,
  slug             VARCHAR(255) NOT NULL UNIQUE,
  description      TEXT NULL,
  industry         VARCHAR(100) NULL,
  stage            ENUM('idea','mvp','growth','scale','exit') NOT NULL DEFAULT 'idea',
  founding_year    SMALLINT UNSIGNED NULL,
  team_size        TINYINT UNSIGNED NULL,
  website          VARCHAR(255) NULL,
  logo_path        VARCHAR(500) NULL,
  country_code     CHAR(2) NULL,
  program_type     ENUM('general','incubator','accelerator') NOT NULL DEFAULT 'general',
  status           ENUM('pending','active','graduated','inactive') NOT NULL DEFAULT 'pending',
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  deleted_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_startup_profiles_founder (founder_id),
  KEY idx_startup_profiles_status (status)
);
```

---

### startup_team_members

```sql
CREATE TABLE startup_team_members (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  startup_id   BIGINT UNSIGNED NOT NULL,
  user_id      BIGINT UNSIGNED NULL COMMENT 'NULL = unregistered member',
  name         VARCHAR(255) NOT NULL,
  role         VARCHAR(150) NOT NULL,
  email        VARCHAR(255) NULL,
  is_founder   TINYINT(1) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_startup_members_startup (startup_id),
  CONSTRAINT fk_startup_members_startup FOREIGN KEY (startup_id)
    REFERENCES startup_profiles(id) ON DELETE CASCADE
);
```

---

### startup_milestones

```sql
CREATE TABLE startup_milestones (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  startup_id     BIGINT UNSIGNED NOT NULL,
  title          VARCHAR(255) NOT NULL,
  description    TEXT NULL,
  category       VARCHAR(100) NULL,
  target_date    DATE NULL,
  completed_at   TIMESTAMP NULL,
  status         ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_by     BIGINT UNSIGNED NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_startup_milestones_startup (startup_id),
  CONSTRAINT fk_startup_milestones_startup FOREIGN KEY (startup_id)
    REFERENCES startup_profiles(id) ON DELETE CASCADE
);
```

---

### startup_mentors

```sql
CREATE TABLE startup_mentors (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  startup_id   BIGINT UNSIGNED NOT NULL,
  mentor_id    BIGINT UNSIGNED NOT NULL,
  assigned_at  TIMESTAMP NOT NULL,
  assigned_by  BIGINT UNSIGNED NULL,
  status       ENUM('active','ended') NOT NULL DEFAULT 'active',
  notes        TEXT NULL,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_startup_mentors_startup (startup_id),
  KEY idx_startup_mentors_mentor (mentor_id)
);
```

---

> **Wave 5B — GENERIC Program Architecture (D-065/D-066/D-067):** ONE program architecture
> shared by Incubator AND Accelerator (type on startup_programs). Cohorts (intake cycles) are
> first-class (**program_cohorts**); coordinators manage cohorts (**program_coordinators**, M-2 —
> NOT CRM assignment). startup_program_enrollments is the GOVERNED participation record: status
> widened to the M-1 intake flow (applied→under_review→accepted→active→graduated→withdrawn/
> removed) + cohort_id + decision/reason fields; UNIQUE(startup_id, cohort_id) (D-067 no double
> entry); a startup may hold only one active participation (D-067 conflict guard). Lifecycle
> transitions route through StartupGovernanceService (H-3, D-063 remains the single authority).
> Governance events → PROGRAM_MANAGEMENT (D-066; forced removal/graduation-reversal/program
> suspend/reinstate/terminate = HIGH). startup_programs.status widened (suspended/terminated/
> archived). Accelerator (5c) adds specialized features only (Demo Day, Investor Showcase).

### program_cohorts  (Wave 5B / D-065 — intake cycles, shared)

```sql
CREATE TABLE program_cohorts (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  program_id       BIGINT UNSIGNED NOT NULL,
  name             VARCHAR(255) NOT NULL,
  intake_opens_at  TIMESTAMP NULL,
  intake_closes_at TIMESTAMP NULL,
  start_date       DATE NULL,
  end_date         DATE NULL,
  max_startups     SMALLINT UNSIGNED NULL,
  status           ENUM('planned','intake_open','active','closed','archived') NOT NULL DEFAULT 'planned',
  created_at       TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_program_cohorts_program (program_id),
  CONSTRAINT fk_program_cohorts_program FOREIGN KEY (program_id) REFERENCES startup_programs(id) ON DELETE CASCADE
);
```

### program_coordinators  (Wave 5B / M-2 — cohort coordinators, NOT CRM assignment)

```sql
CREATE TABLE program_coordinators (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cohort_id    BIGINT UNSIGNED NOT NULL,
  user_id      BIGINT UNSIGNED NOT NULL,
  assigned_by  BIGINT UNSIGNED NULL,
  assigned_at  TIMESTAMP NOT NULL,
  created_at   TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_program_coordinator (cohort_id, user_id),
  CONSTRAINT fk_program_coordinators_cohort FOREIGN KEY (cohort_id) REFERENCES program_cohorts(id) ON DELETE CASCADE
);
```

> startup_program_enrollments (Wave 5A) is EXTENDED in 5B: + cohort_id, applied_at, decided_at,
> decided_by, withdrawal_reason, removal_reason; status enum widened to the M-1 flow;
> UNIQUE(startup_id, cohort_id).

> **Wave 5C — Accelerator thin specialization (D-068/D-069):** Accelerator = type='accelerator'
> reusing the whole program layer. ONLY new surface = the GENERIC, LIGHTWEIGHT Program Events
> layer (M-1): **program_events** (types demo_day/pitch_event/showcase/readiness_review/
> graduation_showcase; a `finalized_at` lock — NO workflow engine), **program_event_judges**
> (existing users referenced; H-2 no investor registry), **program_event_scores** (one per
> judge×startup×criterion, M-4; OPERATIONAL-MATURITY only, H-3 — never valuation/equity/
> financial). Readiness = aggregate of finalized readiness_review scores (ReadinessCalculator);
> it gates accelerator graduation via CompletionValidator (M-2). Showcase = exposure/discovery of
> curated public startup fields ONLY (H-1; no cap-table/data-room). Audited via PROGRAM_MANAGEMENT
> (score/readiness override + showcase revoke HIGH). D-069 PROHIBITS in Accelerator: investor
> registry, fundraising, due-diligence, deal room, cap-table, investment-matching (those = 5d).

### program_events  (Wave 5C / D-068 — generic, lightweight; reusable ecosystem infra)

```sql
CREATE TABLE program_events (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id     BIGINT UNSIGNED NULL,
  cohort_id     BIGINT UNSIGNED NOT NULL,
  type          ENUM('demo_day','pitch_event','showcase','readiness_review','graduation_showcase') NOT NULL,
  title         VARCHAR(255) NOT NULL,
  description   TEXT NULL,
  scheduled_at  TIMESTAMP NULL,
  finalized_at  TIMESTAMP NULL,                   -- lock only; NOT a workflow state machine
  created_by    BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_program_events_cohort (cohort_id),
  CONSTRAINT fk_program_events_cohort FOREIGN KEY (cohort_id) REFERENCES program_cohorts(id) ON DELETE CASCADE
);

CREATE TABLE program_event_judges (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, event_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL, assigned_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id), UNIQUE KEY uk_program_event_judge (event_id, user_id),
  CONSTRAINT fk_program_event_judges_event FOREIGN KEY (event_id) REFERENCES program_events(id) ON DELETE CASCADE
);

CREATE TABLE program_event_scores (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, event_id BIGINT UNSIGNED NOT NULL,
  judge_id BIGINT UNSIGNED NOT NULL, startup_id BIGINT UNSIGNED NOT NULL,
  criterion VARCHAR(100) NOT NULL, score DECIMAL(5,2) NOT NULL, feedback TEXT NULL,  -- maturity only (H-3)
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  PRIMARY KEY (id), UNIQUE KEY uk_program_event_score (event_id, judge_id, startup_id, criterion),
  CONSTRAINT fk_program_event_scores_event FOREIGN KEY (event_id) REFERENCES program_events(id) ON DELETE CASCADE
);
```

### startup_programs

```sql
CREATE TABLE startup_programs (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  name             VARCHAR(255) NOT NULL,
  type             ENUM('general','incubator','accelerator') NOT NULL,
  cohort_name      VARCHAR(100) NULL,
  start_date       DATE NULL,
  end_date         DATE NULL,
  max_startups     TINYINT UNSIGNED NULL,
  description      TEXT NULL,
  status           ENUM('planned','active','completed','cancelled') NOT NULL DEFAULT 'planned',
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### startup_program_enrollments

```sql
CREATE TABLE startup_program_enrollments (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  startup_id       BIGINT UNSIGNED NOT NULL,
  program_id       BIGINT UNSIGNED NOT NULL,
  enrolled_at      TIMESTAMP NOT NULL,
  graduated_at     TIMESTAMP NULL,
  status           ENUM('active','graduated','withdrawn') NOT NULL DEFAULT 'active',
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_program_enrollment (startup_id, program_id),
  KEY idx_startup_prog_enroll_startup (startup_id)
);
```

---

## MODULE 7 — CLIENT PORTAL

> **Wave 2 governance:** `client_projects.account_id` and `client_tickets.account_id` are
> ownership keys (D-050) → ORG-OWNED via AccountScope + OrgOwnedPolicy. Children
> (`client_project_milestones`, `client_deliverables`, `client_ticket_replies`) carry NO
> account_id and are PARENT-ISOLATED (W2-1) — reached only through their AccountScope-
> protected parent, never queried independently. `client_ticket_replies.is_internal=1` is
> staff-only, filtered from clients at query + policy + resource layers (W2-4). Deliverable/
> agreement files are policy-gated/streamed, never public URLs (W2-5). Lifecycle events are
> audited under `portal_management` (D-056).

### client_projects

```sql
CREATE TABLE client_projects (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id           BIGINT UNSIGNED NULL,
  account_id          BIGINT UNSIGNED NOT NULL,
  contract_id         BIGINT UNSIGNED NULL,
  title               VARCHAR(255) NOT NULL,
  description         TEXT NULL,
  status              ENUM('planning','active','on_hold','completed','cancelled')
                      NOT NULL DEFAULT 'planning',
  start_date          DATE NULL,
  target_end_date     DATE NULL,
  actual_end_date     DATE NULL,
  project_manager_id  BIGINT UNSIGNED NULL,
  created_at          TIMESTAMP NULL,
  updated_at          TIMESTAMP NULL,
  deleted_at          TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_client_projects_account (account_id),
  KEY idx_client_projects_status (status),
  CONSTRAINT fk_client_projects_account FOREIGN KEY (account_id)
    REFERENCES crm_accounts(id)
);
```

---

### client_project_milestones

```sql
CREATE TABLE client_project_milestones (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id     BIGINT UNSIGNED NOT NULL,
  title          VARCHAR(255) NOT NULL,
  description    TEXT NULL,
  due_date       DATE NULL,
  completed_at   TIMESTAMP NULL,
  status         ENUM('pending','in_progress','completed','missed') NOT NULL DEFAULT 'pending',
  created_by     BIGINT UNSIGNED NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_client_milestones_project (project_id),
  CONSTRAINT fk_client_milestones_project FOREIGN KEY (project_id)
    REFERENCES client_projects(id) ON DELETE CASCADE
);
```

---

### client_deliverables

```sql
CREATE TABLE client_deliverables (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id       BIGINT UNSIGNED NOT NULL,
  milestone_id     BIGINT UNSIGNED NULL,
  title            VARCHAR(255) NOT NULL,
  description      TEXT NULL,
  file_path        VARCHAR(500) NOT NULL,
  version          VARCHAR(20) NOT NULL DEFAULT '1.0',
  status           ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  submitted_at     TIMESTAMP NULL,
  approved_at      TIMESTAMP NULL,
  approved_by      BIGINT UNSIGNED NULL,
  created_by       BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_client_deliverables_project (project_id)
);
```

---

### client_tickets

```sql
CREATE TABLE client_tickets (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  project_id       BIGINT UNSIGNED NULL,
  account_id       BIGINT UNSIGNED NOT NULL,
  user_id          BIGINT UNSIGNED NOT NULL,
  title            VARCHAR(255) NOT NULL,
  description      TEXT NOT NULL,
  priority         ENUM('low','normal','high','critical') NOT NULL DEFAULT 'normal',
  status           ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  assigned_to      BIGINT UNSIGNED NULL,
  resolved_at      TIMESTAMP NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_client_tickets_account (account_id),
  KEY idx_client_tickets_status (status),
  KEY idx_client_tickets_priority (priority)
);
```

---

### client_ticket_replies

```sql
CREATE TABLE client_ticket_replies (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ticket_id    BIGINT UNSIGNED NOT NULL,
  author_id    BIGINT UNSIGNED NOT NULL,
  body         TEXT NOT NULL,
  is_internal  TINYINT(1) NOT NULL DEFAULT 0,
  attachments  JSON NULL,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_ticket_replies_ticket (ticket_id),
  CONSTRAINT fk_ticket_replies_ticket FOREIGN KEY (ticket_id)
    REFERENCES client_tickets(id) ON DELETE CASCADE
);
```

---

## MODULE 8 — CORPORATE WEBSITE / CMS

### content_pages

```sql
CREATE TABLE content_pages (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  title            VARCHAR(255) NOT NULL,
  slug             VARCHAR(255) NOT NULL,
  body             LONGTEXT NULL,
  template         VARCHAR(100) NOT NULL DEFAULT 'default',
  seo_title        VARCHAR(255) NULL,
  seo_description  TEXT NULL,
  status           ENUM('draft','under_review','published','archived') NOT NULL DEFAULT 'draft',
  published_at     TIMESTAMP NULL,
  created_by       BIGINT UNSIGNED NULL,   -- D-052 publication traceability
  updated_by       BIGINT UNSIGNED NULL,   -- D-052
  published_by     BIGINT UNSIGNED NULL,   -- D-052
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  deleted_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_content_pages_slug (slug),
  KEY idx_content_pages_status (status),
  FULLTEXT KEY ft_content_pages (title, body)   -- W1c-1 (MySQL; SQLite test DB uses LIKE fallback)
);
```

> **D-052 / Wave 1c reconciliation:** the lifecycle is the engine's full four states
> (`draft → under_review → published → archived`, HasContentLifecycle); traceability
> columns `updated_by` and `published_by` were added alongside `created_by`; a FULLTEXT
> index on (title, body) is mandatory (W1c-1). `created_by`/`updated_by` are stamped by
> HasAuthorship; `published_by` is stamped by CmsService::publish().

---

### content_articles

```sql
CREATE TABLE content_articles (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  title            VARCHAR(255) NOT NULL,
  slug             VARCHAR(255) NOT NULL UNIQUE,
  excerpt          TEXT NULL,
  body             LONGTEXT NULL,
  featured_image   VARCHAR(500) NULL,
  seo_title        VARCHAR(255) NULL,
  seo_description  TEXT NULL,
  status           ENUM('draft','under_review','published','archived') NOT NULL DEFAULT 'draft',
  published_at     TIMESTAMP NULL,
  view_count       INT UNSIGNED NOT NULL DEFAULT 0,   -- cached engagement counter
  created_by       BIGINT UNSIGNED NULL,   -- D-052
  updated_by       BIGINT UNSIGNED NULL,   -- D-052
  published_by     BIGINT UNSIGNED NULL,   -- D-052
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  deleted_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_content_articles_status (status),
  FULLTEXT KEY ft_content_articles (title, body)   -- W1c-1
);
```

> **D-052 / Wave 1c reconciliation:** four-state lifecycle; `updated_by`/`published_by`
> added; `view_count` is the cached counter incremented on each recorded view (the
> append-only detail lives in `content_engagement_events`, D-038/D-051).

---

### content_media

```sql
CREATE TABLE content_media (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  type            ENUM('image','document','video','other') NOT NULL,
  file_path       VARCHAR(500) NOT NULL,
  original_name   VARCHAR(255) NOT NULL,
  mime_type       VARCHAR(100) NOT NULL,
  size_kb         INT UNSIGNED NOT NULL,
  alt_text        VARCHAR(255) NULL,
  uploaded_by     BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_content_media_type (type)
);
```

---

## MODULE 9 — KNOWLEDGE CENTER

> **Wave 3 governance:** knowledge_articles + knowledge_resources are engine consumers
> (HasContentLifecycle + HasFullTextSearch + ContentAccessible, LATERAL D-036). `access_tier`
> is strategy-relative (W3-1: 3=CLIENT, 4=PARTNER). `excerpt`/`description` are ALWAYS public
> (teaser/SEO, W3-3); `body`/`file_path` are tier-gated in resources + gated download endpoints
> (W2-5). Publish/archive audited under module 'knowledge' (W3-2). **Wave 3 reconciliation:**
> downloadable assets were SPLIT OUT of the `knowledge_articles.type` enum into a dedicated
> **knowledge_resources** table (file-centric); knowledge_articles.type now covers readable
> content only. Engagement → content_engagement_events (D-051). Tags/bookmarks/ratings/related
> remain article-scoped (deferred; not in Wave 3 build scope).

### knowledge_resources  (Wave 3 — downloadable asset library, engine consumer)

```sql
CREATE TABLE knowledge_resources (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  category_id     BIGINT UNSIGNED NULL,
  type            ENUM('template','toolkit','sop','checklist','dataset','download','other') NOT NULL DEFAULT 'template',
  title           VARCHAR(255) NOT NULL,
  slug            VARCHAR(255) NOT NULL UNIQUE,
  description     TEXT NULL,                       -- ALWAYS public teaser (W3-3)
  file_path       VARCHAR(500) NULL,               -- tier-gated download
  file_size_kb    INT UNSIGNED NULL,
  access_tier     TINYINT UNSIGNED NOT NULL DEFAULT 1,  -- D-036 (LATERAL)
  status          ENUM('draft','under_review','published','archived') NOT NULL DEFAULT 'draft',
  download_count  INT UNSIGNED NOT NULL DEFAULT 0,
  seo_title       VARCHAR(255) NULL,
  seo_description TEXT NULL,
  published_at    TIMESTAMP NULL,
  created_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_knowledge_resources_category (category_id),
  KEY idx_knowledge_resources_tier (access_tier),
  KEY idx_knowledge_resources_status (status),
  FULLTEXT KEY ft_knowledge_resources (title, description)   -- W1c-1
);
```

### knowledge_categories

```sql
CREATE TABLE knowledge_categories (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  name            VARCHAR(150) NOT NULL,
  slug            VARCHAR(150) NOT NULL UNIQUE,
  icon            VARCHAR(100) NULL,
  parent_id       BIGINT UNSIGNED NULL,
  sort_order      INT NOT NULL DEFAULT 0,
  article_count   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_knowledge_cats_parent (parent_id)
);
```

---

### knowledge_articles

```sql
CREATE TABLE knowledge_articles (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  category_id      BIGINT UNSIGNED NULL,
  type             ENUM('article','news','guide','white_paper','template','toolkit',
                   'sop','checklist','case_study','training_resource','video','download',
                   'resource_collection','client_doc','internal_kb') NOT NULL DEFAULT 'article',
  title            VARCHAR(255) NOT NULL,
  slug             VARCHAR(255) NOT NULL UNIQUE,
  excerpt          TEXT NULL COMMENT 'Always public — used for SEO',
  body             LONGTEXT NULL,
  featured_image   VARCHAR(500) NULL,
  file_path        VARCHAR(500) NULL,
  file_size_kb     INT UNSIGNED NULL,
  video_embed_url  VARCHAR(500) NULL,
  access_tier      TINYINT UNSIGNED NOT NULL DEFAULT 1
                   COMMENT '1=public 2=member 3=client 4=partner 5=internal (D-036)',
  status           ENUM('draft','under_review','published','archived') NOT NULL DEFAULT 'draft',
  read_time_min    TINYINT UNSIGNED NULL,
  view_count       INT UNSIGNED NOT NULL DEFAULT 0,
  download_count   INT UNSIGNED NOT NULL DEFAULT 0,
  bookmark_count   INT UNSIGNED NOT NULL DEFAULT 0,
  average_rating   DECIMAL(3,2) NULL,
  seo_title        VARCHAR(255) NULL,
  seo_description  TEXT NULL,
  metadata         JSON NULL COMMENT 'Loose coupling data (e.g. linked course_id)',
  published_at     TIMESTAMP NULL,
  created_by       BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  deleted_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_knowledge_articles_tenant (tenant_id),
  KEY idx_knowledge_articles_category (category_id),
  KEY idx_knowledge_articles_type (type),
  KEY idx_knowledge_articles_tier (access_tier),
  KEY idx_knowledge_articles_status (status),
  FULLTEXT KEY ft_knowledge_articles (title, excerpt, body)
);
```

---

### knowledge_tags

```sql
CREATE TABLE knowledge_tags (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(100) NOT NULL,
  slug         VARCHAR(100) NOT NULL UNIQUE,
  created_at   TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### knowledge_article_tags

```sql
CREATE TABLE knowledge_article_tags (
  article_id   BIGINT UNSIGNED NOT NULL,
  tag_id       BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (article_id, tag_id),
  KEY idx_knowledge_article_tags_tag (tag_id)
);
```

---

### knowledge_bookmarks

```sql
CREATE TABLE knowledge_bookmarks (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      BIGINT UNSIGNED NOT NULL,
  article_id   BIGINT UNSIGNED NOT NULL,
  created_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_knowledge_bookmark (user_id, article_id),
  KEY idx_knowledge_bookmarks_article (article_id)
);
```

---

### knowledge_ratings

```sql
CREATE TABLE knowledge_ratings (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      BIGINT UNSIGNED NOT NULL,
  article_id   BIGINT UNSIGNED NOT NULL,
  rating       TINYINT UNSIGNED NOT NULL COMMENT '1-5',
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_knowledge_rating (user_id, article_id)
);
```

---

### knowledge_views — SUPERSEDED (D-051)

> RETIRED. Replaced by the unified `content_engagement_events` table (event_type=view).
> See "content_engagement_events" below.

### knowledge_downloads — SUPERSEDED (D-051)

> RETIRED. Replaced by `content_engagement_events` (event_type=download).

---

### content_engagement_events  (D-038 / D-051 — unified content analytics)

```sql
CREATE TABLE content_engagement_events (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id     BIGINT UNSIGNED NULL,
  content_type  VARCHAR(100) NOT NULL COMMENT 'polymorphic model class (CMS/Knowledge/Research)',
  content_id    BIGINT UNSIGNED NOT NULL,
  event_type    ENUM('view','download','citation') NOT NULL,
  user_id       BIGINT UNSIGNED NULL,
  session_id    VARCHAR(64) NULL COMMENT 'guest dedup',
  ip_address    VARCHAR(45) NULL,
  country_code  CHAR(2) NULL,
  referrer_url  VARCHAR(500) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cee_content (content_type, content_id),
  KEY idx_cee_event (event_type),
  KEY idx_cee_created (created_at)
) COMMENT 'Append-only. Supersedes knowledge_views/knowledge_downloads/research_downloads (D-051). Cached counters remain on content rows.';
```

---

### knowledge_related

```sql
CREATE TABLE knowledge_related (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  article_id          BIGINT UNSIGNED NOT NULL,
  related_article_id  BIGINT UNSIGNED NOT NULL,
  relation_type       ENUM('manual','auto_category','auto_tag','ai_suggested') NOT NULL,
  score               DECIMAL(5,4) NULL,
  created_at          TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_knowledge_related (article_id, related_article_id),
  KEY idx_knowledge_related_article (article_id)
);
```

---

## MODULE 10 — RESEARCH CENTER

> **Wave 3 governance:** research_publications is an engine consumer (HasContentLifecycle +
> HasFullTextSearch + ContentAccessible, HIERARCHICAL D-034: user_tier >= tier). `access_tier`
> is strategy-relative (W3-1). `abstract` is ALWAYS public (teaser/SEO/citation, W3-3); `body`/
> `file_path` tier-gated. Publish/archive audited under module 'research' (W3-2). A cached
> `citation_count` column was added (counter; structured research_citations graph deferred —
> not in Wave 3 build scope). Engagement (view/download/citation) → content_engagement_events
> (D-051). Authors may be external (user_id NULL, W3-8); M2M via research_publication_authors.

### research_categories

```sql
CREATE TABLE research_categories (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id    BIGINT UNSIGNED NULL,
  name         VARCHAR(150) NOT NULL,
  slug         VARCHAR(150) NOT NULL UNIQUE,
  description  TEXT NULL,
  parent_id    BIGINT UNSIGNED NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### research_authors

```sql
CREATE TABLE research_authors (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  user_id         BIGINT UNSIGNED NULL COMMENT 'NULL for external authors',
  name            VARCHAR(255) NOT NULL,
  title           VARCHAR(150) NULL,
  bio             TEXT NULL,
  avatar_path     VARCHAR(500) NULL,
  email           VARCHAR(255) NULL,
  organisation    VARCHAR(255) NULL,
  orcid_id        VARCHAR(50) NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_research_authors_user (user_id)
);
```

---

### research_publications

```sql
CREATE TABLE research_publications (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  category_id     BIGINT UNSIGNED NULL,
  content_group   ENUM('summary','brief','public_report','insight',
                  'full_report','template','archive',
                  'partner_research','collaborative','restricted',
                  'draft','working_paper','internal','pipeline') NOT NULL,
  title           VARCHAR(255) NOT NULL,
  slug            VARCHAR(255) NOT NULL UNIQUE,
  abstract        TEXT NOT NULL COMMENT 'Always public — SEO, discoverability',
  body            LONGTEXT NULL,
  file_path       VARCHAR(500) NULL,
  file_size_kb    INT UNSIGNED NULL,
  doi             VARCHAR(100) NULL,
  publish_date    DATE NULL,
  access_tier     TINYINT UNSIGNED NOT NULL DEFAULT 1
                  COMMENT '1=public 2=member 3=partner 4=internal 5=admin (D-034)',
  status          ENUM('draft','under_review','published','archived') NOT NULL DEFAULT 'draft',
  view_count      INT UNSIGNED NOT NULL DEFAULT 0,
  download_count  INT UNSIGNED NOT NULL DEFAULT 0,
  created_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_research_pubs_tenant (tenant_id),
  KEY idx_research_pubs_tier (access_tier),
  KEY idx_research_pubs_status (status),
  FULLTEXT KEY ft_research_pubs (title, abstract)
);
```

---

### research_publication_authors

```sql
CREATE TABLE research_publication_authors (
  publication_id   BIGINT UNSIGNED NOT NULL,
  author_id        BIGINT UNSIGNED NOT NULL,
  author_order     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (publication_id, author_id),
  KEY idx_rpa_author (author_id)
);
```

---

### research_downloads — SUPERSEDED (D-051)

> RETIRED. Replaced by the unified `content_engagement_events` table
> (event_type=download; content_type = research publication). See
> "content_engagement_events" in the Knowledge Center section.

---

### research_citations

```sql
CREATE TABLE research_citations (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  publication_id   BIGINT UNSIGNED NOT NULL,
  cited_by_type    ENUM('research_publication','external_url','manual_entry') NOT NULL,
  cited_by_id      BIGINT UNSIGNED NULL,
  cited_by_url     VARCHAR(500) NULL,
  cited_by_title   VARCHAR(255) NULL,
  created_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_research_citations_pub (publication_id)
);
```

---

## MODULE 11 — AI SERVICES

### ai_requests

```sql
CREATE TABLE ai_requests (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  user_id          BIGINT UNSIGNED NULL,
  module           VARCHAR(50) NOT NULL,
  use_case         VARCHAR(100) NOT NULL,
  rate_tier        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=light 2=medium 3=heavy',
  prompt_tokens    INT UNSIGNED NOT NULL DEFAULT 0,
  response_tokens  INT UNSIGNED NOT NULL DEFAULT 0,
  total_tokens     INT UNSIGNED NOT NULL DEFAULT 0,
  model_version    VARCHAR(50) NULL,
  cost_usd         DECIMAL(10,6) NULL,
  status           ENUM('success','failed','timeout','budget_exceeded') NOT NULL,
  cached           TINYINT(1) NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ai_requests_user (user_id),
  KEY idx_ai_requests_use_case (use_case),
  KEY idx_ai_requests_created (created_at)
) COMMENT 'Append-only cost tracking';
```

---

### ai_assessments

```sql
CREATE TABLE ai_assessments (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id         BIGINT UNSIGNED NULL,
  user_id           BIGINT UNSIGNED NOT NULL,
  subject_type      ENUM('startup','client_organization','individual') NOT NULL,
  subject_id        BIGINT UNSIGNED NOT NULL,
  assessment_type   ENUM('startup_readiness','digital_maturity') NOT NULL,
  overall_score     DECIMAL(5,2) NULL,
  dimensions        JSON NULL,
  recommendations   JSON NULL,
  model_version     VARCHAR(50) NULL,
  ai_request_id     BIGINT UNSIGNED NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  deleted_at        TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_ai_assess_subject (subject_type, subject_id),
  KEY idx_ai_assess_type (assessment_type),
  KEY idx_ai_assess_user (user_id)
);
```

---

### ai_cache

```sql
CREATE TABLE ai_cache (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cache_key    VARCHAR(255) NOT NULL UNIQUE,
  response     LONGTEXT NOT NULL,
  use_case     VARCHAR(100) NOT NULL,
  expires_at   TIMESTAMP NOT NULL,
  created_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_ai_cache_expires (expires_at)
);
```

---

## MODULE 12 — NOTIFICATIONS

### notify_preferences

```sql
CREATE TABLE notify_preferences (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id               BIGINT UNSIGNED NOT NULL,
  notification_type     VARCHAR(255) NOT NULL COMMENT 'Notification class name',
  mail_enabled          TINYINT(1) NOT NULL DEFAULT 1,
  whatsapp_enabled      TINYINT(1) NOT NULL DEFAULT 0,
  database_enabled      TINYINT(1) NOT NULL DEFAULT 1,
  updated_at            TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_notify_prefs (user_id, notification_type)
);
```

---

### notify_push_subscriptions

```sql
CREATE TABLE notify_push_subscriptions (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      BIGINT UNSIGNED NOT NULL,
  endpoint     TEXT NOT NULL,
  p256dh       TEXT NOT NULL COMMENT 'VAPID public key',
  auth         VARCHAR(255) NOT NULL,
  user_agent   VARCHAR(500) NULL,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_push_subs_user (user_id)
);
```

---

## MODULE 13 — BILLING & SUBSCRIPTIONS

> **Wave Billing governance (D-084/D-085/D-086):** the substrate is implemented as blueprinted —
> webhook-driven, signature-verified-FIRST (HMAC-SHA512), idempotent (billing_webhooks unique
> (gateway, gateway_event_id) + processed flag), transaction-bounded, replay-safe. Entitlement is a
> pure derivation of LIVE subscription status ({trial, active} only) — NO stored/cached grant →
> immediate revocation (C-3). billing_payments.gateway_transaction_id is the duplicate-payment
> idempotency key. **D-086:** billing models participate in TenantScope (BelongsToTenant); invoice
> numbering is tenant-safe **INV-{TENANT}-{YYYY}-{NNNNNN}** (sequence per tenant+year); webhooks
> reconcile tenant from the referenced subscription. Audit → BILLING_MANAGEMENT (refund/chargeback/
> admin override/cancel/reactivate = HIGH). MembershipTierResolver is a READ-ONLY hook (knowledge_
> tier_grant/research_tier_grant); ContentAccessService is NOT modified (Membership is a separate
> gate). Paystack runs in sandbox (D-083). `module='membership'` plans are the Membership substrate.

> **Wave Membership governance (D-080/D-081/D-082/D-087) — Billing review ACCEPTED 2026-06-05.**
> Membership adds **NO schema** — it is a typed use of this module: an active `billing_subscription`
> to a `module='membership'` plan, whose `knowledge_tier_grant` / `research_tier_grant` columns are
> the content-tier elevation hook. The hook is now ACTIVATED: `MembershipTierResolver` is consulted by
> `ContentAccessService` as **`max(roleTier, membershipTier)`** — ELEVATE-ONLY (C-1), Knowledge/Research
> CONTENT tiers ONLY (C-2: never CRM/portal/marketplace-mod/startup-gov/account/tenant/admin), LIVE
> status (C-3: no cached grant → immediate revocation). Grants are clamped to
> `ics.membership.max_grant_tier` (default 3 — never internal/super). Audit → MEMBERSHIP_MANAGEMENT
> (manual entitlement grant/removal + tenant-wide tier-grant policy = HIGH). Tenant-aware via the same
> TenantScoped billing models (C-4); per-tenant plans + analytics. Validations 1–8 are the GREEN-CI gate.

### billing_plans

```sql
CREATE TABLE billing_plans (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id             BIGINT UNSIGNED NULL,
  name                  VARCHAR(255) NOT NULL,
  slug                  VARCHAR(100) NOT NULL UNIQUE,
  description           TEXT NULL,
  type                  ENUM('subscription','one_time') NOT NULL,
  module                VARCHAR(50) NOT NULL COMMENT 'training|membership|marketplace|consulting|event|research|knowledge',
  billing_period        ENUM('monthly','quarterly','annual','one_time') NOT NULL,
  price                 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  currency              CHAR(3) NOT NULL DEFAULT 'NGN',
  trial_days            TINYINT UNSIGNED NOT NULL DEFAULT 0,
  research_tier_grant   TINYINT UNSIGNED NULL COMMENT 'Research tier elevation if module=research',
  knowledge_tier_grant  TINYINT UNSIGNED NULL COMMENT 'Knowledge tier elevation if module=knowledge',
  features              JSON NULL,
  gateway_plan_id       VARCHAR(100) NULL COMMENT 'Paystack plan code',
  is_active             TINYINT(1) NOT NULL DEFAULT 1,
  sort_order            INT NOT NULL DEFAULT 0,
  created_at            TIMESTAMP NULL,
  updated_at            TIMESTAMP NULL,
  deleted_at            TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### billing_subscriptions

```sql
CREATE TABLE billing_subscriptions (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id               BIGINT UNSIGNED NULL,
  user_id                 BIGINT UNSIGNED NOT NULL,
  plan_id                 BIGINT UNSIGNED NOT NULL,
  status                  ENUM('trial','active','past_due','cancelled','expired')
                          NOT NULL DEFAULT 'trial',
  quantity                SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  trial_ends_at           TIMESTAMP NULL,
  current_period_start    TIMESTAMP NULL,
  current_period_end      TIMESTAMP NULL,
  cancelled_at            TIMESTAMP NULL,
  cancellation_reason     TEXT NULL,
  ends_at                 TIMESTAMP NULL COMMENT 'Scheduled end date',
  gateway_subscription_id VARCHAR(100) NULL,
  gateway_customer_id     VARCHAR(100) NULL,
  gateway_email_token     VARCHAR(100) NULL,
  metadata                JSON NULL,
  created_at              TIMESTAMP NULL,
  updated_at              TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_billing_subs_user (user_id),
  KEY idx_billing_subs_plan (plan_id),
  KEY idx_billing_subs_status (status),
  CONSTRAINT fk_billing_subs_plan FOREIGN KEY (plan_id)
    REFERENCES billing_plans(id)
);
```

---

### billing_invoices

```sql
CREATE TABLE billing_invoices (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  invoice_number   VARCHAR(30) NOT NULL UNIQUE COMMENT 'INV-YYYY-000001',
  user_id          BIGINT UNSIGNED NOT NULL,
  subscription_id  BIGINT UNSIGNED NULL,
  status           ENUM('draft','issued','paid','overdue','cancelled','refunded')
                   NOT NULL DEFAULT 'draft',
  issue_date       DATE NOT NULL,
  due_date         DATE NOT NULL,
  paid_at          TIMESTAMP NULL,
  subtotal         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount_amount  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  tax_amount       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  currency         CHAR(3) NOT NULL DEFAULT 'NGN',
  notes            TEXT NULL,
  pdf_path         VARCHAR(500) NULL,
  sent_at          TIMESTAMP NULL,
  reminder_sent_at TIMESTAMP NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  deleted_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_billing_invoice_number (invoice_number),
  KEY idx_billing_invoices_user (user_id),
  KEY idx_billing_invoices_status (status),
  KEY idx_billing_invoices_due (due_date)
);
```

---

### billing_invoice_items

```sql
CREATE TABLE billing_invoice_items (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  invoice_id       BIGINT UNSIGNED NOT NULL,
  description      VARCHAR(500) NOT NULL,
  quantity         DECIMAL(8,2) NOT NULL DEFAULT 1,
  unit_price       DECIMAL(12,2) NOT NULL,
  subtotal         DECIMAL(12,2) NOT NULL,
  discount_pct     DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  module           VARCHAR(50) NULL,
  billable_type    VARCHAR(100) NULL COMMENT 'polymorphic',
  billable_id      BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_billing_items_invoice (invoice_id),
  CONSTRAINT fk_billing_items_invoice FOREIGN KEY (invoice_id)
    REFERENCES billing_invoices(id) ON DELETE CASCADE
);
```

---

### billing_invoice_sequences

```sql
CREATE TABLE billing_invoice_sequences (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       BIGINT UNSIGNED NULL,
  year            SMALLINT UNSIGNED NOT NULL,
  last_sequence   INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_billing_seq (tenant_id, year)
);
```

---

### billing_payments

```sql
CREATE TABLE billing_payments (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id               BIGINT UNSIGNED NULL,
  invoice_id              BIGINT UNSIGNED NULL,
  user_id                 BIGINT UNSIGNED NOT NULL,
  gateway                 ENUM('paystack','flutterwave','stripe') NOT NULL DEFAULT 'paystack',
  gateway_transaction_id  VARCHAR(100) NOT NULL UNIQUE COMMENT 'Idempotency key',
  gateway_transaction_ref VARCHAR(100) NULL,
  amount                  DECIMAL(12,2) NOT NULL,
  currency                CHAR(3) NOT NULL DEFAULT 'NGN',
  status                  ENUM('pending','success','failed','refunded','chargeback')
                          NOT NULL DEFAULT 'pending',
  payment_method          VARCHAR(50) NULL COMMENT 'card|bank_transfer|ussd|mobile_money',
  channel                 VARCHAR(50) NULL,
  paid_at                 TIMESTAMP NULL,
  gateway_response        JSON NULL,
  created_at              TIMESTAMP NULL,
  updated_at              TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_billing_payment_gw_txn (gateway_transaction_id),
  KEY idx_billing_payments_invoice (invoice_id),
  KEY idx_billing_payments_user (user_id)
);
```

---

### billing_webhooks

```sql
CREATE TABLE billing_webhooks (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  gateway          ENUM('paystack','flutterwave','stripe') NOT NULL,
  event_type       VARCHAR(100) NOT NULL,
  gateway_event_id VARCHAR(100) NULL COMMENT 'Idempotency — gateway event ID',
  payload          JSON NOT NULL,
  signature_valid  TINYINT(1) NOT NULL DEFAULT 0,
  processed        TINYINT(1) NOT NULL DEFAULT 0,
  processed_at     TIMESTAMP NULL,
  error_message    TEXT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_billing_webhooks_gateway (gateway),
  KEY idx_billing_webhooks_processed (processed)
) COMMENT 'Append-only';
```

---

## MODULE 14 — COMMUNITY MODULE

> **Wave 4b governance (D-035/D-057):** CTI base + 6 extensions (1:1, profile_id UNIQUE).
> Access = VISIBILITY (public/authenticated) + OWNER (user_id) — module-local scope, NOT
> ContentAccessible, NOT the four proven mechanisms. W4b-1: cross-module link pointers
> (founder/startup.startup_id, trainer.instructor_id, partner.partner_id, researcher.author_id)
> are internal-only — NEVER serialised, NEVER joined into the linked module (no CRM/portal/
> training/research leak); CommunityProfileResource exposes whitelisted publicFields() only.
> W4b-2: a link is accepted only if the user owns the linked record. W4b-3: consultant
> creation fires ONE-WAY CRM lead capture (consultant never sees the lead). W4b-6:
> views/follows/endorsements = analytics (cached counters); only verify/suspend audited under
> COMMUNITY_MANAGEMENT. The Partner CTI extension model is App\Models\Community\
> PartnerCommunityProfile (named to avoid collision with App\Models\Partner\PartnerProfile).

### community_profiles

```sql
CREATE TABLE community_profiles (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id        BIGINT UNSIGNED NULL,
  user_id          BIGINT UNSIGNED NOT NULL UNIQUE,
  profile_type     ENUM('founder','startup','consultant','trainer','partner','researcher') NOT NULL,
  display_name     VARCHAR(255) NOT NULL,
  tagline          VARCHAR(120) NULL,
  bio              TEXT NULL,
  avatar_path      VARCHAR(500) NULL,
  cover_image_path VARCHAR(500) NULL,
  website_url      VARCHAR(255) NULL,
  location_country CHAR(2) NULL,
  location_city    VARCHAR(100) NULL,
  linkedin_url     VARCHAR(255) NULL,
  twitter_url      VARCHAR(255) NULL,
  visibility       ENUM('public','authenticated') NOT NULL DEFAULT 'public',
  is_verified      TINYINT(1) NOT NULL DEFAULT 0,
  verified_at      TIMESTAMP NULL,
  verified_by      BIGINT UNSIGNED NULL,
  view_count       INT UNSIGNED NOT NULL DEFAULT 0,
  follower_count   INT UNSIGNED NOT NULL DEFAULT 0,
  status           ENUM('active','suspended','hidden') NOT NULL DEFAULT 'active',
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  deleted_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_community_profiles_user (user_id),
  KEY idx_community_profiles_type (profile_type),
  KEY idx_community_profiles_country (location_country),
  KEY idx_community_profiles_verified (is_verified),
  FULLTEXT KEY ft_community_profiles (display_name, tagline, bio)
);
```

---

### community_founder_profiles

```sql
CREATE TABLE community_founder_profiles (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  startup_id       BIGINT UNSIGNED NULL,
  stage            ENUM('idea','mvp','growth','scale','exit') NULL,
  industries       JSON NULL,
  seeking          JSON NULL COMMENT '[funding, mentorship, partnerships, talent]',
  years_experience TINYINT UNSIGNED NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_community_founder (profile_id)
);
```

---

### community_startup_profiles

```sql
CREATE TABLE community_startup_profiles (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  startup_id       BIGINT UNSIGNED NULL,
  founding_year    SMALLINT UNSIGNED NULL,
  team_size        TINYINT UNSIGNED NULL,
  stage            ENUM('idea','mvp','growth','scale','exit') NULL,
  industry         VARCHAR(100) NULL,
  business_model   VARCHAR(50) NULL,
  seeking          JSON NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### community_consultant_profiles

```sql
CREATE TABLE community_consultant_profiles (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  expertise_areas  JSON NULL,
  years_experience TINYINT UNSIGNED NULL,
  certifications   JSON NULL,
  languages        JSON NULL,
  availability     ENUM('available','limited','unavailable') NOT NULL DEFAULT 'available',
  engagement_types JSON NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### community_trainer_profiles

```sql
CREATE TABLE community_trainer_profiles (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  instructor_id    BIGINT UNSIGNED NULL,
  specializations  JSON NULL,
  certifications   JSON NULL,
  delivery_modes   JSON NULL,
  years_experience TINYINT UNSIGNED NULL,
  courses_count    INT UNSIGNED NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### community_partner_profiles

```sql
CREATE TABLE community_partner_profiles (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id         BIGINT UNSIGNED NOT NULL UNIQUE,
  partner_id         BIGINT UNSIGNED NULL,
  organisation_name  VARCHAR(255) NULL,
  partnership_types  JSON NULL,
  service_areas      JSON NULL,
  coverage_regions   JSON NULL,
  created_at         TIMESTAMP NULL,
  updated_at         TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### community_researcher_profiles

```sql
CREATE TABLE community_researcher_profiles (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id         BIGINT UNSIGNED NOT NULL UNIQUE,
  author_id          BIGINT UNSIGNED NULL,
  institution        VARCHAR(255) NULL,
  research_areas     JSON NULL,
  academic_degree    VARCHAR(100) NULL,
  orcid_id           VARCHAR(50) NULL,
  publications_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at         TIMESTAMP NULL,
  updated_at         TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### community_skills

```sql
CREATE TABLE community_skills (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(100) NOT NULL UNIQUE,
  slug         VARCHAR(100) NOT NULL UNIQUE,
  category     VARCHAR(100) NOT NULL COMMENT 'Technology|Consulting|Training|Research|Business',
  created_at   TIMESTAMP NULL,
  PRIMARY KEY (id)
);
```

---

### community_profile_skills

```sql
CREATE TABLE community_profile_skills (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id        BIGINT UNSIGNED NOT NULL,
  skill_id          BIGINT UNSIGNED NOT NULL,
  endorsement_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at        TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_profile_skill (profile_id, skill_id),
  KEY idx_community_ps_skill (skill_id)
);
```

---

### community_endorsements

```sql
CREATE TABLE community_endorsements (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id       BIGINT UNSIGNED NOT NULL,
  skill_id         BIGINT UNSIGNED NOT NULL,
  endorsed_by_id   BIGINT UNSIGNED NOT NULL,
  created_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_endorsement (profile_id, skill_id, endorsed_by_id)
);
```

---

## MODULE 15 — ANALYTICS LAYER (TIER 1)

### analytics_snapshots

```sql
CREATE TABLE analytics_snapshots (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  snapshot_date   DATE NOT NULL,
  active_users    INT UNSIGNED NOT NULL DEFAULT 0,
  new_users       INT UNSIGNED NOT NULL DEFAULT 0,
  total_logins    INT UNSIGNED NOT NULL DEFAULT 0,
  active_leads    INT UNSIGNED NOT NULL DEFAULT 0,
  active_projects INT UNSIGNED NOT NULL DEFAULT 0,
  published_listings INT UNSIGNED NOT NULL DEFAULT 0,
  active_startups INT UNSIGNED NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_analytics_snapshots_date (snapshot_date)
);
```

---

### analytics_revenue_daily

```sql
CREATE TABLE analytics_revenue_daily (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_date     DATE NOT NULL,
  currency        CHAR(3) NOT NULL DEFAULT 'NGN',
  category        VARCHAR(50) NOT NULL,
  gross_revenue   DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  net_revenue     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  refunds         DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  invoice_count   INT UNSIGNED NOT NULL DEFAULT 0,
  payment_count   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_revenue_daily (report_date, currency, category)
);
```

---

### analytics_mrr_snapshots

```sql
CREATE TABLE analytics_mrr_snapshots (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  snapshot_date           DATE NOT NULL,
  currency                CHAR(3) NOT NULL DEFAULT 'NGN',
  mrr                     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  arr                     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  active_subscriptions    INT UNSIGNED NOT NULL DEFAULT 0,
  new_subscriptions       INT UNSIGNED NOT NULL DEFAULT 0,
  cancelled_subscriptions INT UNSIGNED NOT NULL DEFAULT 0,
  trial_subscriptions     INT UNSIGNED NOT NULL DEFAULT 0,
  created_at              TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mrr_snapshots (snapshot_date, currency)
);
```

---

## MODULE 16 — DATA WAREHOUSE (TIER 2)

### dw_dim_date

```sql
CREATE TABLE dw_dim_date (
  date_key      INT NOT NULL COMMENT 'YYYYMMDD format',
  full_date     DATE NOT NULL,
  day_of_week   TINYINT UNSIGNED NOT NULL,
  day_name      VARCHAR(10) NOT NULL,
  week_number   TINYINT UNSIGNED NOT NULL,
  month_number  TINYINT UNSIGNED NOT NULL,
  month_name    VARCHAR(10) NOT NULL,
  quarter       TINYINT UNSIGNED NOT NULL,
  year          SMALLINT UNSIGNED NOT NULL,
  is_weekend    TINYINT(1) NOT NULL DEFAULT 0,
  fiscal_quarter TINYINT UNSIGNED NULL,
  fiscal_year   SMALLINT UNSIGNED NULL,
  PRIMARY KEY (date_key)
) COMMENT 'Pre-populated 2024-2040';
```

---

### dw_dim_user

```sql
CREATE TABLE dw_dim_user (
  user_key        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_user_id  BIGINT UNSIGNED NOT NULL,
  full_name       VARCHAR(255) NOT NULL,
  email           VARCHAR(255) NOT NULL,
  role_name       VARCHAR(100) NOT NULL,
  org_name        VARCHAR(255) NULL,
  country_code    CHAR(2) NULL,
  effective_from  DATE NOT NULL,
  effective_to    DATE NULL COMMENT 'NULL = current record',
  is_current      TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (user_key),
  KEY idx_dw_dim_user_source (source_user_id),
  KEY idx_dw_dim_user_current (is_current)
);
```

---

### dw_fact_revenue

```sql
CREATE TABLE dw_fact_revenue (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  date_key         INT NOT NULL,
  user_key         BIGINT UNSIGNED NULL,
  org_key          BIGINT UNSIGNED NULL,
  module_key       BIGINT UNSIGNED NULL,
  currency_key     BIGINT UNSIGNED NULL,
  source_type      VARCHAR(50) NOT NULL,
  source_id        BIGINT UNSIGNED NOT NULL,
  gross_amount_ngn DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  net_amount_ngn   DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  loaded_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_dw_fact_rev_date (date_key),
  KEY idx_dw_fact_rev_user (user_key)
);
```

---

### dw_etl_runs

```sql
CREATE TABLE dw_etl_runs (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  command          VARCHAR(100) NOT NULL,
  started_at       TIMESTAMP NOT NULL,
  completed_at     TIMESTAMP NULL,
  rows_extracted   INT UNSIGNED NOT NULL DEFAULT 0,
  rows_loaded      INT UNSIGNED NOT NULL DEFAULT 0,
  rows_skipped     INT UNSIGNED NOT NULL DEFAULT 0,
  status           ENUM('running','success','failed') NOT NULL DEFAULT 'running',
  error_message    TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_dw_etl_runs_status (status)
);
```

---

## SYSTEM TABLES

### sys_jobs

```sql
CREATE TABLE sys_jobs (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  queue          VARCHAR(255) NOT NULL,
  payload        LONGTEXT NOT NULL,
  attempts       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  reserved_at    INT UNSIGNED NULL,
  available_at   INT UNSIGNED NOT NULL,
  created_at     INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_sys_jobs_queue (queue, reserved_at, available_at)
);
```

---

### sys_failed_jobs

```sql
CREATE TABLE sys_failed_jobs (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid           VARCHAR(36) NOT NULL UNIQUE,
  connection     TEXT NOT NULL,
  queue          TEXT NOT NULL,
  payload        LONGTEXT NOT NULL,
  exception      LONGTEXT NOT NULL,
  failed_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);
```

---

### sys_sessions

```sql
CREATE TABLE sys_sessions (
  id            VARCHAR(255) NOT NULL,
  user_id       BIGINT UNSIGNED NULL,
  ip_address    VARCHAR(45) NULL,
  user_agent    TEXT NULL,
  payload       LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  PRIMARY KEY (id),
  KEY idx_sys_sessions_user (user_id),
  KEY idx_sys_sessions_activity (last_activity)
);
```

---

### sys_cache

```sql
CREATE TABLE sys_cache (
  key        VARCHAR(255) NOT NULL,
  value      MEDIUMTEXT NOT NULL,
  expiration INT NOT NULL,
  PRIMARY KEY (key)
);
```

---

## INTERNATIONALISATION

### i18n_translations

```sql
CREATE TABLE i18n_translations (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  translatable_type VARCHAR(100) NOT NULL COMMENT 'Model class',
  translatable_id   BIGINT UNSIGNED NOT NULL,
  locale            VARCHAR(10) NOT NULL COMMENT 'en|fr|ar',
  field             VARCHAR(100) NOT NULL COMMENT 'Column being translated',
  value             LONGTEXT NOT NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_i18n_translations (translatable_type, translatable_id, locale, field),
  KEY idx_i18n_locale (locale)
);
```

---

## FULL TABLE INVENTORY

| Module Prefix | Table Count |
|---|---|
| core_ | 5 + 5 Spatie/Sanctum + notifications |
| crm_ | 7 |
| training_ | 10 |
| marketplace_ | 4 |
| partner_ | 4 |
| startup_ | 6 |
| client_ | 5 |
| content_ | 3 |
| knowledge_ | 9 |
| research_ | 6 |
| ai_ | 3 |
| notify_ | 2 |
| billing_ | 7 |
| community_ | 10 |
| analytics_ | 3 (core subset — full set per §10 of Blueprint) |
| dw_ | 3 (core subset — full set per §19 of Blueprint) |
| sys_ | 4 |
| i18n_ | 1 |
| **TOTAL** | **~119** |

---

## SCALABILITY RISKS

| Table | Risk | Mitigation |
|---|---|---|
| core_audit_logs | Unbounded append-only growth | Partition by year (Phase 2); archive to cold storage (Phase 3) |
| knowledge_views / research_downloads | High-frequency appends | Bulk insert via batch jobs; partition by month Phase 2 |
| ai_requests | Cost tracking at volume | Indexed by created_at; daily aggregation offloads dashboard queries |
| marketplace_listings FULLTEXT | Search degrades at scale | Meilisearch Phase 2 replaces FULLTEXT |
| dw_fact_* | ETL duration grows with data | Delta loads; dedicated MySQL connection; read replica Phase 2 |
| sys_jobs | Queue backlog under load | Redis + Horizon Phase 2 eliminates MySQL queue entirely |

---

## SECURITY NOTES

| Concern | Control |
|---|---|
| PII in core_users | mfa_secret encrypted at rest; password is bcrypt hash |
| Cross-tenant data leakage | tenant_id on all tables + TenantScope (Phase 3 active) |
| Audit log tampering | MySQL trigger blocks UPDATE/DELETE on core_audit_logs |
| Billing data | Card data never stored — Paystack only; billing_payments stores reference only |
| File paths in DB | Paths are relative (never absolute); served via authenticated controller |

---

## APPROVAL SECTION

| Role | Name | Signature | Date |
|---|---|---|---|
| Platform Owner | | | |
| Lead Architect | | | |
| Database Architect | | | |
| Security Officer | | | |

**Status:** Awaiting Review and Approval
**Gate:** This blueprint must be approved before the first migration file is written.
Every migration must reference a table defined here.
