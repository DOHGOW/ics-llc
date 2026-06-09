# INVESTMENT GOVERNANCE REVIEW — INVESTMENT NETWORK (Wave 5D Gate, D-075)
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-04
Status: Governance analysis — IMPLEMENTATION GATE (D-075). No code.
Author: Lead Architect (architecture & governance analysis)
Decision References: D-069, D-070..D-074 (architecturally approved); D-075 (OPEN/BLOCKING);
C-1, B-1 (compliance), B-2 (data residency), A-2 (multi-country audience)

> **⚠ NOT LEGAL ADVICE.** This is an architecture-and-governance analysis to frame the regulatory
> surface and required controls. **D-075 closure requires sign-off by QUALIFIED LOCAL LEGAL COUNSEL
> in each target jurisdiction.** Statements about Nigerian/Ghanaian/Kenyan/South African law are
> high-level orientation that MUST be verified by licensed counsel before any implementation.

---

## PURPOSE

Determine whether the Investment Network can legally and operationally exist within the platform,
which OPERATING MODEL it should adopt, and the controls required before implementation. Output a
GO / CONDITIONAL GO / NO GO for Wave 5D.

---

## 1. REGULATORY CLASSIFICATION ANALYSIS

The regulatory exposure is a SPECTRUM driven by what the platform DOES, not what it calls itself.

| Activity | Classification | Regulated? |
|---|---|---|
| Startup/investor public profiles, discovery | **Informational** | Generally low/unregulated |
| Curated, private, invitation-only introductions | Introduction/facilitation | Low–moderate (depends on compensation + solicitation) |
| NDA-gated data rooms, structured due diligence | Facilitation support | Moderate (confidentiality/privacy; not per se brokerage) |
| Investment MATCHING engine (algorithmic pairing for transactions) | Approaches intermediation | Moderate–high |
| Public solicitation / advertising of securities offerings | **Securities promotion** | **Regulated** |
| Executing transactions, holding/transferring funds, custody | **Brokerage / dealing** | **Heavily regulated (licensing)** |
| Taking transaction-based / success-contingent compensation on a raise | **Brokerage indicator** | **Regulated** |
| Direct fundraising / public offer of shares (crowdfunding) | **Securities offering** | **Heavily regulated (portal licensing)** |

**Key lines (verify with counsel):** the platform stays on the *facilitation* side while it does NOT
(a) solicit the public, (b) execute transactions or custody funds, (c) take success-fee/transaction
compensation contingent on a raise, or (d) run a public crowdfunding offer. Crossing any of these
moves it toward **brokerage/dealing** (Option D) and triggers licensing.

## 2. JURISDICTION ANALYSIS (orientation — counsel to confirm)

| Jurisdiction | Regulator(s) | Indicative regimes to assess |
|---|---|---|
| **Nigeria** | SEC Nigeria; CBN (FX) | ISA 2007; SEC Rules on Crowdfunding (2021, MSME + portal licensing, caps); Nigeria Data Protection Act 2023; CBN exchange-control for cross-border flows |
| **Ghana** | SEC Ghana; Bank of Ghana | Securities Industry Act 2016 (Act 929); Data Protection Act 2012 (Act 843) |
| **Kenya** | Capital Markets Authority (CMA); CBK | CMA Act; CMA crowdfunding regulations (2022, licensed platforms); Data Protection Act 2019 |
| **South Africa** | FSCA; SARB (exchange control) | FAIS Act (financial advisory/intermediary licensing); Companies Act (offers to the public/prospectus); FICA (KYC/AML); POPIA (data protection) |
| **Cross-border** | All of the above + FX authorities | An investor in country X funding a startup in country Y triggers MULTIPLE securities + exchange-control + data-transfer regimes simultaneously |

**Implication:** cross-border investment is the highest-complexity path. **Recommend a phased,
single-jurisdiction launch (Nigeria first) with counsel sign-off, then expand jurisdiction-by-
jurisdiction.** Do NOT launch multi-country/cross-border at once.

## 3. INVESTOR CLASSIFICATION MODEL

| Tier | Definition | Data-room access |
|---|---|---|
| **Public** | unauthenticated / no verification | discovery profiles only; NO deal/financial data |
| **Verified Investor** | KYC-completed identity | limited "teaser" room (non-sensitive) |
| **Accredited Investor** | meets jurisdiction net-worth/income/qualification thresholds (counsel-defined per country) | full data room on grant + NDA |
| **Institutional Investor** | licensed funds/VCs/corporates | full data room + enhanced DD; institutional KYC |

Access tiers map directly to D-070 grant levels + D-072 redaction tiers. Sensitive financials are
restricted to Accredited/Institutional (per jurisdiction rules).

## 4. STARTUP CLASSIFICATION MODEL

| Stage | Disclosure posture | Eligible investor tiers |
|---|---|---|
| **Idea** | minimal; high-risk warning | Accredited/Institutional only (counsel may restrict) |
| **Early Stage** | basic financials/cap-table in room | Accredited/Institutional |
| **Revenue Stage** | fuller financials + DD | Verified (teaser) → Accredited/Institutional (full) |
| **Growth Stage** | comprehensive DD | all verified tiers per rules |

Stage gates which investor tiers may engage and the disclosure depth — a risk-management + suitability
control.

## 5. KYC REQUIREMENTS
Identity verification, sanctions/PEP screening, accreditation evidence capture, jurisdiction
determination, ongoing re-verification. Stored in the gated investment_investor_profiles (D-073);
never public. KYC provider integration likely required.

## 6. AML REQUIREMENTS
Sanctions/PEP screening at onboarding; suspicious-activity monitoring (relevant when fees/Billing
land); record-keeping; reporting obligations (FICA/comparable). No fund custody in the recommended
model — which substantially reduces AML exposure but does not eliminate onboarding obligations.

## 7. NDA GOVERNANCE
Legally-binding electronic NDA acceptance BEFORE any data-room document access (D-070 precondition);
acceptance record (who/when/version) immutable and audited; enforceability + e-signature validity
confirmed per jurisdiction; NDA version retained.

## 8. DATA ROOM GOVERNANCE
Per-document access logging (D-071); time-limited + revocable grants; watermarking; encryption at
rest (D-024/D-072); need-to-know redaction tiers; no public URLs (streamed, W2-5); access review +
revocation on deal end/withdrawal.

## 9. FINANCIAL INFORMATION GOVERNANCE
All financial/valuation/fundraising data confined to the data room (D-072); confidentiality +
need-to-know; encrypted; access audited HIGH; NEVER projected to Community/Marketplace/Knowledge/
Research/CMS/Showcase (Test B). Disclosure depth gated by investor tier + startup stage.

## 10. CAP-TABLE GOVERNANCE
Data room is the authoritative cap-table record (D-074); change audit HIGH; round history immutable;
reconciled with Startup Hub D-064 founder-ownership subset (no conflict; D-064 protections intact).
Accuracy/consistency controls; access restricted to entitled parties + ICS.

## 11. DUE-DILIGENCE GOVERNANCE
Structured, consent-based DD within a grant (Investment-Network-specific records, NOT Program Events,
per the standing rule); document collection gated/streamed; DD responses retained; access audited.

## 12. AUDIT & RETENTION REQUIREMENTS
Append-only audit (D-046) under INVESTMENT_MANAGEMENT (D-071) incl. per-document access; financial/
regulatory record retention per jurisdiction (commonly 5–7 years — confirm); access logs retained;
tamper-evident; exportable for regulator requests.

## 13. PRIVACY & DATA PROTECTION REQUIREMENTS
Lawful basis + consent for KYC/financial PII; data-subject rights; **data residency** (B-2 — some
regimes restrict cross-border transfer; hosting/region implications, D-037 VPS/region path);
cross-border transfer safeguards; breach notification; DPIA recommended given sensitivity.

## 14. RISK REGISTER

| ID | Risk | Class | Severity | Control |
|---|---|---|---|---|
| IR-1 | Unlicensed brokerage/dealing | Securities | **CRITICAL** | no execution/custody/contingent fees; counsel sign-off |
| IR-2 | Public solicitation of securities | Securities | **CRITICAL** | invitation-only; no public offers; accredited gating |
| IR-3 | Crowdfunding-without-license (Option D) | Securities | **CRITICAL** | exclude Option D unless licensed |
| IR-4 | KYC/AML failure | Regulatory | HIGH | KYC/AML program + provider |
| IR-5 | Financial data breach (data room) | Data leakage | **CRITICAL** | encryption + grant/NDA + per-doc audit (D-072) |
| IR-6 | Cross-border / exchange-control breach | Regulatory | HIGH | phased single-jurisdiction; FX counsel |
| IR-7 | Privacy / data-residency non-compliance | Privacy | HIGH | residency hosting; consent; DPIA |
| IR-8 | Cross-module financial contamination | Contamination | HIGH | Test B isolation; projection discipline |
| IR-9 | Multi-tenant data-room leakage (franchise) | Multi-tenant | HIGH | TenantScope wraps grants; isolation tests |
| IR-10 | NDA unenforceability | Legal | MEDIUM | counsel-validated e-NDA + retention |
| IR-11 | Reputational (failed deals/fraud) | Operational | MEDIUM | verification + disclaimers + moderation |

---

## ★ MANDATORY QUESTION — RECOMMENDED OPERATING MODEL

| Option | Description | Regulatory burden | Fit to approved architecture |
|---|---|---|---|
| A — Discovery Only | visibility only; no rooms/DD/matching | Lowest | UNDER-delivers (data rooms/DD already designed) |
| B — Managed Introduction | curated introductions; controlled access; no transactions | Low–moderate | Partial (no structured DD/rooms) |
| **C — Investment Facilitation** | **structured deal support, DD, data rooms; NO execution/custody** | **Moderate (manageable with controls)** | **MATCHES the approved architecture (D-070..D-074)** |
| D — Investment Marketplace | direct fundraising, investor participation, execution | Highest (portal/crowdfunding licensing) | Exceeds approved scope; would breach D-069 |

**RECOMMENDATION: OPTION C — Investment Facilitation Platform**, scoped with HARD GUARDRAILS that
keep it on the facilitation side of the regulatory line:
1. **No transaction execution and no fund custody** (deals are records; payment, if any, via Billing
   as fees — never investment capital).
2. **No public solicitation** — invitation-only, private data rooms; accredited/institutional gating
   for sensitive financials.
3. **No success/transaction-contingent compensation** on a raise (avoids brokerage indicator).
4. **No investment-matching engine that effects transactions** (AI matching, if any, is suggestion-
   only and deferred — D-029).
5. **Phased single-jurisdiction launch (Nigeria first)** with counsel sign-off before each new
   jurisdiction; no cross-border until separately cleared.

Justification: Option C delivers the platform's intended value (curated introductions + data rooms +
structured DD that PREPARE startups, per D-069) while staying short of brokerage/dealing and
crowdfunding (Options D). It maps exactly to the architecturally-approved design (D-070..D-074) and
to the D-069 Prepare-vs-Execute boundary — "execute" here means *facilitate the deal process*, NOT
*custody/transact capital*. Option D is explicitly EXCLUDED unless the platform obtains securities/
crowdfunding-portal licensing (a separate strategic + licensing decision, not in current scope).

---

## REQUIRED CONTROLS BEFORE IMPLEMENTATION (the D-075 conditions)

1. **Qualified local legal counsel sign-off** per launch jurisdiction (securities + data protection
   + FX) — confirming Option C scoping is non-licensable as structured, or specifying required
   licences/registrations.
2. **KYC/AML program** (provider + procedures) + investor accreditation verification.
3. **Counsel-validated e-NDA** (enforceability + retention).
4. **Data-residency + privacy compliance** (hosting region per B-2; consent; DPIA).
5. **The D-072 data-room controls** (encryption, grant+NDA gating, per-document audit, isolation).
6. **Guardrail enforcement** (no execution/custody/public-solicitation/contingent-fees) baked into
   the build and verified.
7. **Phased rollout plan** (single jurisdiction first).

---

## REQUIRED DELIVERABLE OUTCOME

### OUTCOME: **CONDITIONAL GO** (for Wave 5D, under Operating Model Option C)

- **NOT a full GO:** qualified legal counsel sign-off (condition 1) is a hard prerequisite that this
  analysis cannot itself satisfy (not legal advice).
- **NOT a NO GO:** the model is viable and the architecture is sound (D-070..D-074) — Option C with
  the stated guardrails is a recognised, implementable facilitation model.
- **CONDITIONAL GO** therefore: Wave 5D may proceed to implementation **ONLY** once D-075's seven
  conditions — above all (1) external counsel sign-off — are satisfied, under Option C, single
  jurisdiction first. **Option D remains NO GO without securities/crowdfunding licensing.**

D-075 stays OPEN/BLOCKING until the counsel sign-off and the seven conditions are evidenced.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Legal / Regulatory Counsel (per jurisdiction) | | | | |
| Compliance / Data Protection Officer | | | | |

**Status:** Governance analysis complete. **Wave 5D implementation REMAINS DENIED pending D-075
closure (external legal sign-off + the seven conditions).** Await approval before any implementation.
