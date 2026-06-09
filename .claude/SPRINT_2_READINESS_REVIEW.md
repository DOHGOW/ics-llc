# SPRINT 2 READINESS REVIEW
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-05-31
Status: Recommendation — Awaiting Owner Decision
Author: Chief Enterprise Architect

---

## PURPOSE

Determine whether Sprint 2 (business modules — CMS, CRM, Knowledge Center, Research
Center, Marketplace, Community, Training Institute, Partner Portal) may begin.

---

## READINESS ASSESSMENT

### What is ready
- Core Platform is implemented and internally consistent (Tasks 1–10; D-001…D-048).
- Authentication, RBAC, audit, user lifecycle, localization, and security middleware
  are built with layered guards and a conformance test baseline.
- Integration wiring is consolidated in `bootstrap/app.php` / `bootstrap/providers.php`.
- Module seams are defined: every business table carries `tenant_id`; events are the
  cross-module contract (D-027); the unified content engine (D-038) and tiered
  access services are specified.

### What must be satisfied first (conditions)
| # | Condition | Source |
|---|---|---|
| C-1 | Project bootstrapped (create-project + install + npm + migrate + seed) | Go-Live A |
| C-2 | **CI green** — all 6 conformance suites + pint + phpstan + driver-gate + secrets | Go-Live, Governance §7 |
| C-3 | Hostinger capability spike executed; limitations confirmed | R-013 |
| C-4 | Security + auth + authz + audit verifications pass | Go-Live B/D/E/F |
| C-5 | Backups configured + restore tested | Go-Live G |

### Critical dependency for Sprint 2 design
- **Org-ownership policies are the sole Phase 1 isolation control** (TenantScope
  deferred). The FIRST Sprint 2 modules that hold organisation-owned data (CRM,
  Client Portal, Partner) MUST ship rigorous, tested ownership policies on the
  BasePolicy framework. This is the highest-priority carry-over (RA-2/T5-2).

---

## RISK SUMMARY

| Risk | Status | Effect on Sprint 2 |
|---|---|---|
| R-012 overlay not yet bootstrapped/green | OPEN | Blocks — Sprint 2 builds on an unproven base if skipped |
| R-013 host spike not executed | OPEN | May invalidate environment assumptions |
| RA-2 org-isolation via policy only | MANAGED | Drives Sprint 2 module test requirements |
| Phase-2 deferrals (DW ETL, i18n content) | INTENTIONAL | Not blocking |

---

## RECOMMENDATION

> **CONDITIONAL GO.**

The architecture and core platform are ready in design and code. Sprint 2 business-
module development may begin **once the integration/environment gate is satisfied** —
specifically: the project is bootstrapped, **CI is green on the conformance baseline**,
the Hostinger capability spike has run, and the Go-Live checklist sections B/D/E/F/G
pass. Beginning business-module code before CI is green would build on an unverified
foundation and is not advised.

Recommended Sprint 2 entry sequence (per MODULE_DEPENDENCY_DIAGRAM build order):
1. **Corporate Website / CMS** and **CRM** (Level 1 — depend only on Core Platform).
2. Establish the **unified content engine** (D-038) during the CMS work.
3. Enforce **org-ownership policies + tests** the moment CRM/Client data appears.

If the Owner accepts a **parallel-track** approach, the lowest-risk option is to let
DevOps execute the Go-Live gate (bootstrap + CI + spike) while the architect prepares
the Sprint 2 CMS/CRM design — but **no business-module code merges until CI is green.**

---

## DECISION REQUESTED

- [ ] CONDITIONAL GO — satisfy the gate, then begin Sprint 2 (recommended)
- [ ] FULL GO — begin Sprint 2 now (accept R-012/R-013 risk — not recommended)
- [ ] HOLD — do not begin Sprint 2 yet

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Owner decision. Per standing instruction, NO CMS/CRM/Knowledge/
Research/Marketplace/Community/Training/Partner development begins without approval.
