# AUTHENTICATION ARCHITECTURE REVIEW — SPRINT 1 · TASK 4 (PLANNING)
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Planning — Awaiting Approval (no Task 4 code until approved)
Author: Chief Enterprise Architect
Decision References: D-006, D-021, D-022, D-023, D-039, D-041, D-042

---

## EXECUTIVE SUMMARY

This review designs the Authentication Foundation (Task 4) before any code is
written. It covers web + API authentication, password reset, MFA, sessions,
Sanctum, lockout, and the GDPR export/delete implications — mapped to approved
decisions. It also raises one required schema correction (AF-1) that follows from
the approved F-5 encryption decision. No application code is produced here.

Task 4 scope (from SPRINT_1_TASK_BREAKDOWN): T-4.1 web auth · T-4.2 Sanctum API ·
T-4.3 password policy + HIBP · T-4.4 lockout · T-4.5 MFA · T-4.6 session
hardening · T-4.7 GDPR export/delete. Plus companion items: F-2 driver→table
wiring, F-3 password_reset_tokens migration, F-5 mfa encryption (AF-1).

---

## 0. PREREQUISITE CORRECTIONS (apply at Task 4 start)

| ID | Item | Action | Approval |
|---|---|---|---|
| AF-1 | `core_users.mfa_secret` is VARCHAR(64) in the Task 3 migration, but an encrypted TOTP secret (F-5) exceeds 64 chars | Change the column to TEXT (edit the not-yet-run Task 3 migration, OR add an alter migration). Blueprint already updated (D-042). | Needs nod at Task 4 start |
| F-2 | Driver→table config wiring | Set config/queue.php (sys_jobs/sys_failed_jobs), config/cache.php (sys_cache), config/session.php (sys_sessions) | Approved (deferred) |
| F-3 | `password_reset_tokens` migration | Author per D-041 spec | Approved |
| F-1 | Stock Laravel default migrations present after bootstrap | Delete users/cache/jobs defaults | Approved |

---

## 1. AUTHENTICATION FLOWS

### 1.1 Web (session) — Blade UI
```
Browser → POST /login { email, password, (totp?) }
  → Throttle/lockout middleware (T-4.4)
  → Validate credentials (bcrypt verify, cost 12)
      fail → increment lockout counter → E-CORE-005 on 5th → 401
  → MFA required for role? (admin) and enabled?
      yes → challenge TOTP → verify (T-4.5)
  → Regenerate session id (anti-fixation, T-4.6)
  → E-CORE-002 UserLoggedIn (audit: IP/UA)  [LogAuditEvent]
  → Redirect to dashboard
Logout → POST /logout → invalidate session → E-CORE-003
```

### 1.2 API (token) — Sanctum
```
Client → POST /api/v1/auth/login { email, password, (totp?) }
  → Throttle/lockout
  → Validate credentials → MFA if required
  → Issue Sanctum token (abilities, expires_at)
  → E-CORE-002 (audit)
  → 200 { token, user, permissions[] }
Subsequent → Authorization: Bearer <token> → Sanctum guard → RBAC/Policy
Logout → POST /api/v1/auth/logout → revoke current token → E-CORE-003
```

Guards: `web` (session) + `sanctum` (token). Eloquent user provider points at the
`core_users` model. Default-deny authorization (Task 5) gates everything past auth.

---

## 2. PASSWORD RESET FLOW (D-041)

```
Request → POST /forgot-password { email }
  → Always respond 200 (no account enumeration)
  → If user exists: create HASHED token in password_reset_tokens (PK email)
  → Email reset link via Brevo (queued) — but see auth-critical mail note below
  → Throttle: auth.passwords.users.throttle (e.g. 60s between requests)

Reset → POST /reset-password { email, token, password, password_confirmation }
  → Broker verifies token hash + not expired (auth.passwords.users.expire, e.g. 60 min)
  → Validate new password (policy + HIBP, T-4.3); block reuse (history)
  → Update core_users.password (bcrypt) → E-CORE-004 PasswordChanged
  → Revoke other sessions/tokens (T-4.6 / Sanctum) → SendPasswordChangedAlert
  → Delete the reset token row
```

Auth-critical mail (D-039 SPOF-04): reset email is sent on a high-priority/
synchronous path with `MAIL_FALLBACK_MAILER` if Brevo fails — it must not sit
behind the 5-minute shared-hosting cron queue.

---

## 3. MFA FLOW (TOTP) — D-039 / F-5

```
Enrol → POST /api/v1/profile/mfa/enrol
  → Generate TOTP secret (google2fa)
  → Store ENCRYPTED in core_users.mfa_secret (Laravel encrypted cast — F-5/AF-1)
  → Return provisioning QR (bacon-qr-code) — secret shown once
Confirm → POST /mfa/confirm { totp }
  → Verify code → set mfa_enabled = true → generate recovery codes (hashed)
  → E-CORE audit; notify user

Login challenge (admin roles REQUIRED; others optional):
  credentials OK → if mfa_enabled → require valid TOTP (or recovery code)
  → window tolerance ±1 step; rate-limit attempts; recovery code single-use
Disable → requires re-auth + (admin) approval; audited
```

Rules: NO plaintext secret ever (F-5, binding); secret encrypted at rest;
recovery codes stored hashed; admin MFA enforced at the guard.

---

## 4. SESSION STRATEGY (D-037 / D-039)

| Aspect | Phase 1 (shared) | VPS |
|---|---|---|
| Driver | `file` (M-1 — spares DB connections, LIM-08) | `redis` (config-only) |
| Cookie | httpOnly + Secure + SameSite=Strict (T-4.6) | same |
| Lifetime | SESSION_LIFETIME=120 min | same |
| Fixation | regenerate id on login + privilege change | same |
| sys_sessions | provisioned but INACTIVE (file driver) | redis (inactive) |

Privilege change (role assign/revoke, password change) regenerates the session and
revokes other tokens. Secure cookie requires HTTPS (Cloudflare/SSL, D-039).

---

## 5. SANCTUM STRATEGY (D-021 / D-023)

| Aspect | Approach |
|---|---|
| Token type | Personal access token (bearer); hashed at rest (SHA-256) |
| Abilities | Scoped per token (least privilege); default minimal |
| Expiry | SANCTUM_TOKEN_EXPIRATION=1440 min (24h); remember-me longer per policy |
| Revocation | On logout, password change, deactivation → delete token rows |
| Stateful | SANCTUM_STATEFUL_DOMAINS = platform domain (SPA cookie option) |
| Storage | personal_access_tokens (Task 3) |

API auth never uses sessions; CSRF applies to web only (T-9.3). Tokens carry no
PII; permissions resolved server-side via Spatie + Policies (Task 5).

---

## 6. LOCKOUT STRATEGY (D-039 / E-CORE-005)

```
Track failed attempts per (email + IP) in cache (sys_cache/file).
Threshold: 5 consecutive failures → lock 15 min, exponential backoff on repeats.
On lock → E-CORE-005 AccountLocked → SendAccountLockedAlert + NotifySecurityTeam
         → LogAuditEvent.
No account enumeration: identical response/timing for unknown vs wrong-password.
Successful login resets the counter. Lockout independent of password reset.
```

Rate limiting (T-9.2) is a separate, coarser per-IP throttle; lockout is the
credential-specific control. Both apply to web and API login.

---

## 7. GDPR / NDPA EXPORT & DELETE IMPLICATIONS (D-006 / E-CORE-009/010)

### Export (E-CORE-009)
- Endpoint: `GET /api/v1/profile/data-export` (authenticated, rate-limited, audited).
- Includes auth-domain data: profile (core_users minus secrets), consent ledger,
  notification preferences, login/audit references about the subject.
- EXCLUDES: password hash, `mfa_secret`, tokens (secrets are never exported).

### Delete / Right to Erasure (E-CORE-010)
- Endpoint: `POST /api/v1/profile/delete`.
- Soft-delete `core_users` + NULLify PII (name/email/IP) → pseudonymise.
- Revoke ALL Sanctum tokens + sessions immediately (synchronous).
- Consent rows: retained as anonymised proof-of-process (lawful basis), not hard-deleted.
- Audit trail: preserved (no FK to user, hashes only) — the erasure itself is audited.
- Retention policy (core_retention_policies) governs eventual purge.

---

## 8. SECURITY REVIEW

| Control | Decision | Status |
|---|---|---|
| Bcrypt cost 12; no plaintext password | D-039 | Planned |
| HIBP breach check (k-anonymity, built-in `uncompromised`) | D-039 | Planned (T-4.3) |
| Password history (no reuse of last N) | D-039 | Planned |
| MFA secret encrypted at rest; recovery codes hashed | F-5/D-042 | Planned (AF-1 column fix first) |
| Admin MFA enforced | Role Matrix | Planned |
| Lockout + no enumeration | D-039 | Planned |
| Session: httpOnly/Secure/SameSite + regeneration | D-039 | Planned |
| Sanctum token hashed + scoped + expiry + revocation | D-021 | Planned |
| Auth-critical mail synchronous + fallback | D-039 SPOF-04 | Planned (F-2 + mail config) |
| All auth events audited (E-CORE-002…010) | D-006 | Planned (T-6 wiring) |
| Secrets never exported (GDPR) | D-006 | Planned |

**Findings:**
- **AF-1 (must fix first):** mfa_secret column width — TEXT, not VARCHAR(64).
- **AF-2:** confirm password reset email uses the synchronous/fallback path (not the cron queue) — SPOF-04.
- **AF-3:** recovery-code storage must be hashed (treat like passwords).
No critical blockers beyond AF-1 (a one-line column change).

---

## 9. DECISION ID MAPPING

| Flow / Control | Decision IDs |
|---|---|
| Web + API authentication | D-021, D-023 |
| Password policy + HIBP | D-039 |
| Password reset (table + flow) | D-041, D-006 |
| MFA (TOTP, encrypted secret) | D-039, F-5/D-042 |
| Session strategy | D-037, D-039 |
| Sanctum tokens | D-021, D-023 |
| Lockout | D-039 (E-CORE-005) |
| Notifications (reset/lockout/welcome) | D-022 |
| Audit of auth events | D-006, D-039 (E-CORE-002…010) |
| GDPR export/delete | D-006 (E-CORE-009/010) |
| Driver→table wiring (sessions/queue/cache) | D-037 (F-2) |
| Config-only migration preserved | D-037 |

---

## 10. TASK 4 EXECUTION OUTLINE (on approval — for reference, not code)

1. AF-1 column fix + F-3 password_reset_tokens migration + F-2 config wiring.
2. T-4.1 web auth · T-4.2 Sanctum API.
3. T-4.3 password policy + HIBP · T-4.4 lockout.
4. T-4.5 MFA (encrypted secret) · T-4.6 session hardening.
5. T-4.7 GDPR export/delete.
6. Tests for each (lockout, authz deny, secret-never-exported, reset expiry).

Note: Task 4 is the first task that introduces **models** (e.g., the User model
with HasApiTokens, HasRoles, encrypted mfa_secret cast) — expected and in scope
for Authentication, distinct from Task 3's migrations-only constraint.

---

## APPROVAL SECTION

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security Officer | | | | |
| Technical Lead | | | | |

**Status:** Planning complete. **Do not generate Task 4 code until approved.**
Approval should also confirm AF-1 (mfa_secret → TEXT) so it is applied at Task 4 start.
