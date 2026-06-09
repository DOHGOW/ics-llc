# HOSTINGER LIMITATIONS REGISTER
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-30
Status: Pre-populated (Anticipated) — to be confirmed by HOSTINGER_CAPABILITY_SPIKE.md
Owner: Technical Lead
Decision References: D-003, D-037 (VPS-ready), D-039 (hardening)

---

## EXECUTIVE SUMMARY

This register records every confirmed limitation of the Phase 1 host (Hostinger
Premium Shared Hosting), its impact, the Phase 1 workaround, and how it resolves
on VPS migration (D-037).

It is pre-populated with **anticipated** limitations that are highly characteristic
of shared hosting and very likely to be confirmed by the capability spike. Each
entry has a STATUS:

- ANTICIPATED — expected; confirm via the spike, then update STATUS
- CONFIRMED — verified by the spike on this specific account
- NOT-AN-ISSUE — spike showed the limitation does not apply here

Every limitation maps to a capability spike check (LIM ↔ CHECK) and, where relevant,
to an architecture-review finding ID.

**Governing principle (D-037):** none of these limitations require a database,
application, or code redesign. Each is handled by configuration, a runtime
workaround, or deferral — and each is fully resolved by the config-only VPS
migration.

---

## SEVERITY LEGEND

| Level | Meaning |
|---|---|
| BLOCKER | Must be resolved before launch on shared hosting |
| HIGH | Materially constrains Phase 1; workaround mandatory |
| MEDIUM | Manageable with a defined workaround |
| LOW | Minor; monitor |

---

## LIM-01 — No Persistent Background Processes

- **STATUS:** ANTICIPATED (Spike CHECK 11) · Review: HOST-01 · Severity: HIGH
- **Limitation:** Shared hosting forbids long-running daemons; no Supervisor, so
  `queue:work` cannot run as a persistent worker.
- **Impact:** Background jobs (email, notifications, invoices, AI results) cannot
  process continuously; they wait for a cron tick.
- **Workaround (Phase 1):** `QUEUE_CONNECTION=database`; a cron runs
  `php artisan queue:work --stop-when-empty` on the shortest allowed interval.
  Auth-critical mail sent synchronously (D-039 SPOF-04). Heavy listeners are
  `ShouldQueue` so no code changes are needed later.
- **Future VPS Resolution:** Supervisor + Redis + Horizon run persistent workers.
  Flip `QUEUE_CONNECTION=redis` — config only (D-037).

---

## LIM-02 — No Redis

- **STATUS:** ANTICIPATED (Spike CHECK 11, S3) · Review: SCAL-01/02 · Severity: HIGH
- **Limitation:** No Redis service on shared hosting.
- **Impact:** No high-performance cache/queue/session store; these fall back to
  MySQL or filesystem.
- **Workaround (Phase 1):** `CACHE_STORE=file` (or APCu if present), 
  `SESSION_DRIVER=file`, `QUEUE_CONNECTION=database`. Keeping cache/session OFF
  MySQL also protects the DB connection budget (LIM-08).
- **Future VPS Resolution:** Install Redis; set drivers to `redis` — config only.

---

## LIM-03 — Database Engine  →  RESOLVED (NOT A LIMITATION)

- **STATUS:** CONFIRMED via Gate 0 — production engine is **MySQL 8.x** · Severity: NONE
- **Outcome:** The host provides MySQL 8.x, matching D-002 as specified. The
  anticipated MariaDB-compatibility concern (JSON/FULLTEXT/CTE differences) does
  **not apply**. No limitation remains.
- **Action taken (T-2.4):** Local/staging/CI pinned to MySQL 8 (`docker-compose.yml`
  default `mysql:8.0`; CI engine-parity job enabled). If production runs MySQL 8.4,
  set `DB_IMAGE=mysql:8.4` to match the exact minor version.
- **Residual:** none. Confirm the exact minor version (8.0 vs 8.4) and align the
  image tag — housekeeping, not a risk.

---

## LIM-04 — Cron Granularity May Exceed 1 Minute

- **STATUS:** ANTICIPATED (Spike CHECK 04) · Review: HOST-02 · Severity: MEDIUM
- **Limitation:** Some shared plans enforce a minimum cron interval (5–15 min)
  instead of 1 minute.
- **Impact:** Laravel scheduler loses sub-interval granularity; queue/notification
  latency rises to the cron interval.
- **Workaround (Phase 1):** Accept higher latency for non-urgent work; auth-critical
  mail synchronous; if `exec` is allowed, a single cron can loop internally to
  simulate 1-minute ticks.
- **Future VPS Resolution:** True 1-minute cron + persistent workers eliminate the
  constraint entirely.

---

## LIM-05 — MySQL TRIGGER Privilege May Be Denied

- **STATUS:** ANTICIPATED (Spike CHECK 08) · Review: SEC-03 · Severity: MEDIUM
- **Limitation:** Shared hosting may not grant `CREATE TRIGGER`, blocking
  DB-enforced append-only audit logs.
- **Impact:** `core_audit_logs` immutability cannot be enforced at the database layer.
- **Workaround (Phase 1):** Enforce immutability at the application layer
  (write-only repository) AND export audit logs off-box periodically (write-once
  external store). Already the D-039 plan — no redesign.
- **Future VPS Resolution:** Add the DB trigger as a defense-in-depth hardening
  layer once `SUPER`/`TRIGGER` is available.

---

## LIM-06 — 99.9% SLO Not Achievable

- **STATUS:** ANTICIPATED (environmental) · Review: HOST-03, R-006 · Severity: HIGH
- **Limitation:** Shared hosting offers no uptime guarantee at 99.9%; no control
  over the node or neighbors.
- **Impact:** D-009's 99.9% SLO cannot be contractually promised in Phase 1.
- **Workaround (Phase 1):** Declare Phase 1 a documented pre-SLO / best-effort
  period; do not promise 99.9% to government clients while on shared hosting.
- **Future VPS Resolution:** VPS (and cloud) make the SLO defensible; SLO demand is
  an explicit VPS migration trigger (VPS_MIGRATION_CHECKLIST Part D).

---

## LIM-07 — Weak Tenant/Process Isolation for Sensitive Data

- **STATUS:** ANTICIPATED (environmental) · Review: SEC-01, R-010 · Severity: HIGH
- **Limitation:** Shared hosting co-locates accounts on one OS; limited isolation
  of processes, memory, and (mis)configuration risk.
- **Impact:** Confidential CRM/PII/contract/payment-reference data carries elevated
  exposure risk versus dedicated infrastructure.
- **Workaround (Phase 1):** Full D-039 hardening (`.env` off web root, app-layer
  audit immutability + off-box export, Cloudflare WAF, least privilege, encrypted
  sensitive fields); no card data stored (Paystack-hosted). Consider deferring the
  most sensitive CRM/contract documents until VPS if the owner deems residual risk
  too high.
- **Future VPS Resolution:** VPS provides a dedicated environment and full
  control; the strongest argument among the migration triggers.

---

## LIM-08 — Limited Concurrent Database Connections

- **STATUS:** ANTICIPATED (Spike CHECK 14) · Review: SCAL-01, SPOF-01 · Severity: HIGH
- **Limitation:** Per-user MySQL connection caps (often 25–75, sometimes lower).
- **Impact:** Web + cron queue + analytics can exhaust the pool → "Too many
  connections"; because sessions could be DB-backed, this can also break login.
- **Workaround (Phase 1):** Sessions + cache OFF MySQL (file/APCu); short-lived
  queries; eager loading to avoid N+1; chunked analytics cron.
- **Future VPS Resolution:** Tune `max_connections`; Redis offloads session/cache/
  queue; read replica offloads analytics.

---

## LIM-09 — Data Warehouse ETL Cannot Run

- **STATUS:** ANTICIPATED (Spike CHECK 06) · Review: PERF-05, CPLX-01 · Severity: MEDIUM
- **Limitation:** Multi-hour ETL windows are killed by execution-time limits and the
  absence of persistent processes.
- **Impact:** `dw_*` star-schema tables cannot be populated automatically on shared
  hosting.
- **Workaround (Phase 1):** `ICS_WAREHOUSE_ETL_ENABLED=false` — DW schema exists
  (intact architecture, D-037) but ETL automation is gated off. Phase 1 reporting
  uses Tier-1 `analytics_*` tables via small chunked cron.
- **Future VPS Resolution:** Flip the flag true; workers run nightly ETL — config
  only. Schema unchanged.

---

## LIM-10 — Synchronous AI Calls Risk Blocking Workers

- **STATUS:** ANTICIPATED (Spike CHECK 11) · Review: PERF-02 · Severity: MEDIUM
- **Limitation:** Few PHP workers; a slow Gemini HTTP call in-request can starve the
  site, and the queue that should absorb it is itself cron-throttled.
- **Impact:** Risk of request-thread blocking and degraded responsiveness under AI load.
- **Workaround (Phase 1):** All AI work queued (never in the web request);
  `ICS_AI_HIGH_VOLUME=false` with low caps; public/guest AI assistant hard-capped
  per IP/session with a daily kill-switch (COST-01); graceful fallback on timeout.
- **Future VPS Resolution:** Real workers process AI jobs async at volume; raise
  caps via `ICS_AI_HIGH_VOLUME=true`.

---

## LIM-11 — Inode (File Count) Limits

- **STATUS:** ANTICIPATED (Spike CHECK 13) · Review: HOST-06, SCAL-03 · Severity: MEDIUM
- **Limitation:** Shared plans cap total file count (inodes), independent of disk GB.
- **Impact:** Many small files (file cache, sessions, logs, uploads) can exhaust
  inodes and halt all writes even with free disk space.
- **Workaround (Phase 1):** Prefer DB/APCu over file cache if inodes are tight; wire
  retention/prune jobs (D-006 `core_retention_policies`) for logs and append-only
  tables; migrate uploads to object storage early (D-024) if growth is high.
- **Future VPS Resolution:** VPS disk has no inode cap of this kind; object storage
  removes upload pressure entirely.

---

## LIM-12 — PHP Memory / Execution Caps Constrain PDF & Aggregation

- **STATUS:** ANTICIPATED (Spike CHECK 05/06) · Review: PERF-04 · Severity: MEDIUM
- **Limitation:** Memory and execution limits may be below what DOMPDF (invoices,
  certificates) and large aggregations need.
- **Impact:** OOM or timeout during PDF generation or analytics rollups.
- **Workaround (Phase 1):** Raise limits via hPanel where allowed; generate PDFs in
  queued jobs; keep aggregation chunked and idempotent.
- **Future VPS Resolution:** Set generous limits in php.ini; ample RAM removes the
  constraint.

---

## LIM-13 — No Native CDN / WAF (Mitigated by Cloudflare)

- **STATUS:** ANTICIPATED (Spike CHECK 16) · Review: SEC-09, PERF-01, COST-01 · Severity: MEDIUM
- **Limitation:** Shared hosting alone provides no application WAF, bot control, or
  edge cache for a public, government-facing, SEO-heavy site.
- **Impact:** Exposure to bots (guest-AI cost abuse), FULLTEXT load under traffic,
  and no DDoS buffer.
- **Workaround (Phase 1):** Put Cloudflare (free tier) in front from day one
  (D-039 SEC-09) — CDN + WAF + bot management + edge cache.
- **Future VPS Resolution:** Cloudflare continues to front; add server-level
  hardening and optional paid WAF tiers.

---

## LIM-14 — Search Limited to MySQL/MariaDB FULLTEXT

- **STATUS:** ANTICIPATED (architectural) · Review: PERF-01 · Severity: LOW
- **Limitation:** No dedicated search engine on shared hosting; FULLTEXT degrades and
  locks under load on throttled CPU.
- **Impact:** Search across Knowledge/Research/Marketplace/Community slows at scale.
- **Workaround (Phase 1):** Hard pagination + cached results + Cloudflare edge cache
  for public search; FULLTEXT is adequate at Phase 1 volumes.
- **Future VPS Resolution:** Meilisearch on VPS (already planned) — additive, no
  schema change.

---

## LIM-15 — WhatsApp / Email Channel Cost & Reliability on Shared

- **STATUS:** ANTICIPATED (Spike CHECK 17) · Review: COST-03, SPOF-04 · Severity: LOW
- **Limitation:** Outbound API reliability depends on host networking; WhatsApp is
  per-conversation priced; Brevo is the sole email path.
- **Impact:** Possible delivery friction; cost growth on WhatsApp; email SPOF.
- **Workaround (Phase 1):** WhatsApp opt-in and reserved for high-value events;
  secondary SMTP fallback for auth-critical mail (D-039 SPOF-04); verify outbound
  per CHECK 17.
- **Future VPS Resolution:** Same providers, better networking; optionally a
  dedicated mail relay.

---

## ANTICIPATED LIMITATIONS — SUMMARY TABLE

| LIM | Limitation | Sev | Spike Check | Resolves on VPS by |
|---|---|---|---|---|
| 01 | No persistent workers | HIGH | 11 | Redis+Horizon (config) |
| 02 | No Redis | HIGH | 11/S3 | Install Redis (config) |
| 03 | DB engine | RESOLVED | 07 | Confirmed MySQL 8.x — not a limitation |
| 04 | Cron > 1 min | MED | 04 | 1-min cron + workers |
| 05 | No TRIGGER priv | MED | 08 | Add DB trigger |
| 06 | No 99.9% SLO | HIGH | env | VPS/cloud SLO |
| 07 | Weak isolation (PII) | HIGH | env | Dedicated VPS |
| 08 | Few DB connections | HIGH | 14 | Redis + tuning + replica |
| 09 | DW ETL can't run | MED | 06 | Flip ETL flag |
| 10 | Sync AI blocking | MED | 11 | Async workers |
| 11 | Inode limits | MED | 13 | VPS disk + object storage |
| 12 | Memory/exec caps | MED | 05/06 | php.ini + RAM |
| 13 | No CDN/WAF | MED | 16 | Cloudflare (now) + server |
| 14 | FULLTEXT only | LOW | — | Meilisearch |
| 15 | Channel cost/reliability | LOW | 17 | Better networking |

**Pattern:** every limitation resolves on VPS by **configuration or additive
provisioning** — none requires database, application, or code redesign. This
validates the D-037 config-only migration guarantee.

---

## IMPACT ASSESSMENT, REQUIRED MITIGATIONS & PROVISIONAL GO/NO-GO

> ⚠️ PROVISIONAL — BASED ON ANTICIPATED PROFILE, NOT REAL SPIKE OUTPUT.
> The Host Capability Review results submitted contained empty placeholders
> (PASS/PARTIAL/FAIL lists were blank). This assessment is therefore built on the
> ANTICIPATED Hostinger Premium Shared Hosting profile (LIM-01…15 above), NOT on
> measured results from the live account. It MUST be reconciled against the actual
> HOSTINGER_OPERATOR_QUICKSHEET.md output before the review is signed.
> No limitation is marked CONFIRMED until real spike data is recorded.

### 1. Impact Assessment (projected)

Grouping the anticipated findings by how they affect the Phase 1 launch:

| Impact Class | Findings | Net Effect on Phase 1 |
|---|---|---|
| No launch blocker IF criticals pass | CRITICAL spike checks (PHP 8.3, extensions, APIs reachable, FK, JSON, SSL, docroot→/public, .env isolation) | Expected to PASS on Hostinger; these are standard shared-hosting capabilities |
| Operates with planned workaround | LIM-01 (no workers), LIM-02 (no Redis), LIM-04 (cron), LIM-08 (connections), LIM-09 (no ETL), LIM-10 (sync AI) | Runs on shared via D-037 config-driven runtime; degraded latency, not blocked |
| Compatibility watch-item | LIM-03 (MariaDB not MySQL 8), LIM-05 (no TRIGGER) | Schema works on MariaDB ≥10.4; audit immutability moves to app layer (D-039). No redesign |
| Posture / governance | LIM-06 (no 99.9% SLO), LIM-07 (weak isolation for PII) | Phase 1 is pre-SLO; PII risk managed by D-039 hardening; both are VPS triggers |
| Cost / abuse surface | LIM-13 (no native WAF), guest AI (COST-01) | Mitigated by Cloudflare + hard AI caps from day one |

**Overall projected impact:** No anticipated finding requires a database,
application, or code redesign. Every item is absorbed by configuration, a runtime
workaround, or deferral — consistent with the D-037 config-only guarantee. The
platform is projected to be launchable on shared hosting in a reduced-throughput
posture, migrating to VPS on the defined triggers.

### 2. Required Mitigations (must be in place before / during Sprint 1)

| # | Mitigation | Addresses | Owner | Gate |
|---|---|---|---|---|
| M-1 | Sessions + cache OFF MySQL (file/APCu) | LIM-02, LIM-08 | Tech Lead | Sprint 1 task A-3 |
| M-2 | Queue = database driver + cron processor; auth-critical mail synchronous + SMTP fallback | LIM-01, LIM-04, SPOF-04 | Tech Lead | Sprint 1 F-2 |
| M-3 | Pin local + staging DB to the SAME engine/version as production (likely MariaDB) | LIM-03 | Tech Lead | Pre-Sprint 1 |
| M-4 | App-layer append-only audit + off-box export (do not depend on TRIGGER) | LIM-05 | Lead Architect | Sprint 1 B-2b/E-2 |
| M-5 | Feature flags OFF on shared: ETL, heavy jobs, AI high-volume, community scaling | LIM-09, LIM-10 | Tech Lead | Sprint 1 A-2 |
| M-6 | Cloudflare in front (CDN/WAF/bot) + hard per-IP/session AI caps + daily kill-switch | LIM-13, COST-01 | DevOps | Sprint 1 A-6 |
| M-7 | `.env` outside web root; security headers; error display off | SEC-02 | Tech Lead | Sprint 1 A-5 |
| M-8 | Retention/prune jobs for logs + append-only tables; monitor inodes | LIM-11 | Tech Lead | Sprint 1 / ongoing |
| M-9 | Declare Phase 1 pre-SLO in client-facing terms; PII-sensitivity decision for shared | LIM-06, LIM-07 | Platform Owner | Before go-live |
| M-10 | Raise PHP memory/exec/upload via hPanel to targets; queue PDF generation | LIM-12 | DevOps | Sprint 1 / billing sprint |

### 3. Provisional Go / No-Go Recommendation

> RECOMMENDATION: **CONDITIONAL GO** (provisional).

Rationale (projected): On the anticipated profile, all CRITICAL spike checks are
expected to PASS, and every PARTIAL is already mitigated by an approved decision
(D-037 / D-039) and the M-1…M-10 list above. No finding forces a redesign.

This recommendation becomes BINDING only when ALL of the following are true:
- [ ] Real HOSTINGER_OPERATOR_QUICKSHEET.md output is recorded in this register
- [ ] Every CRITICAL check actually PASSED on the live account
- [ ] Every real PARTIAL/FAIL has a CONFIRMED entry + chosen workaround (M-list)
- [ ] No Escalation-Criteria trigger fired (Quicksheet) without architect sign-off

If a CRITICAL check actually FAILS (e.g., docroot cannot target /public, Paystack
inbound blocked, no FK/JSON, no SSL): recommendation flips to **NO-GO** for that
capability → invoke VPS migration (D-037) or resolve before launch.

**Action required from the operator:** run the Quicksheet, paste the real
PASS/PARTIAL/FAIL lists, and resubmit. I will then finalize this register and
convert the recommendation to binding.

---

## CONFIRMED LIMITATIONS (populate after spike execution)

> Move entries here, with STATUS: CONFIRMED and the actual measured value, once
> HOSTINGER_CAPABILITY_SPIKE.md has been run against the live account. Add any
> limitation discovered that is not anticipated above.

| LIM | Limitation | Actual Value | Sev | Workaround Applied | VPS Resolution |
|---|---|---|---|---|---|
| | | | | | |

---

## APPROVAL SECTION

| Role | Name | Signature | Date |
|---|---|---|---|
| Technical Lead | | | |
| Lead Architect | | | |
| Platform Owner | | | |

**Status:** Pre-populated (Anticipated). Finalize after the capability spike;
attach to the Host Capability Review.
