# USER ROLE MATRIX
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: Awaiting Approval
Author: Chief Enterprise Architect

Decision References: D-021 (RBAC Architecture), D-004 (Multi-Tenancy), D-006 (Compliance)

---

## EXECUTIVE SUMMARY

This document defines all 14 platform roles, their scope, purpose, permissions
summary, module access, creation authority, MFA requirements, and session
behaviour. All roles are implemented via Spatie Laravel-Permission (D-021).

All roles default to zero permissions. Permissions are additive only.
The principle of least privilege is enforced across all roles.
All authorization is enforced server-side. Frontend visibility is decorative only.

Total Roles: 14
Tiers: Platform (5) | Organization (4) | Individual (5)

---

## ROLE TIER OVERVIEW

```
TIER 1 — PLATFORM (ICS Internal)
  R-01  Platform Super Admin
  R-02  Platform Admin
  R-03  ICS Staff — CRM
  R-04  ICS Staff — Training
  R-05  ICS Staff — Content

TIER 2 — ORGANIZATION (External Managed Entities)
  R-06  Client Admin
  R-07  Partner Admin
  R-08  Government Agency Representative
  R-09  Vendor

TIER 3 — INDIVIDUAL
  R-10  Startup Founder
  R-11  Startup Team Member
  R-12  Trainer / Instructor
  R-13  Student / Trainee
  R-14  Guest (Public)
```

---

## R-01 — PLATFORM SUPER ADMIN

| Attribute | Value |
|---|---|
| Role ID | R-01 |
| Role Name | Platform Super Admin |
| Spatie Role Slug | `platform-super-admin` |
| Tier | Platform |
| Scope | Global — all modules, all tenants, all records |
| Purpose | Unrestricted platform ownership and configuration. Reserved for ICS leadership only. |
| Creation Authority | Cannot be self-assigned. Created only by direct database seeding or by another Super Admin. |
| Max Instances | 2–3 (ICS leadership only) |
| MFA | **Required — TOTP mandatory** |
| Session Duration | 8 hours (no remember-me) |
| API Access | Full |
| Tenant Scope | All tenants including ICS default |
| Audit Logging | All actions logged |

**Module Access:**

| Module | Access Level |
|---|---|
| Corporate Website / CMS | Full manage |
| CRM | Full manage |
| Client Portal | Full manage |
| Training Institute | Full manage |
| Partner Portal | Full manage |
| Startup Hub | Full manage |
| Opportunity Marketplace | Full manage + approve |
| Knowledge Center | Full manage + publish all tiers |
| Research Center | Full access — all tiers including draft |
| AI Services | Full access |
| Community Module | Full manage + verify |
| Billing & Subscriptions | Full manage |
| Analytics + Data Warehouse | Full access |
| Platform Configuration | Full access |
| Audit Logs | Full read access |
| User Management | Full manage — all users |

**Security Notes:**
- Account creation must be manually audited
- Login events trigger immediate email alert to primary ICS email
- Idle session timeout: 30 minutes
- Accounts must be reviewed quarterly

---

## R-02 — PLATFORM ADMIN

| Attribute | Value |
|---|---|
| Role ID | R-02 |
| Role Name | Platform Admin |
| Spatie Role Slug | `platform-admin` |
| Tier | Platform |
| Scope | Global — all modules, operational management |
| Purpose | Day-to-day platform operations. Full operational access excluding core system configuration. |
| Creation Authority | Platform Super Admin only |
| Max Instances | Unlimited (ICS management team) |
| MFA | **Required — TOTP mandatory** |
| Session Duration | 8 hours |
| API Access | Full operational |
| Audit Logging | All actions logged |

**Module Access:**

| Module | Access Level |
|---|---|
| Corporate Website / CMS | Full manage |
| CRM | Full manage |
| Client Portal | Full manage |
| Training Institute | Full manage |
| Partner Portal | Full manage |
| Startup Hub | Full manage |
| Opportunity Marketplace | Full manage + approve |
| Knowledge Center | Full manage + publish all tiers |
| Research Center | Full access — Tiers 1–4; Tier 5 read only |
| AI Services | Full access |
| Community Module | Full manage + verify |
| Billing & Subscriptions | Full manage |
| Analytics + Data Warehouse | Full access |
| Platform Configuration | Read only (no structural changes) |
| Audit Logs | Full read access |
| User Management | Create/edit/deactivate (cannot manage Super Admin) |

**Distinction from Super Admin:** Cannot modify core platform configuration, cannot create Super Admin accounts, cannot access Tier 5 internal research content for editing.

---

## R-03 — ICS STAFF — CRM

| Attribute | Value |
|---|---|
| Role ID | R-03 |
| Role Name | ICS Staff — CRM |
| Spatie Role Slug | `ics-staff-crm` |
| Tier | Platform |
| Scope | CRM module + limited cross-module read |
| Purpose | Manage leads, opportunities, accounts, contracts, and client relationships |
| Creation Authority | Platform Admin or Super Admin |
| MFA | Optional (recommended) |
| Session Duration | 8 hours |
| API Access | CRM module only |

**Module Access:**

| Module | Access Level |
|---|---|
| CRM | Full manage (own records) |
| Client Portal | Read (project and client data for service delivery) |
| Opportunity Marketplace | Post listings |
| Knowledge Center | Tier 2 read + Tier 5 draft for content creation |
| Research Center | Tier 4 internal access |
| AI Services | Lead Qualification, Proposal Generation, Digital Maturity Assessment |
| Analytics | CRM reports only |
| Community Module | Read public profiles; verify on request |
| All other modules | No access |

---

## R-04 — ICS STAFF — TRAINING

| Attribute | Value |
|---|---|
| Role ID | R-04 |
| Role Name | ICS Staff — Training |
| Spatie Role Slug | `ics-staff-training` |
| Tier | Platform |
| Scope | Training Institute + supporting modules |
| Purpose | Manage courses, enrollments, assessments, certifications, and instructors |
| Creation Authority | Platform Admin or Super Admin |
| MFA | Optional |
| Session Duration | 8 hours |

**Module Access:**

| Module | Access Level |
|---|---|
| Training Institute | Full manage (all courses, enrollments, assessments) |
| Knowledge Center | Tier 5 internal + publish Training Resources (Tier 2) |
| Research Center | Tier 4 internal access |
| AI Services | Training Recommendations, Content Drafting |
| Analytics | Training reports only |
| Community | Read trainer profiles |
| All other modules | No access |

---

## R-05 — ICS STAFF — CONTENT

| Attribute | Value |
|---|---|
| Role ID | R-05 |
| Role Name | ICS Staff — Content |
| Spatie Role Slug | `ics-staff-content` |
| Tier | Platform |
| Scope | Corporate Website, Knowledge Center, Research Center |
| Purpose | Create, review, and publish platform content across all public-facing content modules |
| Creation Authority | Platform Admin or Super Admin |
| MFA | Optional |
| Session Duration | 8 hours |

**Module Access:**

| Module | Access Level |
|---|---|
| Corporate Website / CMS | Full manage — pages, articles, media |
| Knowledge Center | Full manage — all tiers including draft and internal |
| Research Center | Full manage — all tiers including Tier 4 internal |
| AI Services | Content Drafting, Knowledge Search, Research Assistant |
| Analytics | Content performance reports |
| Community | Read public profiles |
| All other modules | No access |

---

## R-06 — CLIENT ADMIN

| Attribute | Value |
|---|---|
| Role ID | R-06 |
| Role Name | Client Admin |
| Spatie Role Slug | `client-admin` |
| Tier | Organization |
| Scope | Own organization data only |
| Purpose | Manage their organization's access to the Client Portal and add team members |
| Creation Authority | Platform Admin or ICS CRM Staff (upon contract signature) |
| MFA | Optional (recommended for government clients) |
| Session Duration | 24 hours (remember-me: 30 days) |
| Tenant Scope | Own organization (crm_account) |

**Module Access:**

| Module | Access Level |
|---|---|
| Client Portal | Full access (own org only) — projects, deliverables, invoices, tickets |
| Knowledge Center | Tier 1 + Tier 2 + Tier 3 (client resources) |
| Research Center | Tier 1 + Tier 2 |
| Training Institute | Enrol and complete courses |
| Opportunity Marketplace | Browse and apply |
| Community Module | Create and manage own profile |
| AI Services | Training Recommendations, Opportunity Matching |
| All other modules | No access |

**Security Note:** Strict account/organization scoping enforced at application layer. Client Admin cannot see other clients' data under any circumstances.

---

## R-07 — PARTNER ADMIN

| Attribute | Value |
|---|---|
| Role ID | R-07 |
| Role Name | Partner Admin |
| Spatie Role Slug | `partner-admin` |
| Tier | Organization |
| Scope | Own partner organization data |
| Purpose | Manage partner profile, submit referrals, access partner resources, post opportunities |
| Creation Authority | Platform Admin (upon partner approval) |
| MFA | Optional |
| Session Duration | 24 hours |

**Module Access:**

| Module | Access Level |
|---|---|
| Partner Portal | Full access (own org only) |
| Knowledge Center | Tier 1 + Tier 2 + Tier 4 (partner resources) |
| Research Center | Tier 1 + Tier 2 + Tier 3 (partner publications) |
| Opportunity Marketplace | Post + browse + apply |
| Training Institute | Enrol and complete courses |
| Community Module | Create and manage own profile |
| AI Services | Training Recommendations, Opportunity Matching, Knowledge Search |
| All other modules | No access |

---

## R-08 — GOVERNMENT AGENCY REPRESENTATIVE

| Attribute | Value |
|---|---|
| Role ID | R-08 |
| Role Name | Government Agency Representative |
| Spatie Role Slug | `government-agency-rep` |
| Tier | Organization |
| Scope | Assigned agency + platform public content |
| Purpose | Enable government agency staff to access training, post opportunities, and engage with ICS knowledge resources |
| Creation Authority | Platform Admin |
| MFA | Optional (recommended — government security standards) |
| Session Duration | 24 hours |

**Module Access:**

| Module | Access Level |
|---|---|
| Knowledge Center | Tier 1 + Tier 2 (D-044/EP-2: Tier 4 is partner-specific; removed for Gov Rep) |
| Research Center | Tier 1 + Tier 2 + Tier 3 |
| Training Institute | Enrol and complete courses |
| Opportunity Marketplace | Post + browse + apply |
| Community Module | Create and manage own profile |
| AI Services | Training Recommendations, Opportunity Matching, Knowledge Search |
| All other modules | No access |

---

## R-09 — VENDOR

| Attribute | Value |
|---|---|
| Role ID | R-09 |
| Role Name | Vendor |
| Spatie Role Slug | `vendor` |
| Tier | Organization |
| Scope | Marketplace + public platform content |
| Purpose | Post service listings and respond to marketplace opportunities |
| Creation Authority | Platform Admin (upon vendor approval) |
| MFA | Not required |
| Session Duration | 24 hours |

**Module Access:**

| Module | Access Level |
|---|---|
| Opportunity Marketplace | Post listings + browse + apply |
| Knowledge Center | Tier 1 + Tier 2 |
| Research Center | Tier 1 + Tier 2 |
| Community Module | Create and manage own profile |
| All other modules | No access |

---

## R-10 — STARTUP FOUNDER

| Attribute | Value |
|---|---|
| Role ID | R-10 |
| Role Name | Startup Founder |
| Spatie Role Slug | `startup-founder` |
| Tier | Individual |
| Scope | Own startup + platform ecosystem resources |
| Purpose | Register and manage a startup, track progress, access support resources |
| Creation Authority | Self-registration (platform approval workflow) |
| MFA | Not required |
| Session Duration | 24 hours |

**Module Access:**

| Module | Access Level |
|---|---|
| Startup Hub | Full manage (own startup only) |
| Knowledge Center | Tier 1 + Tier 2 |
| Research Center | Tier 1 + Tier 2 |
| Training Institute | Enrol and complete courses |
| Opportunity Marketplace | Browse and apply |
| Community Module | Create and manage Founder + Startup profiles |
| AI Services | Startup Readiness Assessment, Training Recommendations, Opportunity Matching |
| All other modules | No access |

---

## R-11 — STARTUP TEAM MEMBER

| Attribute | Value |
|---|---|
| Role ID | R-11 |
| Role Name | Startup Team Member |
| Spatie Role Slug | `startup-member` |
| Tier | Individual |
| Scope | Own startup (read + contribute, no manage) |
| Purpose | Contribute to a startup without ownership-level access |
| Creation Authority | Startup Founder (invite) |
| MFA | Not required |
| Session Duration | 24 hours |

**Module Access:**

| Module | Access Level |
|---|---|
| Startup Hub | Read + contribute (no profile management, no milestone delete) |
| Knowledge Center | Tier 1 + Tier 2 |
| Training Institute | Enrol and complete courses |
| Opportunity Marketplace | Browse and apply |
| Community Module | Create own profile |
| All other modules | No access |

---

## R-12 — TRAINER / INSTRUCTOR

| Attribute | Value |
|---|---|
| Role ID | R-12 |
| Role Name | Trainer / Instructor |
| Spatie Role Slug | `trainer` |
| Tier | Individual |
| Scope | Own courses within Training Institute |
| Purpose | Create and deliver course content, manage assessments |
| Creation Authority | ICS Training Staff (must be manually approved) |
| MFA | Not required |
| Session Duration | 24 hours |

**Module Access:**

| Module | Access Level |
|---|---|
| Training Institute | Create and manage own courses + deliver assessments |
| Knowledge Center | Publish Training Resources (Tier 2) — with approval workflow |
| Community Module | Create and manage Trainer profile |
| AI Services | Content Drafting, Training Recommendations |
| All other modules | No access |

**Security Note:** Trainer content is subject to ICS review and approval. No content auto-published without ICS Training Staff approval.

---

## R-13 — STUDENT / TRAINEE

| Attribute | Value |
|---|---|
| Role ID | R-13 |
| Role Name | Student / Trainee |
| Spatie Role Slug | `student` |
| Tier | Individual |
| Scope | Enrolled courses + platform public content |
| Purpose | Access and complete enrolled training courses |
| Creation Authority | Self-registration |
| MFA | Not required |
| Session Duration | 24 hours (remember-me: 30 days) |

**Module Access:**

| Module | Access Level |
|---|---|
| Training Institute | Access enrolled courses only |
| Knowledge Center | Tier 1 + Tier 2 |
| Research Center | Tier 1 + Tier 2 |
| Opportunity Marketplace | Browse and apply |
| Community Module | Create own profile |
| AI Services | Training Recommendations, Knowledge Search |
| All other modules | No access |

---

## R-14 — GUEST (PUBLIC)

| Attribute | Value |
|---|---|
| Role ID | R-14 |
| Role Name | Guest |
| Spatie Role Slug | N/A (unauthenticated) |
| Tier | Individual |
| Scope | Public content only |
| Purpose | Browse public-facing platform content; represents all unauthenticated visitors |
| Creation Authority | N/A — no account required |
| MFA | N/A |
| Session Duration | N/A |

**Module Access:**

| Module | Access Level |
|---|---|
| Corporate Website | Full public read |
| Knowledge Center | Tier 1 content only (articles, news, public guides, case studies) |
| Research Center | Tier 1 content only (summaries, briefs) |
| Opportunity Marketplace | Browse published listings only |
| Community Directory | Browse public profiles only |
| All other modules | No access |

---

## ROLE COMPARISON SUMMARY

| Role | CRM | Training | Marketplace | Knowledge | Research | Billing | Analytics | AI |
|---|---|---|---|---|---|---|---|---|
| Super Admin | Full | Full | Full | All Tiers | All Tiers | Full | Full | Full |
| Platform Admin | Full | Full | Full | All Tiers | T1–T4 | Full | Full | Full |
| ICS CRM Staff | Full | — | Post | T2+T5 | T4 | — | CRM Only | 3 uses |
| ICS Training Staff | — | Full | — | T2+T5 | T4 | — | Training Only | 2 uses |
| ICS Content Staff | — | — | — | Full | T1–T4 | — | Content Only | 3 uses |
| Client Admin | — | Enrol | Browse | T1–T3 | T1–T2 | View invoices | — | 2 uses |
| Partner Admin | Partner | Enrol | Post+Apply | T1–T4 | T1–T3 | — | — | 3 uses |
| Gov Agency Rep | — | Enrol | Post+Apply | T1–T2 | T1–T3 | — | — | 3 uses |
| Vendor | — | — | Post+Apply | T1–T2 | T1–T2 | — | — | — |
| Startup Founder | — | Enrol | Apply | T1–T2 | T1–T2 | — | — | 3 uses |
| Startup Member | — | Enrol | Apply | T1–T2 | T1–T2 | — | — | — |
| Trainer | — | Own courses | — | T2 (publish) | — | — | — | 2 uses |
| Student | — | Enrolled only | Apply | T1–T2 | T1–T2 | — | — | 2 uses |
| Guest | — | — | Browse | T1 | T1 | — | — | — |

---

## ROLE LIFECYCLE

```
Creation:
  Super Admin → Created by DB seed or another Super Admin
  Platform Staff → Created by Platform Admin
  Org roles (Client, Partner, Gov, Vendor) → Created by Platform Admin after approval
  Individual roles (Founder, Member, Trainer, Student) → Self-registration + approval

Modification:
  Role changes take effect on next session refresh (not mid-session)
  Role escalation (to any ICS Staff or above) requires Platform Admin approval

Deactivation:
  User account status → suspended: access immediately revoked
  Deleted accounts: soft-delete; data retained per retention policy (D-006)
  Personal access tokens revoked on account suspension (Sanctum)

Review Cycle:
  Super Admin accounts: quarterly audit
  Platform Staff accounts: semi-annual audit
  Org accounts: annual review or on contract/agreement expiry
```

---

## SECURITY IMPLICATIONS

| Concern | Control |
|---|---|
| Privilege escalation | Role changes require Platform Admin approval; audit logged |
| Orphaned accounts | Deactivation workflow enforced on contract expiry |
| Shared accounts | Prohibited — one account per human user enforced |
| Guest data capture | Guests leave no PII footprint; analytics use anonymised session data |
| NDPA compliance | User consent recorded at registration; deletion workflow per D-006 |

---

## FUTURE EXPANSION POINTS

| Future Module | Role Impact |
|---|---|
| Membership System (D-019) | New billing-linked access elevation — no new role required |
| Investment Network (D-019) | New role: Investor (legal review complete first) |
| Franchise Operations (D-019) | New role: Franchise Admin (tenant-scoped Super Admin equivalent) |
| Events Module | No new role — existing roles receive event registration permission |
| Vendor Marketplace (D-019) | Extends existing Vendor role scope |

---

## APPROVAL SECTION

| Role | Name | Signature | Date |
|---|---|---|---|
| Platform Owner | | | |
| Lead Architect | | | |
| Security Officer | | | |

**Status:** Awaiting Review and Approval
**Gate:** This document must be approved before RBAC implementation (D-021) begins.
