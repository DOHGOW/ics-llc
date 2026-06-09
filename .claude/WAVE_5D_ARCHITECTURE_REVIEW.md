# WAVE 5D ARCHITECTURE REVIEW — INVESTMENT NETWORK
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-03
Status: Architecture review — NO code/migrations/models/services/tables. Design only.
Author: Lead Architect
Validates against: D-024, D-025, D-029, D-031, D-035, D-037, D-046, D-053, D-057, D-061,
D-063, D-064, D-065, D-066, D-068, D-069; C-1, W4b-1
Inputs: ECOSYSTEM_ROADMAP_REVIEW, ACCESS_CONTROL_CONSOLIDATION_REVIEW, WAVE_5_ARCHITECTURE_PLAN,
WAVE_5A/5B/5C reviews

> **This is the platform's highest-governance, highest-risk module (securities/financial). It is
> where the C-1 / D-069 boundary is finally consumed: Investment Network EXECUTES what every prior
> wave deferred. A mandatory legal/compliance/securities governance review (proposed D-075) is a
> HARD PREREQUISITE before any implementation.**

---

## ★ PRE-5D VALIDATION (REQUIRED): can Program Events be consumed without becoming a workflow engine?

**Question (standing directive):** can Investment Network, Community, and Marketplace consume the
Program Events layer WITHOUT turning it into a workflow / business-process / orchestration /
cross-module state machine?

**Finding: YES — as a READ-ONLY signal/reference source ONLY, under a strict rule.**

| Consumer | Safe consumption | Prohibited |
|---|---|---|
| Investment Network | READ a startup's demo_day/showcase/readiness outcomes as a deal-flow SIGNAL; link a deal to the originating event by id | pushing DD/deal/grant workflow STATES into program_events; adding investment columns/types |
| Community | READ "presented at Demo Day X" as a public profile reference | writing community state into events |
| Marketplace | READ-only link a listing to an event (provenance) | event-driven listing workflow inside program_events |

**Governance rule (propose as standing):** Program Events remains LIGHTWEIGHT and **append/finalize
only** (event + scores + `finalized_at` lock). Other modules may **reference/read** it; NONE may
add workflow states, statuses, orchestration, or process behavior. Each consuming module keeps its
OWN workflow records (e.g., Investment Network DD/deals are separate). **Conclusion: consumption is
SAFE under the read-only rule; Investment Network builds its own DD/deal records and does NOT
extend Program Events.**

---

## 1. INVESTMENT NETWORK SCOPE DEFINITION (D-069 — EXECUTE)

Investment Network is where investment activity EXECUTES: investor identity (regulated),
investor↔startup relationships (interest/connection), **NDA-gated data rooms** (the SOLE system of
record for cap-table/valuation/fundraising/financials/docs, C-1), due diligence, deals/rounds, and
(future) investment matching + fees. It CONSUMES (read-only) Startup Hub (startups, lifecycle
investment_ready), Community ('investor' public identity), and Accelerator signals. It REUSES the
participation/grant access family. It is the ONLY module that may hold financial/cap-table/DD data.

## 2. ACCESS CONTROL MODEL — Mandatory Test A

**A. Can Investment Network reuse an existing access-control family? YES — the
membership/PARTICIPATION (grant) family, plus an NDA + financial-confidentiality OVERLAY.**

Core access = "is this investor a GRANTED + NDA-accepted participant of this startup's data room?"
— structurally identical to Training enrollment / program participation (a grant relationship).
A thin **DataRoomAccessService** (ParticipationGate-conforming) answers it. Comparison:

| Mechanism | Fit | Why not |
|---|---|---|
| AccountScope | ✗ | not org-row isolation |
| ContentAccessService | ✗ | not tiered content (financial confidentiality ≠ content tiers) |
| HasAssignmentVisibility | ✗ | not internal CRM assignment |
| Community visibility | ✗ | identity visibility, not data-room grants |
| Marketplace status | ✗ | no review/publish workflow |
| **Participation/grant family** | ✓ | data-room grant = a granted participant relationship |

**No new access-control FAMILY.** What IS new is a **compliance OVERLAY** (not an access family):
- **NDA precondition** — no data-room document access until `nda_accepted_at` is set.
- **Financial-confidentiality** — per-document access logged (regulatory), encryption-at-rest,
  redaction tiers (teaser vs full room), revocable grants.
This overlay is controls layered on the grant shape — consistent with the roadmap conclusion
("grant-based participation + compliance overlay, not a new family").

## 3. INVESTOR IDENTITY MODEL (Test A / H-2 — no duplicate registry)

Two-layer, no duplication:
- **Public identity:** a Community **'investor'** profile type (NEW D-035 CTI extension
  `community_investor_profiles`) — public discovery fields only (firm, focus areas, stages), per
  W4b-1. The canonical identity remains `core_users`.
- **Regulated/private:** an Investment Network investor record (`investment_investor_profiles`) for
  KYC status, accreditation status, mandate/thesis, jurisdiction — GATED to the investor + ICS
  compliance staff; NEVER public. This is sensitive PII/regulatory data.

**No parallel investor registry (H-2):** identity = user + Community public projection; the
Investment Network record EXTENDS it with regulated data. (Proposed D-073.)

## 4. STARTUP ↔ INVESTOR RELATIONSHIP MODEL

| Relationship | Design | Privacy |
|---|---|---|
| Expression of interest | investor expresses interest in a startup (from showcase/discovery; one-way handoff from Accelerator) | private to parties + ICS |
| Connection / introduction | mutual-consent introduction | private |
| **Data-room grant** | startup/ICS grants an investor access to the startup's data room; NDA precondition; revocable | the access relationship; audited |
| Deal / round | terms + status between investor(s) and startup (NOT payment execution — Billing, D-031) | private; HIGH audit |

Startup consent is required (the startup/ICS creates the grant) — investors cannot self-grant.

## 5. NDA / DATA ROOM ARCHITECTURE

- **One data room per startup** (`investment_data_rooms`) — the SOLE store for cap-table/valuation/
  fundraising/financials/documents (C-1, system of record).
- **Documents** (`investment_data_room_documents`) — encrypted-at-rest (D-024), streamed via
  policy (W2-5), NEVER public URLs; per-document access logged.
- **Grants** (`investment_data_room_grants`: room_id, investor_id, status, nda_accepted_at,
  granted_by, revoked_at) — NDA acceptance is a precondition to ANY document access; grants are
  revocable; redaction tiers (teaser vs full).
- DataRoomAccessService gates every read: granted + active + nda_accepted.

## 6. FINANCIAL DATA PROTECTION — Mandatory Test B

**B. All financial/ownership/valuation/fundraising/cap-table/DD data is COMPLETELY ISOLATED from
Community, Marketplace, Knowledge, Research, Public CMS, and Accelerator Showcase.**

| Module | What it may hold | Enforcement |
|---|---|---|
| Community ('investor'/startup) | public discovery fields only (W4b-1) | publicFields() whitelist; no financial join |
| Marketplace | public listings; no financials | listing fields only |
| Knowledge/Research/CMS | tiered/public content; no financials | ContentAccessService; no investment join |
| Accelerator Showcase | curated public startup fields (H-1) | StartupPublicResource; no cap-table |
| Startup Hub | minimal gated founder ownership_percent (C-1, governance subset) | $hidden + gated; data room authoritative |
| **Investment Network data room** | **the SOLE financial/cap-table/DD store** | grant + NDA + encryption + per-doc audit |

**Test B: SATISFIED BY DESIGN** — financial data exists ONLY in the gated data room; every other
module holds public projections, enforced by the established projection discipline (W4b-1/C-1/H-1)
and by the data room being the single source. (Proposed D-072.)

## 7. CAP-TABLE GOVERNANCE — Test C (D-064 intact)

- The **data room is the authoritative cap-table system of record** (C-1): shareholders, share
  classes, ownership %, valuation, rounds (`investment_cap_table_*`). Stored gated; changes audited
  HIGH; round history immutable.
- **Reconciliation with D-064 (Test C):** Startup Hub's minimal `startup_team_members.ownership_percent`
  (D-064 founder governance — totals ≤100%, ≥1 founder, transfers immutable) remains intact for
  FOUNDER CONTROL governance; the data-room cap table is the FULL authoritative record. Rule: the
  data room is authoritative; the Startup Hub subset is a governance view reconciled to it (no
  conflict; D-064 protections unchanged). **(Proposed D-074 — defines authority + reconciliation.)**

## 8. DUE DILIGENCE ARCHITECTURE

- DD = checklists/requests/document collection within a data-room grant
  (`investment_due_diligence_items`: grant_id, item, status, response/doc) — gated to the grant
  parties + ICS; documents streamed (W2-5).
- **DD is Investment-Network-specific** — it has its OWN lightweight status; it is NOT built on
  Program Events (the directive's red line). Program Events stays lightweight.

## 9. AUDIT ARCHITECTURE (D-046)

- **Propose `AuditCategory::INVESTMENT_MANAGEMENT`.** HIGH-sensitivity (financial/regulatory):
  data-room grant/revoke, NDA acceptance, **every data-room document access**, cap-table change,
  deal stage change, DD document access, fee events. Per-document access logging is a regulatory
  control, not optional. (Proposed D-071.)

## 10. ANALYTICS ARCHITECTURE (D-025 / W4-9)

- Own aggregator. Pipeline (interest→grant→DD→deal), conversion, sector trends — **AGGREGATE ONLY:
  NO investor PII, NO cap-table/valuation/financial data** in dashboards (privacy/regulatory).
- Scheduled; dashboards read persisted non-identifying aggregates.

## 11. AI READINESS (D-029 — seams, deferred)

- Investor↔startup matching, deal-flow scoring, DD anomaly detection — seams only.
- **AI must NOT access raw financial PII** without explicit controls/consent; matching uses
  non-sensitive features (sector, stage, mandate). Deferred to the AI sprint.

## 12. FUTURE BILLING INTEGRATION (D-031)

- Deal/success fees, data-room subscriptions, introduction fees — Investment Network RECORDS the
  fee event; **Billing executes** payment (deferred to the Billing wave). Mirrors the Training/
  program invoice-seam pattern.

## 13. FUTURE FRANCHISE / TenantScope COMPATIBILITY — Test E

- `tenant_id` on every investment table; data rooms/grants/deals tenant-scoped.
- **Franchise:** each tenant runs its own Investment Network; **cross-tenant data-room leakage =
  catastrophic** → TenantScope must wrap the grant access; exhaustive isolation tests.
- TenantScope nests ABOVE grant participation (tenant > grant > user); additive, no schema change
  to enable (D-037). **Test E: COMPATIBLE.**

## 14. REGULATORY & COMPLIANCE RISKS (the defining risk)

- **Securities regulation:** facilitating investment may trigger securities-dealer/broker
  licensing, accredited/qualified-investor rules, prospectus/solicitation rules — **per-jurisdiction
  across Africa** (A-2 multi-country, B-1 compliance). 
- **KYC / AML:** investor onboarding likely requires KYC/AML checks (regulated).
- **Data residency / privacy:** investor KYC + startup financials are sensitive personal/commercial
  data (NDPR/GDPR-class, B-2 residency).
- **These make a dedicated legal/compliance/securities governance review a HARD PREREQUISITE.**

---

## MANDATORY ARCHITECTURE TESTS — RESULTS

| Test | Result |
|---|---|
| **A** — reuse an existing access family? | ✅ YES — participation/grant family (DataRoomAccessService) + NDA/financial OVERLAY; **no new family** |
| **B** — financial/cap-table/DD isolated from Community/Marketplace/Knowledge/Research/CMS/Showcase? | ✅ YES — data room is the SOLE store; all else is public projections (W4b-1/C-1/H-1) |
| **C** — D-063 + D-064 protections intact? | ✅ YES — lifecycle authority unchanged (reads investment_ready); D-064 founder governance unchanged; data room authoritative for full cap table (D-074 reconciles) |
| **D** — D-069 Prepare-vs-Execute enforceable? | ✅ YES — Investment Network is the EXECUTE side; investor registry/fundraising/cap-table/DD/deals/matching live ONLY here |
| **E** — TenantScope compatible? | ✅ YES — tenant_id present; TenantScope wraps grants; additive |

---

## RISK ANALYSIS (classified)

| Class | Risk | Severity |
|---|---|---|
| Securities/compliance | Unlicensed investment facilitation; accredited-investor rules; per-jurisdiction | **CRITICAL** |
| Regulatory | KYC/AML obligations on investor onboarding | **CRITICAL** |
| Data leakage | Cap-table/financials/DD leaking to any other module | **CRITICAL** |
| Privacy | Investor KYC + startup financial PII (residency/consent) | HIGH |
| Multi-tenant | Cross-tenant data-room leakage (franchise) | HIGH |
| Cross-module contamination | Investment data joined into Community/Marketplace/etc | HIGH |
| Access integrity | Grant without NDA; revoked grant still reading | HIGH |
| Cap-table authority conflict | Startup Hub subset vs data-room full record diverge | MEDIUM |
| Program Events scope creep | Investment workflow pushed into Program Events | MEDIUM |

---

## FINDINGS

| ID | Severity | Finding | Disposition |
|---|---|---|---|
| **5D-C1** | **CRITICAL** | Securities/KYC/AML/accredited-investor compliance (multi-jurisdiction) | **Mandatory legal/compliance governance review BEFORE implementation (D-075)** |
| **5D-C2** | **CRITICAL** | Data room must be the SOLE financial store; encrypted; grant+NDA gated; per-doc audit | D-072 |
| **5D-H1** | HIGH | NDA precondition before any data-room access; legally-binding acceptance record | enforce |
| **5D-H2** | HIGH | Investor identity must reference existing identities (Community 'investor' + user); no duplicate registry | D-073 |
| **5D-H3** | HIGH | Cap-table authority — data room authoritative; D-064 Startup Hub subset reconciled | D-074 |
| **5D-H4** | HIGH | Program Events consumed READ-ONLY; no investment workflow states pushed in | standing rule |
| **5D-M1** | MEDIUM | DD is Investment-Network-specific; NOT built on Program Events | own records |
| **5D-M2** | MEDIUM | Multi-tenant data-room isolation (TenantScope) — exhaustive tests | release gate |
| **5D-M3** | MEDIUM | Billing fee events recorded here; executed by Billing (D-031) | seam |
| **5D-L1** | LOW | AI matching/DD-anomaly deferred; no raw-PII AI access | seams |
| **5D-L2** | LOW | Analytics aggregate-only; no PII/financials | enforce |

### Missing governance / schema / decisions (surfaced)
- **Missing governance:** a dedicated **INVESTMENT_GOVERNANCE_REVIEW** (securities/KYC/AML/data-
  residency/accredited-investor) with legal sign-off — does not exist; REQUIRED before 5D code (D-075).
- **Missing schema:** the entire investment_* schema is NEW (no investment module in the blueprint):
  investment_investor_profiles, investment_data_rooms, investment_data_room_documents,
  investment_data_room_grants, investment_cap_table_*, investment_due_diligence_items,
  investment_interests, investment_deals; + community_investor_profiles (D-035 extension).
- **Decision points (propose; NOT decide now):** D-070 (grant-family access + NDA/financial overlay),
  D-071 (INVESTMENT_MANAGEMENT audit + per-doc access logging), D-072 (data room SOLE store, encrypted,
  isolated), D-073 (investor identity two-layer, no duplicate registry), D-074 (cap-table authority +
  D-064 reconciliation), **D-075 (MANDATORY legal/compliance governance review GATE)**.

---

## FINAL VERDICT

**SOUND WITH CONDITIONS.** The architecture is sound: it reuses the participation/grant access
family (no new mechanism), isolates ALL financial/cap-table/DD data inside a single NDA-gated,
encrypted, per-document-audited data room (Test B satisfied), keeps D-063/D-064 intact (Test C),
enforces the D-069 Prepare-vs-Execute boundary (Test D), and is TenantScope-compatible (Test E).
Program Events can be consumed read-only without becoming a workflow engine.

**However, this module's REGULATORY dimension makes a dedicated legal/compliance/securities
governance review (D-075) a HARD PREREQUISITE — implementation must NOT begin until that review and
sign-off are complete.** The full investment_* schema and decisions D-070..D-075 must be ratified.

Proposed decisions to ratify on approval (NOT now):
- **D-070** Investment access = participation/grant family (DataRoomAccessService) + NDA/financial overlay; no new family.
- **D-071** `AuditCategory::INVESTMENT_MANAGEMENT`; per-document access logging; financial/grant/deal/cap-table events HIGH.
- **D-072** Data room = SOLE system of record for cap-table/valuation/fundraising/financials/DD; encrypted; isolated from all other modules.
- **D-073** Investor identity = Community 'investor' public profile + Investment Network regulated extension; no duplicate registry.
- **D-074** Cap-table authority = data room; D-064 Startup Hub ownership subset reconciled to it.
- **D-075** MANDATORY INVESTMENT_GOVERNANCE_REVIEW (securities/KYC/AML/data-residency/accredited-investor) + legal sign-off BEFORE any 5D implementation.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Security/Compliance | | | | |
| Legal / Regulatory | | | | |
| Technical Lead | | | | |

**Status:** Awaiting Approval. **Do NOT implement Investment Network until approved AND the
mandatory legal/compliance governance review (D-075) and decisions D-070..D-074 are complete.**
