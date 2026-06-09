# PERMISSION MATRIX
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: Awaiting Approval
Author: Chief Enterprise Architect

Decision References: D-021 (RBAC), D-034 (Research Tiers), D-036 (Knowledge Tiers)

---

## EXECUTIVE SUMMARY

This document is the definitive cross-reference of every permission against every
role across every platform module. It is the authoritative source for implementing
the Spatie Laravel-Permission configuration (D-021).

All permissions are enforced server-side only. No frontend rendering decision
constitutes an authorization control.

Permission Notation:
  ✓   = Full access
  O   = Own records only
  R   = Read only
  P   = Post/Submit only
  A   = Approve/Publish only
  T1–T5 = Tiered content access level
  —   = No access

Role Column Headers (abbreviated):
  SU  = Platform Super Admin (R-01)
  PA  = Platform Admin (R-02)
  CS  = ICS Staff — CRM (R-03)
  TS  = ICS Staff — Training (R-04)
  CC  = ICS Staff — Content (R-05)
  CA  = Client Admin (R-06)
  PR  = Partner Admin (R-07)
  GA  = Government Agency Rep (R-08)
  VN  = Vendor (R-09)
  SF  = Startup Founder (R-10)
  SM  = Startup Member (R-11)
  TR  = Trainer (R-12)
  ST  = Student (R-13)
  GU  = Guest (R-14)

---

## MODULE 1 — PLATFORM ADMINISTRATION

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| platform.config.read | ✓ | R | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.config.update | ✓ | — | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.users.create | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.users.read.all | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.users.update.all | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.users.deactivate | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.users.delete | ✓ | — | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.roles.manage | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.audit.read | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.audit.export | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| platform.tenants.manage | ✓ | — | — | — | — | — | — | — | — | — | — | — | — | — |

---

## MODULE 2 — AUTHENTICATION & PROFILE

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| auth.login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| auth.logout | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| profile.read.own | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| profile.update.own | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| profile.data.export | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| profile.delete.own | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| profile.mfa.manage | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| notifications.read.own | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| notifications.preferences | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |

---

## MODULE 3 — CORPORATE WEBSITE / CMS

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| cms.pages.create | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| cms.pages.read | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| cms.pages.update | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| cms.pages.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| cms.pages.publish | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| cms.articles.create | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| cms.articles.update | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| cms.articles.publish | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| cms.articles.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| cms.media.upload | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| cms.media.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| cms.menu.manage | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| cms.read.public | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## MODULE 4 — CRM

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| crm.accounts.create | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.accounts.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.accounts.update | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.accounts.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| crm.contacts.create | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.contacts.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.contacts.update | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.contacts.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| crm.leads.create | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.leads.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.leads.update | ✓ | ✓ | O | — | — | — | — | — | — | — | — | — | — | — |
| crm.leads.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| crm.leads.qualify.ai | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.opportunities.create | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.opportunities.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.opportunities.update | ✓ | ✓ | O | — | — | — | — | — | — | — | — | — | — | — |
| crm.opportunities.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| crm.proposals.create | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.proposals.generate.ai | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.proposals.read | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.proposals.update | ✓ | ✓ | O | — | — | — | — | — | — | — | — | — | — | — |
| crm.contracts.create | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.contracts.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.contracts.update | ✓ | ✓ | O | — | — | — | — | — | — | — | — | — | — | — |
| crm.contracts.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| crm.activities.create | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.activities.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.reports.view | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| crm.reports.export | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |

---

## MODULE 5 — CLIENT PORTAL

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| client.projects.read.own | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| client.projects.manage | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| client.milestones.read | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| client.milestones.manage | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| client.deliverables.read.own | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| client.deliverables.manage | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| client.deliverables.download | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| client.invoices.read.own | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| client.invoices.download | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| client.tickets.create | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| client.tickets.read.own | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| client.tickets.reply | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| client.tickets.manage | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| client.users.manage.own | ✓ | ✓ | — | — | — | ✓ | — | — | — | — | — | — | — | — |

---

## MODULE 6 — TRAINING INSTITUTE

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| training.courses.create | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | ✓ | — | — |
| training.courses.read.all | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | O | — | — |
| training.courses.update | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | O | — | — |
| training.courses.delete | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | — | — | — |
| training.courses.publish | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | — | — | — |
| training.courses.read.catalogue | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| training.enrollments.create | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | — |
| training.enrollments.read.all | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | O | — | — |
| training.enrollments.read.own | ✓ | ✓ | — | ✓ | — | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | ✓ | — |
| training.lessons.access.enrolled | ✓ | ✓ | — | ✓ | — | ✓ | ✓ | ✓ | — | ✓ | ✓ | O | ✓ | — |
| training.assessments.submit | ✓ | ✓ | — | — | — | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | ✓ | — |
| training.assessments.grade | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | O | — | — |
| training.certificates.issue | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | — | — | — |
| training.certificates.read.own | ✓ | ✓ | — | ✓ | — | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| training.certificates.verify | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| training.instructors.manage | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | — | — | — |
| training.reports.view | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | — | — | — |

---

## MODULE 7 — OPPORTUNITY MARKETPLACE

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| marketplace.listings.create | ✓ | ✓ | ✓ | — | — | — | ✓ | ✓ | ✓ | — | — | — | — | — |
| marketplace.listings.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| marketplace.listings.read.published | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| marketplace.listings.update.own | ✓ | ✓ | O | — | — | — | O | O | O | — | — | — | — | — |
| marketplace.listings.delete.own | ✓ | ✓ | — | — | — | — | O | O | O | — | — | — | — | — |
| marketplace.listings.approve | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| marketplace.listings.reject | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| marketplace.applications.create | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| marketplace.applications.read.own | ✓ | ✓ | O | — | — | O | O | O | O | O | O | — | O | — |
| marketplace.applications.read.all | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| marketplace.reports.view | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |

---

## MODULE 8 — PARTNER PORTAL

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| partner.profiles.create | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| partner.profiles.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| partner.profiles.read.own | ✓ | ✓ | ✓ | — | — | — | ✓ | — | — | — | — | — | — | — |
| partner.profiles.update | ✓ | ✓ | ✓ | — | — | — | O | — | — | — | — | — | — | — |
| partner.profiles.approve | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| partner.referrals.create | ✓ | ✓ | — | — | — | — | ✓ | — | — | — | — | — | — | — |
| partner.referrals.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| partner.referrals.read.own | ✓ | ✓ | — | — | — | — | ✓ | — | — | — | — | — | — | — |
| partner.agreements.manage | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| partner.agreements.read.own | ✓ | ✓ | — | — | — | — | ✓ | — | — | — | — | — | — | — |
| partner.tiers.manage | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| partner.reports.view | ✓ | ✓ | ✓ | — | — | — | O | — | — | — | — | — | — | — |

---

## MODULE 9 — STARTUP HUB

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| startup.profiles.create | ✓ | ✓ | — | — | — | — | — | — | — | ✓ | — | — | — | — |
| startup.profiles.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| startup.profiles.read.own | ✓ | ✓ | ✓ | — | — | — | — | — | — | ✓ | ✓ | — | — | — |
| startup.profiles.update.own | ✓ | ✓ | — | — | — | — | — | — | — | ✓ | — | — | — | — |
| startup.profiles.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| startup.milestones.create | ✓ | ✓ | ✓ | — | — | — | — | — | — | ✓ | ✓ | — | — | — |
| startup.milestones.update | ✓ | ✓ | ✓ | — | — | — | — | — | — | ✓ | — | — | — | — |
| startup.milestones.delete | ✓ | ✓ | ✓ | — | — | — | — | — | — | ✓ | — | — | — | — |
| startup.team.manage | ✓ | ✓ | — | — | — | — | — | — | — | ✓ | — | — | — | — |
| startup.mentors.manage | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| startup.programs.manage | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| startup.reports.view | ✓ | ✓ | ✓ | — | — | — | — | — | — | O | — | — | — | — |
| startup.assessment.ai | ✓ | ✓ | ✓ | — | — | — | — | — | — | ✓ | — | — | — | — |

---

## MODULE 10 — KNOWLEDGE CENTER (Tiered — D-036)

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| knowledge.tier1.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| knowledge.tier2.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| knowledge.tier3.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — |
| knowledge.tier4.read | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — |
| knowledge.tier5.read | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — |
| knowledge.articles.create | ✓ | ✓ | — | ✓ | ✓ | — | — | — | — | — | — | R | — | — |
| knowledge.articles.update.own | ✓ | ✓ | — | ✓ | ✓ | — | — | — | — | — | — | O | — | — |
| knowledge.articles.publish | ✓ | ✓ | — | ✓ | ✓ | — | — | — | — | — | — | — | — | — |
| knowledge.articles.delete | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| knowledge.bookmarks.manage.own | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| knowledge.ratings.create | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| knowledge.downloads.access | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| knowledge.search.ai | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| knowledge.reports.view | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |

---

## MODULE 11 — RESEARCH CENTER (Tiered — D-034)

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| research.tier1.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| research.tier2.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| research.tier3.read | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — | — | — | — | — |
| research.tier4.read | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — |
| research.tier5.read | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| research.publications.create | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| research.publications.update | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| research.publications.publish | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| research.publications.delete | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| research.downloads.access | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| research.citations.generate | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| research.reports.view | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |

---

## MODULE 12 — AI SERVICES

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| ai.website.assistant | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| ai.crm.lead.qualify | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| ai.crm.proposal.generate | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| ai.training.recommend | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | — | ✓ | ✓ | — |
| ai.knowledge.search | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| ai.research.assist | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | ✓ | — |
| ai.marketplace.match | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | — |
| ai.startup.readiness | ✓ | ✓ | ✓ | — | — | — | — | — | — | ✓ | — | — | — | — |
| ai.digital.maturity | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| ai.content.draft | ✓ | ✓ | — | ✓ | ✓ | — | — | — | — | — | — | ✓ | — | — |
| ai.usage.view | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| ai.usage.manage | ✓ | — | — | — | — | — | — | — | — | — | — | — | — | — |

---

## MODULE 13 — COMMUNITY MODULE

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| community.directory.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| community.profile.create.own | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| community.profile.update.own | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| community.profile.delete.own | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| community.profile.verify | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| community.profile.suspend | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| community.skills.endorse | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| community.profile.read.all | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — |

---

## MODULE 14 — BILLING & SUBSCRIPTIONS

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| billing.plans.manage | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| billing.invoices.create | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| billing.invoices.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| billing.invoices.read.own | ✓ | ✓ | ✓ | — | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — |
| billing.invoices.download | ✓ | ✓ | ✓ | — | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — |
| billing.payments.read.all | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| billing.payments.read.own | ✓ | ✓ | ✓ | — | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — |
| billing.subscriptions.manage | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| billing.subscriptions.read.own | ✓ | ✓ | — | — | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — |
| billing.subscriptions.cancel.own | ✓ | ✓ | — | — | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — |
| billing.reports.view | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| billing.reports.export | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |

---

## MODULE 15 — ANALYTICS & DATA WAREHOUSE

| Permission | SU | PA | CS | TS | CC | CA | PR | GA | VN | SF | SM | TR | ST | GU |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| analytics.executive.dashboard | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| analytics.crm.reports | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| analytics.training.reports | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | — | — | — |
| analytics.marketplace.reports | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| analytics.partner.reports | ✓ | ✓ | ✓ | — | — | — | O | — | — | — | — | — | — | — |
| analytics.content.reports | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — | — |
| analytics.finance.reports | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| analytics.warehouse.read | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |
| analytics.export | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — | — |

---

## PERMISSION CONFLICT ANALYSIS

| Risk | Affected Permissions | Mitigation |
|---|---|---|
| CRM Staff overreach | crm.*.read.all exposes all leads | Scoped by assigned_to where relevant |
| Content Staff training access | training.courses.* — not granted | CC cannot access training module |
| Partner self-approval | partner.profiles.approve not granted to PR | Approval is ICS-only |
| Trainer self-publish | knowledge.articles.publish not granted to TR | Trainer submits; Content Staff publishes |
| Client cross-account | All client.* checks include account_id match | Policy enforces account scoping |

---

## FUTURE EXPANSION PERMISSIONS

| Future Module | New Permissions Required |
|---|---|
| Membership System | billing.membership.subscribe, billing.membership.cancel |
| Investment Network | invest.profiles.create, invest.deals.view, invest.deals.create |
| Franchise Operations | franchise.tenants.manage, franchise.config.manage |
| Events Module | events.create, events.register, events.attend |
| Vendor Marketplace | vendor.listings.manage (extends marketplace permissions) |

---

## APPROVAL SECTION

| Role | Name | Signature | Date |
|---|---|---|---|
| Platform Owner | | | |
| Lead Architect | | | |
| Security Officer | | | |
| Technical Lead | | | |

**Status:** Awaiting Review and Approval
**Gate:** This matrix must be approved before any Spatie permission seeding begins.
