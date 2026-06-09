# HOSTINGER CAPABILITY SPIKE — RUNBOOK
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: Awaiting Execution
Owner: Technical Lead / DevOps
Decision References: D-002, D-003, D-024, D-031, D-037, D-039

---

## EXECUTIVE SUMMARY

This runbook validates every Phase 1 deployment assumption against the actual
Hostinger Premium Shared Hosting environment **before** any application code is
written. It exists because several architecture decisions depend on host
capabilities that cannot be assumed — specifically database engine/version,
TRIGGER privilege, cron granularity, connection limits, document-root control,
and outbound API access (ARCHITECTURE_REVIEW_REPORT Part A.4).

This document is a **diagnostic runbook**, not application code. Every command
below is a throwaway probe run by an operator over SSH, in hPanel, or in a
MySQL client. Nothing here becomes part of the Laravel application.

How to use:
1. Run each check in order. Record the actual result.
2. Mark PASS / FAIL / PARTIAL in the checklist (Section 19).
3. For every FAIL or PARTIAL, open an entry in HOSTINGER_LIMITATIONS_REGISTER.md.
4. Submit the completed checklist for Host Capability Review approval.
5. Implementation does not begin until that review is approved.

Legend:
  PASS    — meets the architecture assumption as-is
  PARTIAL — works with a defined workaround
  FAIL    — blocks the assumption; requires mitigation or VPS

---

## PRE-REQUISITES

- [ ] hPanel administrative access
- [ ] SSH access enabled (Hostinger hPanel → Advanced → SSH Access)
- [ ] An SSH client; a MySQL client (or phpMyAdmin via hPanel)
- [ ] A scratch database + DB user created for testing
- [ ] A scratch subdomain (e.g., spike.ics-domain) for isolation

Note on SSH: if SSH is unavailable on the plan, every SSH check below has an
hPanel or phpMyAdmin equivalent noted. Lack of SSH is itself a finding
(affects Composer-based deployment — see Check 02).

---

## CHECK 01 — DOCUMENT ROOT CONFIGURATION

**Assumption:** Web root can be set to Laravel's `/public` only; framework files
sit above the web root (D-039 SEC-02).

**Verification method:**
- hPanel → Websites → Manage → check whether "Change website root directory" /
  document root is configurable to a subdirectory.
- SSH: `ls -la ~/` and `ls -la ~/public_html` to learn the directory layout.
- Test: create `spike/public/index.php` and point the subdomain root at
  `spike/public`; confirm files in `spike/` (above public) are NOT web-reachable:
  `curl -i https://spike.<domain>/../.env-test`

**Expected result:** Document root can target `/public`; parent directory not served.

**Failure impact:** If the whole Laravel root is web-served, `.env`, `storage/`,
`vendor/`, and `config/` become reachable → credential exposure (CRITICAL).

**Mitigation strategy:**
- Preferred: set document root to `…/public`.
- Fallback A: place Laravel framework above `public_html`, put `public/` contents
  inside `public_html`, and edit `index.php` paths.
- Fallback B (weakest): keep structure but add strict `.htaccess` deny rules for
  `.env`, `/storage`, `/vendor`, `/config`, `/database`. Do not rely on this alone.

---

## CHECK 02 — LARAVEL DEPLOYMENT COMPATIBILITY

**Assumption:** PHP 8.3, Composer 2.x, and all required PHP extensions present (D-002, D-020).

**Verification method:**
- `php -v` (confirm 8.3.x); if multiple versions, `php8.3 -v`
- `composer --version` (expect 2.x); if absent, check hPanel → Advanced → Composer
- `php -m` and confirm these are listed:
  `bcmath ctype curl dom fileinfo json mbstring openssl pcre pdo pdo_mysql
   tokenizer xml gd zip intl`
- Confirm CLI and web use the same PHP version (hPanel PHP Configuration).

**Expected result:** PHP 8.3.x, Composer 2.x, all extensions present.

**Failure impact:** Missing extensions break Laravel boot, image handling (gd),
PDF/i18n (intl), zip uploads, or DB access (pdo_mysql).

**Mitigation strategy:**
- Enable PHP 8.3 + missing extensions via hPanel PHP Configuration.
- If Composer is unavailable on host: run `composer install` locally/CI and deploy
  the built `vendor/` via Git (D-003 Git deployment).

---

## CHECK 03 — .ENV PLACEMENT OPTIONS

**Assumption:** `.env` can reside outside the web root (D-039 SEC-02).

**Verification method:**
- Confirm Check 01 layout allows a directory above the web root with write access.
- SSH: place a test file above `public_html`; `curl` its would-be URL; confirm 403/404.
- Confirm Laravel base path can load `.env` from the parent (documented capability;
  validate the directory is writable and readable by PHP).

**Expected result:** `.env` lives above web root, unreachable by URL.

**Failure impact:** Secrets (APP_KEY, DB, Paystack, Gemini, Brevo, WhatsApp) exposed.

**Mitigation strategy:** If structure forbids it, combine `.htaccess` hard-deny on
`.env` with file permissions `600` and rotate keys; flag as residual risk and a
VPS migration driver.

---

## CHECK 04 — CRON SCHEDULING LIMITS

**Assumption:** 1-minute cron (`* * * * *`) for `schedule:run`; ≤5-min for the
queue processor (D-037, Blueprint §15).

**Verification method:**
- hPanel → Advanced → Cron Jobs → inspect the minimum allowed interval.
- Create a test cron writing a timestamp to a file every minute:
  `* * * * * /usr/bin/date >> ~/spike/cron.log`
- After 5 minutes, inspect `cron.log` for 1-minute cadence.

**Expected result:** 1-minute cron available.

**Failure impact:** If minimum is 5–15 min: Laravel scheduler loses sub-5-min
granularity; queue/notification latency rises; time-sensitive jobs delayed (HOST-02).

**Mitigation strategy:**
- Accept higher latency for non-critical mail.
- Send auth-critical mail synchronously (D-039 SPOF-04).
- Self-ping pattern (a single cron looping internally) only if exec allowed (Check 11).
- Document actual interval in the Limitations Register; it is a VPS driver.

---

## CHECK 05 — PHP MEMORY LIMITS

**Assumption:** `memory_limit` ≥ 256 MB (DOMPDF invoices/certificates — D-031, PERF-04).

**Verification method:**
- `php -i | grep memory_limit` (CLI) and a phpinfo probe for web SAPI.
- hPanel → PHP Configuration → view/raise `memory_limit`.

**Expected result:** ≥ 256 MB (web and CLI).

**Failure impact:** OOM during PDF generation, large analytics aggregation, or
image processing → failed invoices/certificates, 500 errors.

**Mitigation strategy:** Raise via hPanel; if capped below 256 MB, queue and chunk
PDF/analytics work, or defer PDF generation; flag for VPS.

---

## CHECK 06 — PHP EXECUTION LIMITS

**Assumption:** CLI execution generous (cron jobs); web `max_execution_time` ≥ 60s.

**Verification method:**
- `php -i | grep max_execution_time` (CLI; often 0/unlimited)
- phpinfo probe for web SAPI `max_execution_time`, `max_input_time`.
- Time a representative chunked aggregation query against scratch data.

**Expected result:** CLI unlimited or ≥300s; web ≥60s.

**Failure impact:** Long analytics cron or DOMPDF killed mid-run; partial writes (PERF-05).

**Mitigation strategy:** Keep Tier-1 analytics cron chunked and idempotent within
the limit; DW ETL stays OFF on shared (D-037 flag); raise web limit via hPanel.

---

## CHECK 07 — MYSQL / DATABASE VERSION

**Assumption:** MySQL 8.0+ (D-002). **High-likelihood finding: Hostinger shared
hosting commonly provides MariaDB, not MySQL 8.**

**Verification method:**
- SQL: `SELECT VERSION();`
- SQL: `SHOW VARIABLES LIKE 'version_comment';`

**Expected result:** MySQL 8.0+ — OR MariaDB 10.4+ (compatibility-verify).

**Failure impact:** MariaDB differs from MySQL 8 in: JSON functions and storage,
`utf8mb4` defaults, CTE/window-function edge cases, FULLTEXT ranking, and some
generated-column behavior. The schema (DATABASE_BLUEPRINT) uses JSON columns and
FULLTEXT heavily — incompatibilities would surface there.

**Mitigation strategy:**
- If MariaDB: confirm version ≥ 10.4 (JSON alias support) and run the JSON +
  FULLTEXT compatibility probes (Checks 08/09 extended). Laravel supports MariaDB.
- Record the actual engine/version in the Limitations Register and pin the local/
  staging DB to the SAME engine to avoid dev/prod drift.
- Treat true MySQL 8 as a VPS/cloud-tier guarantee.

---

## CHECK 08 — MYSQL TRIGGER PERMISSIONS

**Assumption:** `CREATE TRIGGER` allowed, for audit-log immutability (DATABASE_BLUEPRINT
core_audit_logs; D-039 SEC-03).

**Verification method:**
- SQL: `SHOW GRANTS FOR CURRENT_USER();` → look for `TRIGGER`.
- Probe:
  ```
  CREATE TABLE spike_t (id INT);
  CREATE TRIGGER spike_bi BEFORE INSERT ON spike_t
    FOR EACH ROW SET NEW.id = NEW.id;
  DROP TRIGGER spike_bi; DROP TABLE spike_t;
  ```

**Expected result:** TRIGGER granted on the application schema.

**Failure impact:** Cannot enforce append-only audit log at the DB layer.

**Mitigation strategy (already the D-039 plan):** Enforce audit immutability at the
application layer via a write-only repository, AND export audit logs off-box
periodically (write-once external store). DB-trigger enforcement becomes a VPS
hardening upgrade. **This check failing is acceptable** — the architecture already
plans for it.

---

## CHECK 09 — FOREIGN KEY SUPPORT

**Assumption:** InnoDB with enforced foreign keys (entire DATABASE_BLUEPRINT).

**Verification method:**
- SQL: `SHOW ENGINES;` → InnoDB = DEFAULT/YES.
- Probe:
  ```
  CREATE TABLE spike_p (id INT PRIMARY KEY) ENGINE=InnoDB;
  CREATE TABLE spike_c (id INT, pid INT,
    FOREIGN KEY (pid) REFERENCES spike_p(id)) ENGINE=InnoDB;
  INSERT INTO spike_c VALUES (1, 999);   -- expect FK error
  DROP TABLE spike_c; DROP TABLE spike_p;
  ```
- Also probe a JSON column (Check 07 link): `CREATE TABLE spike_j (d JSON);`

**Expected result:** InnoDB default; violating insert rejected; JSON column accepted.

**Failure impact:** No referential integrity / no JSON storage → core schema invalid.

**Mitigation strategy:** Ensure all migrations specify `ENGINE=InnoDB`; if JSON
unsupported (very old MariaDB), use `LONGTEXT` with app-level casting (Laravel
`json` cast handles this). Record engine/version constraints.

---

## CHECK 10 — OUTBOUND HTTPS ACCESS

**Assumption:** PHP can make outbound HTTPS to external APIs (D-026, D-031, D-022).

**Verification method:**
- SSH: `curl -I https://api.paystack.co` and to each API host.
- PHP probe (CLI): a one-line cURL to `https://generativelanguage.googleapis.com`
  checking for a TLS handshake + HTTP response (not auth).
- Confirm `allow_url_fopen` and cURL both function.

**Expected result:** Outbound HTTPS succeeds to all four API hosts.

**Failure impact:** AI, email, payment, and WhatsApp integrations all fail.

**Mitigation strategy:** If outbound is restricted/whitelisted, request the host
open the specific endpoints; if impossible, those integrations cannot run on shared
hosting → VPS driver. (Rare on Hostinger, but must be verified — HOST-05.)

---

## CHECK 11 — QUEUE PROCESSING OPTIONS

**Assumption:** Cron-driven `queue:work --stop-when-empty`; no persistent worker
on shared (D-037, HOST-01).

**Verification method:**
- SQL/CLI: check `disabled_functions` for `proc_open`, `exec`, `shell_exec`,
  `popen`: `php -i | grep disable_functions`.
- Test: schedule a cron running `php artisan queue:work --stop-when-empty` against
  the scratch app skeleton (a minimal `artisan` is enough to confirm CLI exec).
- Confirm no Supervisor / persistent process capability (expected absent).

**Expected result:** CLI artisan runs from cron; persistent workers absent (expected).

**Failure impact:** If `proc_open`/`exec` disabled, even some queue tooling fails;
if CLI artisan blocked, only `QUEUE_CONNECTION=sync` remains (no async at all).

**Mitigation strategy:** Use `QUEUE_CONNECTION=database` processed by cron; if exec
disabled, fall back to `sync` for non-critical work and accept inline processing;
real workers are the VPS upgrade (D-037).

---

## CHECK 12 — FILE UPLOAD LIMITS

**Assumption:** `upload_max_filesize` / `post_max_size` ≥ 50 MB (D-024 archives).

**Verification method:**
- `php -i | grep -E 'upload_max_filesize|post_max_size|max_file_uploads'`
- phpinfo for web SAPI.
- hPanel → PHP Configuration to view/raise.

**Expected result:** ≥ 50 MB upload and post sizes.

**Failure impact:** Large document/archive uploads (toolkits, deliverables) rejected.

**Mitigation strategy:** Raise via hPanel; for very large files use chunked upload
or push to object storage early (D-024). Video stays as external embed (decided).

---

## CHECK 13 — STORAGE LIMITS (DISK + INODES)

**Assumption:** Sufficient disk AND inode budget for files, PDFs, logs.

**Verification method:**
- hPanel → File Manager / Hosting → disk usage and inode usage.
- SSH: `df -h ~` (disk) and `find ~ -type f | wc -l` vs the plan's inode cap.

**Expected result:** Ample disk; inode usage well under the plan limit.

**Failure impact:** Inode exhaustion (many small files: cache, sessions, logs,
uploads) halts writes platform-wide even with disk free.

**Mitigation strategy:** Prune logs/cache via the retention jobs (D-006
core_retention_policies); keep sessions/cache off the filesystem if inodes are
tight (use DB or APCu); migrate uploads to object storage early (D-024).

---

## CHECK 14 — DATABASE CONNECTION LIMITS

**Assumption:** Enough concurrent MySQL connections for web + cron + analytics
(SCAL-01, SPOF-01).

**Verification method:**
- SQL: `SHOW VARIABLES LIKE 'max_connections';`
- SQL: `SHOW VARIABLES LIKE 'max_user_connections';`
- `SHOW GRANTS FOR CURRENT_USER();` → note any `MAX_USER_CONNECTIONS` clause.

**Expected result:** ≥ 25 usable concurrent connections for the app user.

**Failure impact:** Under load, web requests + cron queue + analytics exhaust the
pool → "Too many connections" errors; login fails (because sessions are also DB).

**Mitigation strategy (already SPOF-01 plan):** Move SESSION + CACHE off MySQL
(file/APCu) on shared hosting so they don't consume DB connections; keep queries
short; Redis on VPS removes the constraint.

---

## CHECK 15 — SSL SUPPORT

**Assumption:** Free Let's Encrypt SSL, auto-renew, TLS 1.2/1.3 (Blueprint §14).

**Verification method:**
- hPanel → Security → SSL → confirm free SSL available + auto-renew.
- After issue: `curl -Iv https://<domain>` and inspect protocol/cert.
- Optional: SSL Labs scan for protocol/cipher grade.

**Expected result:** Valid SSL, auto-renew on, TLS 1.2+ (1.3 preferred).

**Failure impact:** No HTTPS → breaks HSTS, secure cookies, PWA, webhooks, trust.

**Mitigation strategy:** Issue Let's Encrypt via hPanel; if TLS 1.3 absent, accept
1.2; enforce via Cloudflare (Check 16) which can terminate modern TLS.

---

## CHECK 16 — CLOUDFLARE COMPATIBILITY

**Assumption:** Cloudflare (free tier) can front the site for CDN/WAF/bot control
(D-039 SEC-09).

**Verification method:**
- hPanel → confirm DNS management and nameserver change capability (or CNAME setup).
- Determine if the plan uses Hostinger's Cloudflare-protected nameservers already.
- Plan the activation path (full nameserver delegation vs partial CNAME).

**Expected result:** Cloudflare can be placed in front via nameservers or CNAME.

**Failure impact:** Lose WAF/CDN/bot protection → exposes guest AI cost abuse
(COST-01), FULLTEXT load (PERF-01), and removes a DDoS buffer.

**Mitigation strategy:** Use Hostinger's built-in Cloudflare integration if present;
otherwise delegate nameservers to Cloudflare free tier. If neither is possible,
enforce app-level rate limiting harder and flag as residual risk.

---

## CHECK 17 — API CONNECTIVITY (GEMINI / BREVO / PAYSTACK / WHATSAPP)

**Assumption:** All four external APIs are reachable (outbound) and Paystack
webhooks are receivable (inbound) (D-026, D-022, D-031).

**Verification method (outbound — per endpoint):**
- Gemini:   `curl -I https://generativelanguage.googleapis.com`
- Brevo:    `curl -I https://api.brevo.com`
- Paystack: `curl -I https://api.paystack.co`
- WhatsApp: `curl -I https://graph.facebook.com`
- For each, confirm TLS handshake + an HTTP status (401/404 without keys is fine —
  it proves reachability).

**Verification method (inbound — Paystack webhook):**
- Confirm the host accepts inbound POST to a public path over HTTPS (place a probe
  endpoint on the scratch subdomain and POST to it from an external tool).
- Confirm no host-level WAF blocks unexpected POST bodies.

**Expected result:** All four reachable outbound; inbound HTTPS POST accepted.

**Failure impact:**
- Gemini unreachable → AI features degrade (graceful fallback exists).
- Brevo unreachable → email + password reset fail (use SMTP fallback, D-039).
- Paystack unreachable/inbound blocked → no payment capture or fulfillment.
- WhatsApp unreachable → WhatsApp channel fails (in-app + email still work).

**Mitigation strategy:** Open specific endpoints with the host if filtered; use
Brevo SMTP fallback (SPOF-04); ensure webhook path is excluded from any aggressive
host WAF rule; Paystack inbound is mandatory for revenue — if blocked, escalate to
VPS.

---

## 18. SUPPLEMENTARY CHECKS (RECOMMENDED)

| # | Check | Method | Why |
|---|---|---|---|
| S1 | Timezone = UTC | `php -i \| grep date.timezone`; `SELECT @@global.time_zone;` | Consistent timestamps (D-002) |
| S2 | OPcache enabled | `php -i \| grep opcache.enable` | Performance on shared CPU |
| S3 | APCu available | `php -m \| grep apcu` | Cache option that spares DB connections (Check 14) |
| S4 | Git available | `git --version` | Git-based deployment (D-003) |
| S5 | Symlink support | `php artisan storage:link` test / `ln -s` | Public file delivery (D-024) |
| S6 | Backup tooling | hPanel backups + `mysqldump` access | Off-box backup (SPOF-02) |
| S7 | Error display OFF | `php -i \| grep display_errors` | No stack traces to users (Blueprint §14) |

---

## 19. PASS / FAIL CHECKLIST

Record actual results here and attach to the Host Capability Review.

| # | Capability | Assumption | Result (PASS/PARTIAL/FAIL) | Actual Value / Note | Register Entry? |
|---|---|---|---|---|---|
| 01 | Document root → /public | settable | ☐ | | |
| 02 | Laravel/PHP 8.3/Composer/exts | present | ☐ | | |
| 03 | .env outside web root | possible | ☐ | | |
| 04 | Cron minimum interval | 1 min | ☐ | | |
| 05 | PHP memory_limit | ≥256 MB | ☐ | | |
| 06 | PHP execution time | CLI gen / web ≥60s | ☐ | | |
| 07 | DB engine + version | MySQL 8+ or MariaDB 10.4+ | ☐ | | |
| 08 | CREATE TRIGGER privilege | granted | ☐ | | |
| 09 | InnoDB + FK + JSON | enforced | ☐ | | |
| 10 | Outbound HTTPS | allowed | ☐ | | |
| 11 | Queue processing (cron/exec) | works | ☐ | | |
| 12 | Upload limits | ≥50 MB | ☐ | | |
| 13 | Disk + inode budget | sufficient | ☐ | | |
| 14 | DB connection limit | ≥25 | ☐ | | |
| 15 | SSL (Let's Encrypt) | available + auto-renew | ☐ | | |
| 16 | Cloudflare compatibility | possible | ☐ | | |
| 17a | Gemini reachable | yes | ☐ | | |
| 17b | Brevo reachable | yes | ☐ | | |
| 17c | Paystack out + webhook in | yes | ☐ | | |
| 17d | WhatsApp reachable | yes | ☐ | | |
| S1–S7 | Supplementary | see §18 | ☐ | | |

**Scoring guidance:**
- All CRITICAL checks (01, 02, 03, 09, 10, 15, 17c) must be PASS to proceed on shared hosting.
- Expected PARTIAL/FAIL (acceptable, already mitigated): 04 (cron), 07 (MariaDB), 08 (trigger), 11 (no persistent worker), 14 (connection cap). These are known shared-hosting traits with planned workarounds.
- Any CRITICAL FAIL → escalate the VPS decision (D-037) for that capability.

---

## 20. DECISION GATE

```
IF all CRITICAL checks PASS and all PARTIAL/FAIL items have a recorded
   mitigation in HOSTINGER_LIMITATIONS_REGISTER.md
THEN  Host Capability Review = APPROVED → Phase 1 build may begin
ELSE  resolve blockers, or invoke VPS migration (D-037) for the affected capability
```

---

## APPROVAL SECTION — HOST CAPABILITY REVIEW

| Role | Name | Decision (Approve / Conditional / Reject) | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Technical Lead | | | | |
| DevOps / Operations | | | | |

**Status:** Awaiting Execution, then Review
**Gate:** PHASE_1_SPRINT_1_IMPLEMENTATION_PLAN.md may not start until this review is APPROVED.
