# SPRINT 1 GO-LIVE CHECKLIST
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: To be executed before Sprint 1 sign-off / Sprint 2 start
Owner: Technical Lead / DevOps

This checklist converts the architecture into operational reality. Every item must
PASS (or be a recorded, accepted exception) before Sprint 1 is "done" and before
Sprint 2 business-module development begins.

---

## A. ENVIRONMENT VERIFICATION
- [ ] `composer create-project` skeleton + ICS overlay merged; stock default
      migrations (users/cache/jobs) removed (F-1)
- [ ] `composer install --no-dev` succeeds; `composer.lock` committed
- [ ] `npm install` + `npm run build` succeed (Vite assets built)
- [ ] PHP 8.3 + extensions present incl. **intl** (currency) and pdo_mysql (spike CHECK 02 / C-2)
- [ ] DB engine confirmed = MySQL 8.x; `DB_IMAGE`/CI tag aligned to the exact minor (D-002 / T-2.4)
- [ ] `php artisan migrate --force` clean; `php artisan db:seed` creates 13 roles + ~150 perms
- [ ] Cron entry active (queue processor + scheduler); queue processes a test job
- [ ] Hostinger capability spike executed; HOSTINGER_LIMITATIONS_REGISTER finalized (R-013)

## B. SECURITY VERIFICATION (D-039)
- [ ] `.env` OUTSIDE web root; URL fetch of `.env` returns 403/404 (SEC-02 / spike CHECK 01/03)
- [ ] Security headers present on responses (HSTS over HTTPS, CSP, X-Frame, X-Content-Type, Referrer-Policy, Permissions-Policy)
- [ ] CSP strict; **no 'unsafe-eval'**; Alpine CSP build in use (D-048)
- [ ] Rate limiters enforce 429 (login/reset/mfa/public-forms/api)
- [ ] Session cookies Secure + HttpOnly + SameSite=Strict
- [ ] CSRF active on web; API token-auth exempt
- [ ] `TRUSTED_PROXIES` set to explicit Cloudflare ranges (not '*') (T9-3)
- [ ] Cloudflare fronting (CDN/WAF/bot); TLS valid; `expose_php=Off`
- [ ] `composer audit` + gitleaks clean in CI

## C. LOCALIZATION VERIFICATION (D-014 / D-028)
- [ ] `<html lang="en">` and `<html dir="ltr">` present (WCAG 3.1.1)
- [ ] Locale registry is config-driven; `APP_ACTIVE_LOCALES=en`
- [ ] French/Arabic dormant but supported (files present, inactive)
- [ ] RTL flips to `dir="rtl"` when `ar` activated (test-config only)
- [ ] Date/time + currency helpers function (intl available)

## D. AUDIT VERIFICATION (D-006 / D-046)
- [ ] All lifecycle/auth/role events write append-only audit rows
- [ ] Audit rows immutable (update/delete throw) — proven by test
- [ ] Super Admin actions recorded as sensitivity=high; 6 sensitive categories
- [ ] Escalation request/approve/reject audited (high)
- [ ] `platform.audit.read` restricted to Super/Platform Admin
- [ ] Off-box audit export configured OR accepted as deferred (F-6)

## E. AUTHENTICATION VERIFICATION (D-021 / D-039)
- [ ] Web + API (Sanctum) login works; token expiry + revocation
- [ ] Password policy (12+, complexity) + HIBP breach check enforced
- [ ] Lockout after 5 attempts; no account enumeration
- [ ] MFA TOTP enrol/verify; admin MFA enforced (mfa.admin)
- [ ] mfa_secret encrypted at rest; recovery codes hashed (AF-1/AF-3)
- [ ] Password reset immediate via failover mailer (AF-2); pending users cannot log in

## F. AUTHORIZATION VERIFICATION (D-044 / D-047)
- [ ] Default-deny proven; Gate::before grants only Super Admin
- [ ] Role mapping matches PERMISSION_MATRIX (conformance test green)
- [ ] Escalation guard: no grant at/above own level; Super Admin four-eyes
- [ ] Four-eyes: approver ≠ requester; expiry; immutable audit
- [ ] Last-Super-Admin protection (service + policy)
- [ ] Token revoke on role change; reactivation strips Super Admin

## G. BACKUP VERIFICATION (SPOF-02)
- [ ] Automated off-box backup of DB + `storage/` configured
- [ ] Restore tested (DB import + files) into a scratch environment
- [ ] Documented restore runbook; backup retention defined
- [ ] Pre-deploy/pre-migration backup step in the deploy process

---

## SIGN-OFF
GO only when all CRITICAL items pass and every exception is recorded + accepted.

| Role | Name | GO / NO-GO | Signature | Date |
|---|---|---|---|---|
| Technical Lead | | | | |
| DevOps | | | | |
| Lead Architect | | | | |
| Platform Owner | | | | |
