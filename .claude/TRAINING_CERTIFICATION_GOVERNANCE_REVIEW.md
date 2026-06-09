# TRAINING CERTIFICATION GOVERNANCE REVIEW
# ICS Enterprise Ecosystem Platform — Wave 4a (Training Institute)

Version: 1.0
Date: 2026-06-02
Status: Governance design — drives Wave 4a certificate implementation (D-059)
Author: Lead Architect
Decision References: D-046 (audit), D-058 (CertificateIssued = HIGH), D-037 (config-only),
D-024 (storage), D-031 (paid courses)

Purpose: define the certification standard BEFORE building it, so the implementation is
governed and tamper-evident. This document defines D-059 (a blueprint amendment to
training_certificates).

---

## 1. CERTIFICATE NUMBERING STANDARD

**Format:** `ICS-CERT-{YYYY}-{NNNNNN}`
- `ICS-CERT` — fixed prefix (institutional namespace).
- `{YYYY}` — issue year (4-digit).
- `{NNNNNN}` — zero-padded, monotonically increasing per-year sequence (6 digits → 999,999
  certs/year; widens automatically beyond that).

**Allocation:** a dedicated per-year sequence table `training_certificate_sequences`
(tenant_id, year, last_sequence), incremented inside a DB transaction with row locking —
the SAME proven pattern as `billing_invoice_sequences`. Guarantees: gapless-enough, unique,
race-safe (no two issuers get the same number).

**Immutability:** the certificate_number is allocated ONCE at issuance and never changes —
not on reissue (a reissue gets a NEW number; see §5). `UNIQUE` constraint enforced.

**Integrity:** a `verification_hash` = SHA-256 over a canonical payload
(`certificate_number | user_id | course_id | issued_at | status`) is stored at issuance.
The public verifier recomputes/compares so a tampered record is detectable. (Phase 2 may
upgrade to a signed token / QR; no schema change — D-037.)

## 2. VERIFICATION ARCHITECTURE

- **Public, read-only endpoint:** `GET /api/v1/training/certificates/verify/{number}` — NO
  authentication required (employers/third parties verify).
- **Minimal disclosure (privacy):** returns only `certificate_number`, holder display name,
  course title, `issued_at`, `status` (valid/expired/revoked/superseded), and `expires_at`.
  NO email, NO scores, NO internal IDs, NO PII beyond name + course.
- **Authoritative status:** the verifier reports the LIVE status — a revoked or expired
  certificate verifies as REVOKED/EXPIRED even if the holder still has the PDF.
- **verification_url** on the row stores the canonical public URL (built from the number).
- **PDF delivery** (the holder's own copy) is policy-gated/streamed (W2-5 posture); the
  public verifier never streams the PDF, only the status payload.
- **Rate-limited** (public-forms throttle) to deter enumeration; numbers are non-sequential-
  guessable only weakly (year+seq), so the hash + minimal disclosure are the real defence.

## 3. REVOCATION PROCESS

- **Who:** ICS staff holding `training.certificates.issue` (issuers) — a staff-only
  governance action; never the holder.
- **Effect:** sets `status = revoked`, `revoked_at`, `revoked_by`, `revocation_reason`
  (required). The certificate row is NEVER deleted (audit/history) — revocation is a state.
- **Verification:** immediately reports REVOKED (+ optional reason category) — the PDF the
  holder retains is now publicly invalid.
- **Audit:** `CertificateRevoked` → AuditEventSubscriber under TRAINING_MANAGEMENT,
  **HIGH-sensitivity** (D-058 spirit: credential integrity). Reason captured.
- **Irreversibility:** revocation is terminal; restoring a credential requires a REISSUE
  (new number), not un-revoking.

## 4. EXPIRY MODEL

- **Optional per course:** `training_courses.validity_months` (nullable). NULL = the
  certificate never expires (default). A positive value sets
  `certificate.expires_at = issued_at + validity_months`.
- **Status, not deletion:** expiry is computed/displayed; a scheduled job (or lazy check at
  verify time) flips `status` valid→expired when `expires_at < now()`. The verifier always
  reflects live expiry regardless of the stored flag.
- **Renewal:** an expired credential is renewed by completing the course again (a new
  enrollment → new certificate), OR by reissue if the policy allows continuing education.
- No expiry retro-applies to already-issued certificates if a course's validity_months
  changes later (the cert's expires_at is fixed at issuance) — predictable for holders.

## 5. REISSUE POLICY

- **When:** corrected holder name, regenerated PDF (template change), or administrative
  correction — NOT for revoked credentials (revocation is terminal).
- **Mechanism:** reissue creates a NEW certificate row with a NEW `certificate_number`,
  sets `reissued_from_id` → the prior row, and marks the prior row `status = superseded`.
  The achievement (enrollment_id, course, issued_at lineage) is preserved.
- **Why a new number:** keeps every printed/shared artefact independently verifiable; the
  superseded number verifies as SUPERSEDED with a pointer to the current one.
- **Who + audit:** staff-only (`training.certificates.issue`); `CertificateReissued` audited
  under TRAINING_MANAGEMENT.
- **Idempotency:** reissue is explicit and logged; the original `issued_at` of the
  achievement is retained on the new row (`issued_at` of the *credential*), while a separate
  `created_at` captures the reissue moment.

---

## 6. DATA MODEL (D-059 — blueprint amendment to training_certificates)

Columns beyond the original blueprint:
```
status            ENUM('valid','expired','revoked','superseded') NOT NULL DEFAULT 'valid'
expires_at        TIMESTAMP NULL                 -- from course validity_months
revoked_at        TIMESTAMP NULL
revoked_by        BIGINT UNSIGNED NULL
revocation_reason TEXT NULL
reissued_from_id  BIGINT UNSIGNED NULL           -- prior certificate (reissue lineage)
verification_hash CHAR(64) NULL                  -- SHA-256 integrity
```
Plus `training_courses.validity_months TINYINT UNSIGNED NULL` and a new
`training_certificate_sequences` table (tenant_id, year, last_sequence).

---

## 7. GOVERNANCE CONTROLS SUMMARY

| Control | Rule |
|---|---|
| Issue | only on completed enrollment (course completion verified); staff/automated on completion; audited HIGH |
| Number | ICS-CERT-{YYYY}-{NNNNNN}, per-year sequence, unique, immutable |
| Verify | public, minimal disclosure, live status, hash-checked |
| Revoke | staff-only, reason required, terminal, audited HIGH |
| Expire | optional per course; status-based; live at verify |
| Reissue | staff-only, new number, supersedes prior, lineage kept, audited |
| Integrity | verification_hash; PDF gated; verifier never leaks PII |

---

## VERDICT

**APPROVED STANDARD — proceed to Wave 4a with these certificate rules (D-059).** The
training_certificates implementation MUST realise §1–§6; the WAVE_4A_TRAINING_IMPLEMENTATION
_REVIEW will validate them under "Certificate Governance Validation."

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Compliance | | | | |
