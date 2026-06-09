# USER LIFECYCLE GOVERNANCE REVIEW — PRE-TASK 7
# ICS Enterprise Ecosystem Platform

Version: 1.1 (FINALIZED)
Date: 2026-05-31
Status: FINALIZED — dispositions recorded in D-047; controls implemented in Task 7
        (see TASK_7_IMPLEMENTATION_REVIEW.md). ULC-01…ULC-05 closed; R-1…R-7 built;
        R-8…R-11 deferred (operational runbooks / later sprints).
Reviewer: Chief Enterprise Architect
Decision References: D-006, D-021, D-039, D-040, D-044, D-045, D-046
Inputs: USER_ROLE_MATRIX, PERMISSION_MATRIX, Tasks 4–6 implementation

---

## EXECUTIVE SUMMARY

The user lifecycle the platform supports is largely sound — default-deny RBAC,
four-eyes escalation, immutable audit, GDPR erasure-by-anonymisation, admin MFA.
However this review identifies **five control gaps that should be closed in Task 7**,
the most important being:

- **ULC-01 (HIGH): no approval/"pending" account state** — registration that the
  matrix says requires approval cannot actually be gated (status enum is only
  active/suspended/deactivated).
- **ULC-02 (HIGH): stale-privilege window** — role changes do not revoke existing
  tokens/sessions, so an actor keeps old privileges until token expiry.
- **ULC-03 (HIGH): no "last Super Admin" protection** — the final Super Admin could
  be deactivated/deleted/role-revoked, locking the platform out of its top control.
- **ULC-04 (MEDIUM): reactivation privilege-restoration vector** — reactivating a
  former Super Admin could silently restore Super Admin, bypassing four-eyes.
- **ULC-05 (MEDIUM): self-registration role selection** — without a whitelist, a
  self-registrant could request an elevated role.

None block Task 7; each is a defined control to build into it. Two (ULC-01) may
require a small schema amendment (approval state) — raised for sign-off at Task 7
start, AF-1 style.

---

## PART A — LIFECYCLE REVIEWS

### 1. User Creation Rules
- Creation authority per USER_ROLE_MATRIX: Super Admin (seed/another Super Admin),
  Platform staff (Platform Admin), org roles (Platform Admin after approval),
  individual roles (self-registration + approval).
- **Bootstrap exception:** the FIRST Super Admin is DB-seeded — four-eyes is
  impossible with zero/one Super Admin. Must be documented and the seed protected.
- **Gap ULC-05:** self-registration must restrict the assignable role to a
  whitelist (e.g., Student, Startup Founder/Member); a self-registrant must never
  obtain staff/admin/org-admin roles. Initial role assignment must respect the
  escalation guard.
- Email verification (email_verified_at) should precede activation.

### 2. User Activation Rules
- Activation = email verified + (where required) admin approval → status = active.
- **Gap ULC-01:** there is no `pending`/`awaiting_approval` state. Roles the matrix
  says require approval (notably Trainer — "must be manually approved") cannot be
  held inactive pending review. Either add a status value or an `approved_at`/
  approval flag, and deny login until approved.
- Reactivation of a deactivated/suspended account must be admin-only and audited
  (see ULC-04 for the Super Admin caveat).

### 3. User Deactivation Rules
- `status = deactivated`, all tokens revoked, audited (E-CORE-008). Self-action
  prohibited (UserPolicy). Authority: `platform.users.deactivate` (Super/Platform).
- **Gap ULC-03:** deactivating the LAST active Super Admin must be blocked.
- Deactivation should be reversible (reactivation) but is distinct from suspension
  (offboarding vs temporary hold).

### 4. User Suspension Rules
- `status = suspended` — temporary security hold (investigation, anomaly). Blocks
  login (AuthController allows only `active`). Reversible by admin; reason captured;
  audited.
- **Gap:** no `AccountSuspended` / `AccountReactivated` events yet — add in Task 7
  for a complete audit trail. Suspension differs from deactivation by intent and
  reversibility; both must be enumerated and auditable.

### 5. User Deletion Rules
- GDPR erasure = soft-delete + PII anonymisation + token revocation; audit trail
  preserved (hashes, no FK). Self-service (own) + admin (`platform.users.delete`).
- Hard deletion only via retention purge (anonymised), never raw.
- **Gap ULC-03:** deleting a Super Admin (especially the last) must be blocked or
  require four-eyes. Deletion is destructive and irreversible past the retention
  window.

### 6. Role Assignment Rules
- Level guard (no grant at/above own level) + four-eyes for Super Admin (D-044).
  Self-escalation impossible. Audited (role_assignment, high-sensitivity).
- **Gap ULC-02:** a role change does NOT revoke existing tokens/sessions → the
  subject retains prior privileges until token expiry (stale-privilege window).
  Role changes must revoke tokens + regenerate session.
- **Revocation:** rules for revoking Super Admin should mirror granting it (four-eyes
  or last-Super-Admin protection) to prevent disruptive/abusive revocation.
- Org-role assignment (Client/Partner Admin) binds the user to an account; that
  linkage (account_id) arrives with the CRM/Client/Partner sprints — until then,
  org scoping is incomplete (carried risk).

### 7. Escalation Approval Flow
- request → DIFFERENT Super Admin approves → grant; 2-day expiry; decided-once;
  immutable high-sensitivity audit (D-045/D-046). Self-approval blocked.
- **Limitations:** (a) two colluding Super Admins defeat four-eyes — mitigate with
  alerting + access reviews + minimal Super Admin count; (b) a single Super Admin
  cannot create a second (four-eyes needs two) — define a controlled bootstrap/
  break-glass path.

### 8. Audit Requirements
- All lifecycle events audited; Super Admin actions high-sensitivity (D-046).
- Dispatchers for UserRegistered / AccountDeactivated / RoleRevoked are wired in
  Task 7 (classes/handlers exist). Add AccountSuspended/AccountReactivated.
- **Recommend:** real-time alerting (NotifySecurityTeam) on high-sensitivity
  lifecycle events (Super Admin grant/revoke, deletions, suspensions).

### 9. GDPR Implications
- Erasure-by-anonymisation reconciles right-to-erasure with the immutable audit:
  the user record is anonymised; the audit retains a pseudonymous actor_id +
  hashes (defensible under legal-obligation/legitimate-interest for security).
- Export excludes secrets; rate-limited; audited. Consent ledger retained
  (anonymised) as proof of lawful basis.
- **Gap (minor):** no admin "export/erasure on behalf of a subject" permission +
  audit (support scenario) — add only if the support workflow needs it.
- Retention/purge of aged PII via core_retention_policies (scheduled, later).

### 10. Government Compliance Implications
- SoD (D-040), least privilege, admin MFA, four-eyes, immutable audit →
  ISO 27001-aligned (D-006), suitable for Government audience #1 (D-016).
- **Recommend:** a documented Joiner-Mover-Leaver (JML) process with deprovisioning
  SLAs; periodic access recertification (Super/Platform quarterly, others annually);
  enforced segregation between requester and approver (present via four-eyes);
  2-year audit retention; breach-notification runbook (72h, NDPA/GDPR).

---

## PART B — THREAT IDENTIFICATION

### Abuse Scenarios
| ID | Scenario | Current control | Gap/Action |
|---|---|---|---|
| AB-1 | Self-register into an elevated role | level guard on assignment | Enforce self-register role whitelist (ULC-05) |
| AB-2 | Mass/spam registration | rate limit + Cloudflare | Add registration throttle + bot challenge |
| AB-3 | Orphaned ex-staff accounts retain access | deactivation exists | JML process + periodic recert |
| AB-4 | Account takeover via reset/login | lockout + MFA + no enumeration | (covered) + alert on admin resets |
| AB-5 | Reactivation backdoor restores Super Admin | none | ULC-04 reactivation rule |

### Insider Threat Risks
| ID | Risk | Mitigation |
|---|---|---|
| IN-1 | Two colluding Super Admins defeat four-eyes | minimise Super Admins (2–3), alerting, access reviews |
| IN-2 | Admin self-services sensitive PII (CRM) | least privilege + high-sensitivity audit |
| IN-3 | Admin deactivates others to seize control | last-Super-Admin protection (ULC-03) + audit + alert |
| IN-4 | Admin exports another user's data | export is self-only; admin-on-behalf needs explicit perm + audit |
| IN-5 | Disruptive role revocation | audit + four-eyes/last-admin protection on Super Admin revoke |

### Privilege Escalation Risks
| ID | Vector | Status |
|---|---|---|
| PE-1 | Direct over-grant | BLOCKED (level guard + four-eyes, D-044) |
| PE-2 | Reactivation restoring Super Admin | OPEN → ULC-04 |
| PE-3 | Self-registration role choice | OPEN → ULC-05 |
| PE-4 | Stale token after role change | OPEN → ULC-02 (revoke tokens on role change) |
| PE-5 | Gate::before logic defect | covered by tests (DD-2, RBAC_CONFORMANCE_TEST_SPEC) |

### Recovery Procedures (to formalise as runbooks)
| ID | Scenario | Procedure |
|---|---|---|
| RC-1 | Total Super Admin lockout | Controlled DB seed of a new Super Admin (break-glass), out-of-band approval, audited |
| RC-2 | Lost MFA (user) | Recovery codes first; then admin-assisted reset after identity verification + audit |
| RC-3 | Lost MFA (admin) | Admin reset under four-eyes + audit (higher assurance) |
| RC-4 | Compromised account | Immediate suspension + token revocation + forced reset + audit review |
| RC-5 | Accidental deactivation/deletion | Reactivate/restore within retention window (soft-delete); Super Admin re-grant via four-eyes |
| RC-6 | Single-Super-Admin needs a second | Documented bootstrap exception (seed-based), audited |

---

## PART C — RECOMMENDATIONS (prioritised for Task 7)

| # | Recommendation | Priority | Schema? |
|---|---|---|---|
| R-1 | Add an approval/"pending" account state; deny login until approved (ULC-01) | HIGH | Possibly (enum/flag) — amendment |
| R-2 | Revoke tokens + regenerate session on ANY role change (ULC-02) | HIGH | No |
| R-3 | Last-Super-Admin protection: cannot deactivate/delete/revoke-role the final active Super Admin (ULC-03) | HIGH | No |
| R-4 | Reactivation restores prior roles EXCEPT Super Admin (re-grant via four-eyes) (ULC-04) | MEDIUM | No |
| R-5 | Self-registration role whitelist; never self-grant staff/admin/org roles (ULC-05) | MEDIUM | No |
| R-6 | Add AccountSuspended / AccountReactivated events; wire UserRegistered / AccountDeactivated / RoleRevoked dispatchers (audit) | MEDIUM | No |
| R-7 | Alerting (NotifySecurityTeam) on high-sensitivity lifecycle events | MEDIUM | No |
| R-8 | Four-eyes (or last-admin protection) on Super Admin role REVOCATION | MEDIUM | No |
| R-9 | Break-glass / MFA-recovery / compromised-account runbooks | MEDIUM | No (operational) |
| R-10 | Admin-on-behalf GDPR export/erasure permission + audit (if support needs) | LOW | No (permission only) |
| R-11 | JML process + periodic access recertification | LOW | No (governance) |

**Items needing sign-off at Task 7 start:** R-1 (account approval state — likely a
small schema amendment, AF-1 style). The rest are application-layer controls within
the approved architecture.

### DISPOSITION (D-047) — approved for Task 7
- R-1: APPROVED — `core_users.status` gains 'pending' (login denied until active).
  Blueprint + Task 3 migration updated. ✅
- R-2, R-3, R-4, R-5: APPROVED — build in Task 7.
- R-6, R-7: APPROVED — lifecycle events + security alerting in Task 7.
- R-8, R-9, R-10, R-11: deferred (later sprints / operational runbooks).

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| 10 lifecycle areas reviewed | ✅ |
| Abuse / insider / escalation / recovery identified | ✅ |
| Recommendations provided | ✅ |
| Task 7 NOT implemented | ✅ |

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security Officer | | | | |

**Status:** Awaiting Approval. **Do not implement Task 7 until approved.**
Approval should also indicate the disposition of R-1 (account approval state) so it
can be applied at Task 7 start.
