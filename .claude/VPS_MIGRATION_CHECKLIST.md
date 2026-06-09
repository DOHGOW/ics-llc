# VPS MIGRATION CHECKLIST & DEPLOYMENT STRATEGY
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: Awaiting Review
Author: Chief Enterprise Architect

Decision References: D-003 (Hosting Phases), D-037 (VPS-Ready / Shared-First),
D-039 (Security Hardening), D-032 (Data Warehouse), D-014 (i18n), D-004 (Tenant)

---

## EXECUTIVE SUMMARY

The ICS platform is architected VPS-first and deployed shared-hosting-first.
The same codebase and the same database schema run in both environments. The
environment is selected by `.env` configuration only.

This document defines:
- Part A — Deployment Strategy (how the platform is deployed and operated)
- Part B — Config-Only Migration Contract (the rules that make migration safe)
- Part C — VPS Migration Checklist (the step-by-step switch, when justified)
- Part D — Migration Triggers (when to migrate)
- Part E — Rollback Plan

**The migration promise (D-037):** moving from Hostinger Premium Shared Hosting to
Hostinger VPS requires **configuration changes only** — no database redesign, no
application redesign, no code rewrites.

---

# PART A — DEPLOYMENT STRATEGY

## A.1 Environments

| Environment | Host | Purpose |
|---|---|---|
| Local | Developer machine / Laravel Sail | Development |
| Staging | Hostinger subdomain | Pre-production verification |
| Production (Phase 1) | Hostinger Premium Shared Hosting | Live, shared-runtime profile |
| Production (Phase 2) | Hostinger VPS | Live, VPS-runtime profile |
| Production (Phase 3) | Cloud (managed DB + object storage) | Scale-out |

## A.2 Shared-Hosting Runtime Profile (Phase 1)

| Concern | Phase 1 Setting |
|---|---|
| Queue | `QUEUE_CONNECTION=database`, processed by cron `queue:work --stop-when-empty` every 5 min |
| Cache | `CACHE_STORE=file` (or `database`) — prefer file/APCu to spare DB connections |
| Session | `SESSION_DRIVER=file` — keeps a DB hiccup from also killing sessions (SPOF-01) |
| Scheduler | Single cron entry runs `schedule:run`; honour the host's true minimum interval (HOST-02) |
| Filesystem | `FILESYSTEM_DISK=local` (storage/app) |
| Warehouse ETL | `ICS_WAREHOUSE_ETL_ENABLED=false` |
| Heavy jobs | `ICS_HEAVY_JOBS=false` |
| AI volume | `ICS_AI_HIGH_VOLUME=false` — low caps, guest endpoints hard-limited |
| Community scaling | `ICS_COMMUNITY_SCALING=false` |
| Auth-critical mail | sent synchronously, not via 5-min queue (D-039 SPOF-04) |
| Edge | Cloudflare in front (CDN/WAF/bot) — D-039 SEC-09 |

## A.3 Deployment Process (Shared — Phase 1)

```
1. git push to main
2. Pull on host (Hostinger Git deploy or manual SSH pull)
3. composer install --no-dev --optimize-autoloader
4. php artisan migrate --force
5. php artisan config:cache
6. php artisan route:cache
7. php artisan view:cache
8. php artisan storage:link
9. Verify cron entry active (schedule:run + queue processor)
```

## A.4 Pre-Deployment Host Capability Spike (run BEFORE building — P0)

These shared-hosting unknowns must be verified first (ARCHITECTURE_REVIEW P0):

- [ ] Document root can be set to `/public` only (HOST-04, SEC-02)
- [ ] `.env` can live OUTSIDE the web root (SEC-02)
- [ ] Minimum cron interval confirmed (1 min ideal; 5–15 min changes queue latency) (HOST-02)
- [ ] MySQL `TRIGGER` privilege available? If NO → app-layer audit immutability only (SEC-03)
- [ ] MySQL max concurrent connections (plan cache/session off DB if low) (SCAL-01)
- [ ] PHP memory limit (DOMPDF invoice/cert generation fits?) (PERF-04)
- [ ] Max execution time (chunk analytics cron within it) (PERF-05)
- [ ] Outbound HTTPS to Gemini / Brevo / WhatsApp / Paystack allowed (HOST-05)
- [ ] `proc_open` / `exec` availability for `queue:work` (HOST-05)
- [ ] Disk quota vs expected media/PDF growth (HOST-06)
- [ ] Automated off-box backup of DB + files configured (SPOF-02)

---

# PART B — CONFIG-ONLY MIGRATION CONTRACT

These rules are binding on every developer. They are what make Part C a
configuration change rather than a rewrite. Violating any of them breaks the
D-037 guarantee and is rejected in code review.

## B.1 The Three Guarantees

| # | Rule | CI / Review Gate |
|---|---|---|
| 1 | No infrastructure driver name is hardcoded. Queue/cache/session/filesystem/mail are resolved from `config()`/`.env`. | CI greps for driver literals outside `config/`; build fails on match. |
| 2 | Every non-instant Listener implements `ShouldQueue`. | Review checklist; listeners doing I/O without ShouldQueue are rejected. |
| 3 | Deferred runtime behaviours are wrapped in `if (config('ics.<flag>'))`. | Review checklist; ETL/heavy/AI-volume/community-scaling code must be flag-gated. |

## B.2 Schema Identity Rule

The database schema is IDENTICAL in both environments. The following exist from
the first Phase 1 migration and are never added "later":

- [ ] `dw_*` Data Warehouse tables (D-032) — present; ETL automation flag-gated
- [ ] `i18n_translations` (D-014) — present; populated when FR/AR arrive
- [ ] `tenant_id` on every business table (D-004) — present and nullable
- [ ] `content_engagement_events` unified table (D-038)

No migration is environment-specific. `php artisan migrate` produces the same
schema on shared and VPS.

## B.3 What May Differ Between Environments

ONLY `.env`. Nothing else. Specifically:

```
QUEUE_CONNECTION   CACHE_STORE   SESSION_DRIVER   FILESYSTEM_DISK
MAIL_MAILER        MAIL_FALLBACK_MAILER
ICS_WAREHOUSE_ETL_ENABLED   ICS_HEAVY_JOBS
ICS_AI_HIGH_VOLUME          ICS_COMMUNITY_SCALING
REDIS_HOST / REDIS_PASSWORD (VPS only)
```

---

# PART C — VPS MIGRATION CHECKLIST

Execute when a migration trigger (Part D) is met. Estimated window: a few hours,
mostly provisioning and verification — not redevelopment.

## C.1 Provision VPS

- [ ] Provision Hostinger VPS (sized to current load + headroom)
- [ ] Install PHP 8.3, MySQL 8+, Composer, Nginx (or Apache), Supervisor, Redis
- [ ] Harden: firewall, fail2ban, SSH keys only, automatic security updates
- [ ] Install TLS (Let's Encrypt) with auto-renew
- [ ] Set document root to `/public`; place `.env` outside web root (SEC-02)

## C.2 Migrate Data

- [ ] Put shared site in maintenance mode (`php artisan down`)
- [ ] Final backup of shared MySQL DB + `storage/`
- [ ] Restore DB to VPS MySQL (schema is identical — straight import)
- [ ] Copy `storage/app` files to VPS (or begin cloud-storage migration, D-024)
- [ ] Verify row counts and a checksum sample match

## C.3 Flip Configuration (the actual "migration")

- [ ] `QUEUE_CONNECTION=redis`
- [ ] `CACHE_STORE=redis`
- [ ] `SESSION_DRIVER=redis`
- [ ] `ICS_WAREHOUSE_ETL_ENABLED=true`
- [ ] `ICS_HEAVY_JOBS=true`
- [ ] `ICS_AI_HIGH_VOLUME=true`
- [ ] `ICS_COMMUNITY_SCALING=true`
- [ ] Set `REDIS_HOST`, `REDIS_PASSWORD`
- [ ] Keep `MAIL_FALLBACK_MAILER` configured
- [ ] `php artisan config:cache && route:cache && view:cache`

## C.4 Start VPS-Only Services

- [ ] Configure Supervisor to run `queue:work` (or Horizon) as a persistent worker
- [ ] Install Horizon dashboard (optional, recommended)
- [ ] Replace the every-5-min queue cron with real workers
- [ ] Keep the 1-minute `schedule:run` cron for scheduled commands
- [ ] Confirm DW ETL commands now run on schedule (flag enabled)

## C.5 Verify

- [ ] Login + RBAC works
- [ ] Queue processes a test job in seconds (not minutes)
- [ ] Redis cache + session active (`redis-cli monitor` shows traffic)
- [ ] A notification delivers via worker, not cron
- [ ] DW ETL command runs and writes `dw_etl_runs` success
- [ ] AI request succeeds with raised limits
- [ ] Cloudflare still fronting; TLS valid
- [ ] Auth-critical mail + fallback path works
- [ ] `php artisan up` — bring site live; monitor errors for 24h

## C.6 Decommission

- [ ] Point DNS to VPS; lower TTL beforehand for fast cutover
- [ ] Keep shared hosting warm for 7 days as instant rollback (Part E)
- [ ] After 7 stable days, decommission shared hosting

**Code changes required in Part C: ZERO. Migrations re-run identically: yes.
Schema redesign: none. This satisfies D-037.**

---

# PART D — MIGRATION TRIGGERS

Migrate when ANY of these is sustained (not a one-off spike):

| Signal | Threshold (review and tune) |
|---|---|
| Concurrent users | Approaching shared-hosting worker/connection limits |
| Queue latency | Notifications/invoices regularly delayed > 5–10 min |
| Revenue | Paid courses / subscriptions live and material (justifies cost + SLO) |
| SLO demand | A client contract requires the 99.9% SLO (D-009) — undeliverable on shared |
| AI usage | Demand exceeds the low-volume shared caps |
| Analytics | BI/Data-Warehouse reporting genuinely needed (turn on ETL) |
| Resource limits | Repeated host CPU/memory/execution-time throttling |
| Community growth | Forums / mentorship matching / events demand activates scaling features |

Business rule (D-037): *initial deployment may use shared hosting until traffic
and revenue justify VPS migration.* This table is the "justify" test.

---

# PART E — ROLLBACK PLAN

If VPS migration verification fails:

1. `php artisan down` on VPS.
2. Re-point DNS to the still-warm shared host (low TTL makes this fast).
3. Restore any data written during the VPS window back to shared MySQL
   (the schema is identical — straight import).
4. `php artisan up` on shared host with the Phase 1 `.env` profile.
5. Diagnose VPS issue; retry migration later.

Because schema and code are identical across environments, rollback is symmetric
with migration — a DNS + `.env` operation, not a redevelopment.

---

## RISKS ADDRESSED BY THIS STRATEGY

| Review Finding | How This Strategy Addresses It |
|---|---|
| HOST-01 (no persistent workers) | Cron-queue on shared; real workers on VPS via config flip |
| SPOF-01 (MySQL is sole SPOF) | Sessions/cache off MySQL on shared; Redis on VPS |
| PERF-05 (ETL unrunnable) | ETL flag OFF on shared, ON on VPS — no failed multi-hour jobs |
| HOST-03 (99.9% SLO) | SLO is a documented VPS-tier capability; a migration trigger |
| CPLX-01/03/04 (defer heavy) | Deferred as RUNTIME (flags), not removed from architecture |

---

## APPROVAL SECTION

| Role | Name | Signature | Date |
|---|---|---|---|
| Platform Owner | | | |
| Lead Architect | | | |
| Technical Lead | | | |
| Operations / DevOps | | | |

**Status:** Awaiting Review and Approval
**Gate:** Part A.4 host capability spike must pass before Phase 1 build begins.
