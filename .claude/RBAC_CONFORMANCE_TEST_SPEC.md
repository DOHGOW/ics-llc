# RBAC CONFORMANCE TEST SPECIFICATION
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Specification — tests authored in Task 10.1
Decision References: D-021, D-044, D-045
Test framework: PHPUnit 11 (engine-parity: MySQL 8)

---

## PURPOSE

Defines the automated tests that GUARANTEE the implemented RBAC stays in parity
with the approved matrices and that the escalation controls are enforced. These
tests become CI gates (IMPLEMENTATION_GOVERNANCE §7). PERMISSION_MATRIX and
USER_ROLE_MATRIX are the sources of truth; on any discrepancy the seeder is
corrected, not the matrix.

---

## 1. SEEDER ↔ PERMISSION_MATRIX PARITY

Goal: every role's granted permissions exactly equal the PERMISSION_MATRIX grid.

| ID | Test | Expected |
|---|---|---|
| PM-1 | Catalogue completeness: every permission named in PERMISSION_MATRIX exists after seeding | no missing/extra permissions |
| PM-2 | Per-role grant set equals the matrix row (for all 13 roles) | exact set equality per role |
| PM-3 | Platform Admin = full catalogue minus Super-only set | equality |
| PM-4 | Super Admin holds no explicit permissions but `can()` returns true for all (Gate::before) | true for sampled abilities |
| PM-5 | Gov Rep does NOT have `knowledge.tier4.read` (D-044/EP-2) | denied |

Method: load the matrix as a fixture (or assert against an authoritative array
derived from PERMISSION_MATRIX); diff against `Role::findByName(...)->permissions`.

---

## 2. USER_ROLE_MATRIX PARITY

| ID | Test | Expected |
|---|---|---|
| UR-1 | Exactly 13 Spatie roles seeded; names match USER_ROLE_MATRIX | equality |
| UR-2 | No "Guest" role record exists (unauthenticated) | absent |
| UR-3 | Admin roles (Super, Platform) flagged MFA-required (RequireMfaForAdmins) | enforced |
| UR-4 | Role privilege levels match the defined hierarchy | equality |

---

## 3. NAMING CONVENTION COMPLIANCE

| ID | Test | Expected |
|---|---|---|
| NC-1 | Every permission matches `^[a-z0-9]+(\.[a-z0-9_]+){1,3}$` and starts with a known module token | all pass |
| NC-2 | No permission uses the deprecated `action.module.scope` order | none |
| NC-3 | Module prefixes ∈ {platform, auth, profile, notifications, cms, crm, client, training, marketplace, partner, startup, knowledge, research, ai, community, billing, analytics} | all pass |

---

## 4. ESCALATION GUARD ENFORCEMENT

| ID | Test | Expected |
|---|---|---|
| EG-1 | An actor cannot grant a role at/above its own level | DomainException |
| EG-2 | `assign()` rejects the Super Admin role outright | DomainException |
| EG-3 | A Platform Admin cannot self-assign or assign Super Admin | denied |
| EG-4 | A valid lower-level grant succeeds and is audited (role_assignment) | role attached + audit row |
| EG-5 | Unknown role name rejected | DomainException |

---

## 5. FOUR-EYES APPROVAL ENFORCEMENT

| ID | Test | Expected |
|---|---|---|
| FE-1 | Non-Super-Admin cannot initiate a Super Admin request | DomainException |
| FE-2 | Requester ≠ approver enforced (same Super Admin cannot approve own request) | DomainException |
| FE-3 | Approval by a different Super Admin grants the role + sets status=approved + approver_id + decided_at | success |
| FE-4 | Expired request cannot be approved (status→expired) | DomainException |
| FE-5 | Already-decided request cannot be re-approved/rejected | DomainException |
| FE-6 | Invalid reason code rejected | DomainException |
| FE-7 | Every transition (request/approve/reject/expire) writes a high-sensitivity audit row | audit rows present |

---

## 6. DEFAULT-DENY & AUDIT (cross-cutting)

| ID | Test | Expected |
|---|---|---|
| DD-1 | A user with no permissions is denied on every protected ability | 403 / false |
| DD-2 | Super Admin passes any ability (Gate::before) | true |
| AU-1 | Super Admin actions are recorded as sensitivity=high regardless of category | high |
| AU-2 | Audit rows are immutable (update/delete throw) | LogicException |

---

## EXECUTION
- Run in CI (quality + engine-parity jobs).
- Failure of any PM-*/UR-*/EG-*/FE-* test BLOCKS merge.
- Authored in Task 10.1 (test baseline); this spec is the acceptance contract.

---

## APPROVAL

| Role | Name | Signature | Date |
|---|---|---|---|
| Lead Architect | | | |
| Security Officer | | | |
