# SECURITY EXCEPTION REGISTER
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05
Status: ACTIVE — formal register of accepted security exceptions (D-089 governance).
Author: Lead Architect
Owner: Platform Owner (acceptance) · Lead Architect (technical) · Security/Compliance (review)

> Every entry is a TIME-BOUND, JUSTIFIED, COMPENSATING-CONTROLLED acceptance. Exceptions are enforced via
> `composer.json` `config.audit.ignore` (so `composer audit` stays GREEN) while `block-insecure` remains
> ENABLED for all OTHER advisories. Each must have an exit condition.

---

## SEC-EXC-001 — CVE-2026-48019 (Laravel CRLF injection in default email rule)

| Field | Value |
|---|---|
| Advisory IDs | PKSA-mdq4-51ck-6kdq · GHSA-5vg9-5847-vvmq · CVE-2026-48019 |
| Package | laravel/framework (installed v11.54.0) |
| Affected | all 11.x (`>=11.0.0,<12.0.0`); fixed only in 12.60.0+ / 13.10.0+ |
| Severity | (vendor: unscored) — assessed LOW on this platform (see compensating controls) |
| Decision | **ACCEPTED (temporary)** — D-089 OPTION A |
| Enforcement | `config.audit.ignore: ["PKSA-mdq4-51ck-6kdq","GHSA-5vg9-5847-vvmq","CVE-2026-48019"]`; `block-insecure: true` |
| Compensating controls | (1) all mail via Notifications/Symfony Mailer (header-encoding rejects CRLF); (2) NO raw email-header construction in the codebase (verified by grep); (3) validated emails are predominantly STORED, not headered |
| Residual risk | LOW — primitive exists but lacks an exploitable raw-header sink |
| **Exit condition** | Laravel 12.60+ upgrade (D-089 OPTION B) **before production certification**, OR a confirmed patched 11.x backport |
| Hardening follow-up | tighten the email rule (explicit RFC/strict mode) + a lint guard against raw email-header construction |
| Review cadence | re-evaluate at each release; MUST be closed before go-live |
| Status | OPEN (accepted) |

Full technical justification: CVE_2026_48019_ACCEPTANCE_REPORT.md.

---

## REGISTER POLICY

- `block-insecure` stays **ENABLED** — any NEW advisory blocks resolution until explicitly triaged here.
- No exception is open-ended: each has an exit condition + review cadence.
- The register is reviewed at every release gate and at production certification.
- Adding an exception requires Platform Owner acceptance + Security/Compliance review.

## OPEN EXCEPTIONS SUMMARY

| ID | Subject | Severity | Exit condition | Production-blocking? |
|---|---|---|---|---|
| SEC-EXC-001 | CVE-2026-48019 (Laravel email-rule CRLF) | LOW (assessed) | Laravel 12.60+ upgrade | **YES — must close before go-live** |
