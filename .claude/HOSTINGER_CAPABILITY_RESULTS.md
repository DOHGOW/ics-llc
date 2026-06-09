# HOSTINGER CAPABILITY RESULTS
# ICS Enterprise Ecosystem Platform — D-049 Gate, Step 5

Version: 1.0
Date: 2026-06-05
Status: NOT EXECUTED — no access to the Hostinger host from this environment.
Author: Lead Architect

> **Honesty statement:** The Hostinger capability spike requires executing checks **on the actual
> Hostinger account/host**. This workspace is a local Windows + XAMPP development machine, not Hostinger.
> No SSH/host access to Hostinger was available, so **every host check below is NOT EXECUTED**. Nothing
> here may be read as a Hostinger PASS. Where the *local* box gives a comparable data point, it is noted
> explicitly as **local-only (not Hostinger)** and does not satisfy the gate.

---

## RESULT: ⏸ NOT EXECUTED (no Hostinger access)

---

## HOST CHECKLIST (actual)

| Check | Required | Hostinger result | Local-only datapoint (NOT the host) |
|---|---|---|---|
| PHP version | 8.3 | **NOT EXECUTED** | local XAMPP PHP 8.2.12 (below target) |
| PHP `intl` | enabled | **NOT EXECUTED** | local: **absent** |
| PHP `gd` / `zip` / `pdo_mysql` / `mbstring` / `bcmath` | enabled | **NOT EXECUTED** | local: present |
| PHP `openssl` | enabled | **NOT EXECUTED** | local: present (double-load warning) |
| MySQL version | **MySQL 8** | **NOT EXECUTED** | local: MariaDB 10.4.32 (engine mismatch) |
| Cron support (scheduler: `billing:reconcile` hourly, etc.) | required | **NOT EXECUTED** | n/a |
| Webhook reachability (public `POST /api/v1/billing/webhooks/{gateway}`) | required (Paystack) | **NOT EXECUTED** | n/a |
| SSL / HTTPS | required | **NOT EXECUTED** | n/a |
| `.env` isolation (off web root) | required | **NOT EXECUTED** | n/a |
| Trusted proxy / real client IP (Cloudflare) | required (D-039/T9-4) | **NOT EXECUTED** | n/a |
| Mail transport | required | **NOT EXECUTED** | n/a |
| Filesystem perms / `storage` writable / public symlink | required | **NOT EXECUTED** | n/a |

**PASS / PARTIAL / FAIL verdict:** ⏸ **NOT EXECUTED** — cannot be PASS/PARTIAL/FAIL without running on
Hostinger. Per the requirement "do not assume success," no host capability is certified.

---

## WHAT THIS MEANS

- The **host capability spike (VPS_MIGRATION_CHECKLIST Part A.4)** remains **OUTSTANDING** and must be run
  against the real Hostinger account by someone with host access (SSH or hPanel + PHP selector).
- The most likely host risks to validate first, given the local findings and architecture:
  1. **PHP 8.3 + `ext-intl` availability** on the Hostinger plan (local box lacks both).
  2. **MySQL 8** availability (vs MariaDB) for JSON/FULLTEXT/ENUM + TenantScope parity.
  3. **Cron** for the scheduler (billing reconciliation, D-084) — shared plans vary.
  4. **Public webhook reachability + SSL** for Paystack signature delivery.
  5. **`.env` off web root** and audit-log immutability (R-010 — shared-tenancy isolation).
- Standing risk **R-010 (HIGH)**: confidential CRM/Billing/PII on shared hosting has weak process
  isolation — a genuine VPS-migration trigger; confirm during the spike.

---

## CONCLUSION

Host verification is **NOT EXECUTED**. D-049 Step 5 is **OPEN**. A Hostinger capability spike must be
performed on the actual host before production certification; it could not be performed from this
environment.
