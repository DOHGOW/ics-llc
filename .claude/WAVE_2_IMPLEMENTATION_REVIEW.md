# WAVE 2 IMPLEMENTATION REVIEW — CLIENT PORTAL + PARTNER PORTAL
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-01
Status: Implementation complete — Awaiting Approval (STOP before Wave 3)
Author: Lead Architect
Decision References: D-025, D-031, D-037, D-044, D-046, D-050, D-053, D-055, D-056; W2-1..W2-9
Design baseline: WAVE_2_ARCHITECTURE_REVIEW.md (approved)

---

## EXECUTIVE SUMMARY

Wave 2 delivers the two external-facing, **organisation-owned** portals. It is the FIRST
real consumer of the Wave 1a ownership framework: `BelongsToAccount` + `AccountScope`
(Layer 1) + `OrgOwnedPolicy` (Layer 2) now isolate live portal data by `account_id`. The
four hard problems from the design are all solved in code: child rows are **parent-isolated**
(W2-1), the **referral→CRM-lead boundary** is one-way (W2-3), **internal ticket replies** are
filtered at three layers (W2-4), and **files** are policy-gated/streamed (W2-5). Partner
ownership is unified under AccountScope via D-055 (every partner gets a crm_account). Portal
lifecycle + financial events are audited under the new `portal_management` category, with
agreement/commission/suspension marked HIGH (D-056).

**Verdict: IMPLEMENTATION SOUND** — all seven requirements and the W2 decisions realised.
Standing caveat unchanged: overlay must bootstrap + run GREEN in CI (MySQL — FKs, scoped
bindings, TIMESTAMPDIFF) before operationally "done" (R-012/R-013).

---

## DELIVERABLES

| Layer | Artifact |
|---|---|
| Migrations | 5 client (projects, milestones, deliverables, tickets, ticket_replies) + 4 partner (tiers, profiles, referrals[+account_id], agreements[+account_id]) |
| Models | Client\{ClientProject, ProjectMilestone, Deliverable, Ticket, TicketReply}; Partner\{PartnerTier, PartnerProfile, PartnerReferral, PartnerAgreement} |
| Policies | Client\{ClientProjectPolicy, TicketPolicy}; Partner\{PartnerProfilePolicy, PartnerReferralPolicy, PartnerAgreementPolicy} (all extend OrgOwnedPolicy) — registered in AuthServiceProvider |
| Services | Client\ClientPortalService; Partner\PartnerPortalService (incl. onboardPartner D-055, qualifyReferral W2-3 seam) |
| Analytics | Client\ClientPortalAggregator; Partner\PartnerPortalAggregator (D-025 hooks) |
| Events | Portal\{ProjectStatusChanged, DeliverableStatusChanged, TicketResolved, PartnerProfileStatusChanged, ReferralStageChanged, CommissionRecorded, CommissionPaid, AgreementSigned} |
| Audit | `AuditCategory::PORTAL_MANAGEMENT` + 8 handlers; AuditService gains explicit-sensitivity override (D-056) |
| Controllers | Client\{Project, Milestone, Deliverable, Ticket, TicketReply}; Partner\{PartnerProfile, Referral, Agreement, PartnerDashboard} |
| Routes | routes/portal.php (auth:sanctum; nested + scopeBindings); registered in bootstrap/app.php |
| Permissions | partner.profiles.approve granted to ICS_CRM |
| Docs | DECISION_LOG (D-055/D-056/W2 resolutions), DATABASE_BLUEPRINT (D-055 columns + module notes), this review, PROJECT_MEMORY |

---

## 1. CLIENT PORTAL VALIDATION

| Check | Result | Evidence |
|---|---|---|
| Projects org-owned | ✅ | ClientProject uses BelongsToAccount; account_id NOT NULL → crm_accounts |
| Tickets org-owned | ✅ | Ticket uses BelongsToAccount; account_id stamped from client user |
| Children parent-isolated (W2-1) | ✅ | Milestone/Deliverable/Reply have no BelongsToAccount; nested routes + scopeBindings + parent policy gate |
| Deliverable lifecycle audited | ✅ | changeDeliverableStatus → DeliverableStatusChanged (submitted/approved/rejected) |
| Drafts hidden from clients | ✅ | CLIENT_VISIBLE_STATUSES filter on index + download |
| Internal ticket replies hidden (W2-4) | ✅ | query (publicReplies/scopePublic) + policy (replyInternal) + resource (show selects public for non-staff) |
| Files policy-gated (W2-5) | ✅ | DeliverableController::download streams via disk after policy + status check; no public file_path |
| ICS staff manage; client views own | ✅ | ClientProjectPolicy/TicketPolicy: manage (staff) vs read.own (sameAccount) |

## 2. PARTNER PORTAL VALIDATION

| Check | Result | Evidence |
|---|---|---|
| Partner ownership unified (D-055) | ✅ | onboardPartner provisions a crm_account; account_id REQUIRED on profiles/referrals/agreements |
| Org-owned isolation | ✅ | PartnerProfile/Referral/Agreement use BelongsToAccount + policies |
| Referral submission + lifecycle | ✅ | ReferralController store (partner) + qualify/stage (staff); ReferralStageChanged audited |
| Commission tracking (D-031) | ✅ | recordCommission/payCommission → CommissionRecorded/Paid (HIGH audit) |
| Agreements + signing | ✅ | AgreementController store/sign → AgreementSigned (HIGH audit); file gated (W2-5) |
| Partner dashboard (account-scoped) | ✅ | PartnerDashboardController: AccountScope restricts funnel/commission to the partner's own account |
| Profile approval/suspension staff-only | ✅ | PartnerProfilePolicy::administer (partner.profiles.approve + internal staff); suspension HIGH |

## 3. ISOLATION VALIDATION

| Check | Result | Evidence |
|---|---|---|
| AccountScope is the portal isolation control | ✅ | All org-owned portal models use BelongsToAccount (Layer 1) |
| OrgOwnedPolicy enforces Layer 2 | ✅ | 5 policies extend OrgOwnedPolicy (accessible = internal staff OR sameAccount) |
| Wave 1a harness reused (not reinvented) | ✅ | No new isolation primitive; BelongsToAccount/AccountScope/OrgOwnedPolicy unchanged |
| Children never queried top-level (W2-1) | ✅ | No top-level child routes; all nested under AccountScope-protected parent + scopeBindings |
| Three mechanisms stay separate | ✅ | AccountScope (portals) · ContentAccessService (content) · HasAssignmentVisibility (CRM) — none mixed; Wave 2 touches only AccountScope |
| Cross-org denial testable (W2-9) | ✅ design | Org A cannot bind Org B's project/ticket/profile/referral/agreement (AccountScope → 404); isolation tests are the release gate |

## 4. SECURITY VALIDATION

| Threat | Control | Result |
|---|---|---|
| Org A reads Org B data | AccountScope + OrgOwnedPolicy | ✅ |
| Child cross-org access | Parent-scoped nested routes + scopeBindings (W2-1) | ✅ |
| **Partner sees CRM lead** (W2-3) | partner_referrals.lead_id is `$hidden`; never selected in partner queries; qualify is staff-only | ✅ |
| **Client sees internal notes** (W2-4) | 3-layer filter: scopePublic/publicReplies (query) + replyInternal (policy) + non-staff show path (resource) | ✅ |
| Public file exposure (W2-5) | Streamed downloads behind policy + status checks; no public URLs | ✅ |
| Privilege drift | Org roles hold only `*.own`/create/reply; manage/approve are staff perms | ✅ |
| Self-approval / commission tampering | administer/commission gated to internal staff; audited HIGH | ✅ |

## 5. AUDIT VALIDATION (D-056 / D-046)

| Check | Result | Evidence |
|---|---|---|
| `portal_management` category added | ✅ | AuditCategory::PORTAL_MANAGEMENT |
| 8 portal events wired | ✅ | handlers + subscriptions in AuditEventSubscriber |
| HIGH-sensitivity: agreements | ✅ | handleAgreementSigned forces AuditSensitivity::HIGH |
| HIGH-sensitivity: commissions | ✅ | handleCommissionRecorded/Paid force HIGH |
| HIGH-sensitivity: suspensions | ✅ | handlePartnerProfileStatusChanged forces HIGH when status ∈ {suspended, terminated} |
| Explicit override added cleanly | ✅ | AuditService::log gains `$forceSensitivity`; resolveSensitivity honours it; backward-compatible |
| Append-only + Super-Admin HIGH intact | ✅ | AuditService invariants unchanged (D-046) |
| Events fire once, in services | ✅ | Lifecycle events fired inside ClientPortalService/PartnerPortalService |

## 6. ANALYTICS VALIDATION (D-025)

| Check | Result | Evidence |
|---|---|---|
| Aggregation hooks present | ✅ | ClientPortalAggregator + PartnerPortalAggregator snapshot() |
| Separate from source; scheduled | ✅ | Services built for a scheduled job; system-context reads via acrossAccounts() |
| Client KPIs | ✅ | projects by status, deliverable approval, tickets by status, avg resolution hours |
| Partner KPIs | ✅ | referral funnel, commission paid/pending totals, active partners |
| Per-org dashboard is account-scoped | ✅ | PartnerDashboardController relies on AccountScope (partner sees only own) |
| No heavy per-request scans on portal reads | ✅ | List endpoints select minimal columns + paginate; aggregation offloaded |

## 7. FUTURE TenantScope VALIDATION (D-037)

| Check | Result | Evidence |
|---|---|---|
| tenant_id on parent portal tables | ✅ | client_projects/tickets, partner_profiles/referrals/agreements |
| Tenancy additive over AccountScope | ✅ | tenant > account > user (D-050 #4); a future TenantScope composes above; portals unchanged |
| No schema change to enable | ✅ | columns present; Phase-3 enablement = scope class + .env (D-037) |
| Children inherit via parent | ✅ | consistent with W2-1 parent-isolation |

---

## FINDINGS DISPOSITION

| ID | Finding | Status |
|---|---|---|
| W2-1 | Child parent-isolation | ✅ nested routes + scopeBindings + parent policy; no BelongsToAccount on children |
| W2-2 | Partner ownership unification | ✅ D-055 onboardPartner; account_id required on referrals/agreements |
| W2-3 | Referral→lead one-way boundary | ✅ lead_id $hidden + excluded from selects; qualify staff-only |
| W2-4 | Internal replies filtered (3 layers) | ✅ query + policy + resource |
| W2-5 | File delivery gated/signed | ✅ streamed downloads behind policy + status checks |
| W2-6 | PORTAL_MANAGEMENT + HIGH events | ✅ D-056; agreement/commission/suspension HIGH |
| W2-7 | Multi-user orgs | ✅ Client Admin client.users.manage.own; partner users bound via account_id |
| W2-8 | client_projects FK RESTRICT | ✅ FK has no ON DELETE action (deleting an account with projects is blocked) |
| W2-9 | Isolation tests per model | ⚠ release gate (to run under GREEN-CI bootstrap) |

### Correctness decisions made during implementation (self-flagged)

1. **D-055 realised as a service, not just columns** — `onboardPartner()` provisions the
   crm_account (org OR individual), binds the partner user's `account_id`, and creates the
   pending profile, so AccountScope has no nullable-ownership gap.
2. **Status/stage never mass-assignable** — project/deliverable/ticket/referral/profile
   transitions go ONLY through the services (audited); `update()` excludes them. No
   transition escapes the audit trail.
3. **`lead_id` defended in depth** (W2-3) — both `$hidden` on the model AND explicitly
   omitted from every partner-facing `select()`; `qualify` is staff-only.
4. **Three-layer internal-reply filter** (W2-4) — query (`scopePublic`/`publicReplies`),
   policy (`replyInternal`), and resource (non-staff `show` returns public replies only).
5. **AuditService extended backward-compatibly** — new optional `$forceSensitivity`
   parameter; existing callers unaffected.

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Portals org-owned; AccountScope + BelongsToAccount + OrgOwnedPolicy used (reqs 1–4) | ✅ |
| PORTAL_MANAGEMENT audit events (req 5) | ✅ |
| Analytics aggregation hooks (req 6) | ✅ |
| D-037 TenantScope compatibility preserved (req 7) | ✅ |
| Three isolation mechanisms remain separate | ✅ |
| Wave 3 NOT implemented | ✅ |
| Bootstrap + GREEN CI still required before "done" (R-012/R-013) | ⚠ carried |

---

## REVIEW VERDICT

**IMPLEMENTATION SOUND.** Wave 2 activates the Wave 1a ownership framework as the live
portal isolation control, unifies partner ownership under AccountScope (D-055), defends the
referral→CRM boundary and internal-reply confidentiality in depth, gates files, and audits
portal lifecycle/financial events under PORTAL_MANAGEMENT with the right HIGH-sensitivity
markings. Cleared for approval.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **STOP — do not begin Wave 3 until approved.**
