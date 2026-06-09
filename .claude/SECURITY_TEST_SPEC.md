# SECURITY TEST SPECIFICATION
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Specification — tests authored in Task 10.1
Decision References: D-028, D-037, D-039
Framework: PHPUnit 11

---

## PURPOSE

Automated tests guaranteeing the Task 9 security middleware behaves correctly. CI
gates (IMPLEMENTATION_GOVERNANCE §7).

---

## 1. HEADER TESTS (T-9.1)
| ID | Test | Expected |
|---|---|---|
| HD-1 | Response includes X-Frame-Options=SAMEORIGIN | present |
| HD-2 | X-Content-Type-Options=nosniff | present |
| HD-3 | Referrer-Policy=strict-origin-when-cross-origin | present |
| HD-4 | Permissions-Policy present with locked features | present |
| HD-5 | HSTS present over HTTPS; ABSENT over HTTP | conditional |
| HD-6 | X-Powered-By / Server stripped | absent |
| HD-7 | Header values change when config/env changes (config-driven) | reflects config |

## 2. CSP TESTS
| ID | Test | Expected |
|---|---|---|
| CSP-1 | Content-Security-Policy header present | present |
| CSP-2 | default-src 'self' and object-src 'none' enforced | present |
| CSP-3 | frame-ancestors 'self' present | present |
| CSP-4 | Custom SECURITY_CSP env overrides default | reflects env |

## 3. RATE LIMIT TESTS (T-9.2)
| ID | Test | Expected |
|---|---|---|
| RL-1 | login over limit → 429 + Retry-After | 429 |
| RL-2 | password-reset over limit → 429 | 429 |
| RL-3 | mfa over limit → 429 | 429 |
| RL-4 | public-forms (register) over limit → 429 | 429 |
| RL-5 | api over limit → 429 | 429 |
| RL-6 | login keyed on email+IP (different email same IP not blocked prematurely) | independent |
| RL-7 | limits reflect config (RL_* env) | configurable |

## 4. SESSION TESTS (T-9.3)
| ID | Test | Expected |
|---|---|---|
| SE-1 | Session lifetime = config value | 120 |
| SE-2 | Session id regenerated on login | new id |
| SE-3 | Role change revokes tokens (Task 7 R-2 cross-check) | tokens gone |

## 5. CSRF TESTS
| ID | Test | Expected |
|---|---|---|
| CS-1 | Web POST without CSRF token rejected | 419 |
| CS-2 | Web POST with valid token accepted | ok |
| CS-3 | API (Sanctum bearer) not subject to CSRF | ok |

## 6. PROXY TESTS (T-9.4)
| ID | Test | Expected |
|---|---|---|
| PX-1 | With trusted proxy + X-Forwarded-For, $request->ip() = client IP | client IP |
| PX-2 | Without trusting proxy, forwarded header ignored | proxy IP |
| PX-3 | TRUSTED_PROXIES config drives behaviour | configurable |

## 7. COOKIE TESTS (T-9.3)
| ID | Test | Expected |
|---|---|---|
| CK-1 | Session cookie has Secure flag | present |
| CK-2 | Session cookie has HttpOnly flag | present |
| CK-3 | Session cookie SameSite=Strict | present |

---

## ACCEPTANCE (cross-check)
- [ ] All six security headers verified (HD-/CSP-)
- [ ] Rate limiting enforced + configurable (RL-)
- [ ] Secure/HttpOnly/SameSite cookies (CK-)
- [ ] CSRF on web, exempt on token API (CS-)
- [ ] Trusted-proxy client IP correctness (PX-)

## EXECUTION
- CI quality job; failure BLOCKS merge. Authored in Task 10.1.

## APPROVAL
| Role | Name | Signature | Date |
|---|---|---|---|
| Lead Architect | | | |
| Security Officer | | | |
