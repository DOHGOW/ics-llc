# USER MANAGEMENT TEST SPECIFICATION
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Specification — tests authored in Task 10.1
Decision References: D-021, D-039, D-044, D-045, D-046, D-047
Framework: PHPUnit 11 (engine-parity: MySQL 8)

---

## PURPOSE

Defines the automated tests guaranteeing the Task 7 user-management controls behave
correctly and securely. These become CI gates (IMPLEMENTATION_GOVERNANCE §7).

---

## 1. APPROVAL FLOW TESTS
| ID | Test | Expected |
|---|---|---|
| AP-1 | Self-registering an approval-required role creates status='pending' | pending |
| AP-2 | A pending user CANNOT authenticate | login 401 |
| AP-3 | Admin with `platform.users.update.all` approves a pending user → active | active + AccountApproved event + audit |
| AP-4 | Approve on a non-pending user is rejected | DomainException/422 |
| AP-5 | Approved user can now authenticate | login 200 |

## 2. SELF-REGISTRATION TESTS (R-5)
| ID | Test | Expected |
|---|---|---|
| SR-1 | Register with a whitelisted role (Student) → active | success, role attached |
| SR-2 | Register with a non-whitelisted role (Platform Admin) | rejected (validation/DomainException) |
| SR-3 | Register with an unknown role | rejected |
| SR-4 | Duplicate email | 422 |
| SR-5 | Weak/breached password | 422 (policy + HIBP) |
| SR-6 | UserRegistered event dispatched + audited | event + audit row |

## 3. SUSPENSION TESTS
| ID | Test | Expected |
|---|---|---|
| SU-1 | Admin suspends an active user | status=suspended + tokens revoked |
| SU-2 | Suspended user cannot authenticate | 401 |
| SU-3 | A user cannot suspend themselves | 403 (UserPolicy) |
| SU-4 | Suspend requires a reason | 422 if missing |
| SU-5 | AccountSuspended event + audit + security alert | all present |

## 4. REACTIVATION TESTS (R-4)
| ID | Test | Expected |
|---|---|---|
| RA-1 | Reactivate a suspended user → active | active + AccountReactivated event |
| RA-2 | Reactivating a user who had Super Admin does NOT restore Super Admin | role removed + RoleRevoked event |
| RA-3 | Reactivate on an active user | rejected |
| RA-4 | Reactivation audited | audit row |

## 5. ROLE CHANGE TESTS (D-044)
| ID | Test | Expected |
|---|---|---|
| RC-1 | Assign a role below the actor's level | success + RoleAssigned + audit |
| RC-2 | Assign at/above actor's level | DomainException |
| RC-3 | Direct assign of Super Admin | rejected (four-eyes required) |
| RC-4 | Four-eyes: request + different Super Admin approve → granted | success |
| RC-5 | Same Super Admin approves own request | rejected |
| RC-6 | Revoke a role (non-Super) below actor level | success + RoleRevoked |

## 6. TOKEN REVOCATION TESTS (R-2)
| ID | Test | Expected |
|---|---|---|
| TR-1 | Assigning a role revokes the target's existing Sanctum tokens | tokens deleted |
| TR-2 | Revoking a role revokes tokens | tokens deleted |
| TR-3 | Super Admin grant (four-eyes) revokes target tokens | tokens deleted |
| TR-4 | After token revocation the old token is rejected | 401 |

## 7. LAST SUPER ADMIN PROTECTION TESTS (R-3)
| ID | Test | Expected |
|---|---|---|
| LS-1 | Deactivate the only active Super Admin | rejected (service + policy) |
| LS-2 | Delete the only active Super Admin | rejected |
| LS-3 | Suspend the only active Super Admin | rejected |
| LS-4 | Revoke Super Admin from the only active Super Admin | rejected |
| LS-5 | With two active Super Admins, removing one is allowed | success |

## 8. AUDIT COVERAGE TESTS (D-046)
| ID | Test | Expected |
|---|---|---|
| AU-1 | Every lifecycle action writes an audit row via AuditService | row present |
| AU-2 | Super Admin actor → sensitivity=high | high |
| AU-3 | user_management / role_assignment / escalation categories present | correct category |
| AU-4 | Audit rows are immutable (update/delete throw) | LogicException |
| AU-5 | Escalation request/approve/reject audited (high) | rows present |

## 9. EVENT DISPATCH TESTS
| ID | Test | Expected (Event::fake) |
|---|---|---|
| EV-1 | approve → AccountApproved | dispatched |
| EV-2 | suspend → AccountSuspended | dispatched |
| EV-3 | reactivate → AccountReactivated (+ RoleRevoked if was Super Admin) | dispatched |
| EV-4 | deactivate → AccountDeactivated | dispatched |
| EV-5 | assign → RoleAssigned ; revoke → RoleRevoked | dispatched |
| EV-6 | register → UserRegistered | dispatched |
| EV-7 | Security alert raised for high-sensitivity events (recipients configured) | notification sent |

---

## EXECUTION
- Run in CI quality + engine-parity jobs; failure BLOCKS merge.
- Authored in Task 10.1; this spec is the acceptance contract.

## APPROVAL
| Role | Name | Signature | Date |
|---|---|---|---|
| Lead Architect | | | |
| Security Officer | | | |
