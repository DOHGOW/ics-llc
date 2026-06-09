# BILLING SUBSCRIPTION — ARCHITECTURE REVIEW
# ICS Enterprise Ecosystem Platform — minimum Billing substrate for Membership

Version: 1.0
Date: 2026-06-05
Status: Architecture review — NO code/migrations/models/services. Design only.
Author: Lead Architect
Validates against: D-024, D-025, D-031, D-037, D-039, D-046, D-051, D-076, D-080..D-083; C-1..C-4
Inputs: DATABASE_BLUEPRINT (Billing module — fully pre-modeled), MEMBERSHIP_SYSTEM_ARCHITECTURE_REVIEW

> **The Billing schema is ALREADY fully modelled** in the blueprint (billing_plans,
> billing_subscriptions, billing_invoices, billing_invoice_items, billing_invoice_sequences,
> billing_payments, billing_webhooks) — with Paystack fields, idempotency keys, and tenant_id
> throughout. This review defines the MINIMUM subset to implement for Membership (D-083) and
> validates D-031 authority, immediate revocation, TenantScope, ContentAccessService integration,
> and webhook idempotency.

---

## EXECUTIVE SUMMARY

The minimum Billing substrate for Membership is: **billing_plans + billing_subscriptions +
billing_webhooks** (status engine) plus **billing_invoices/items/sequences + billing_payments**
(financial record). The substrate is **webhook-driven**: Paystack events transition the subscription
state machine; an active/trial subscription is the SOLE entitlement source (live status, C-3). Payment
EXECUTION can be sandboxed initially (Paystack test mode), but the lifecycle + webhook processing are
required. Idempotency is built into the schema (unique gateway_transaction_id / gateway_event_id).
**Verdict: SOUND.** The schema is sound and complete; the work is implementation + the documented
guardrails.

---

## 1. BILLING PLANS (billing_plans — exists)

- Plan = name/slug/type(subscription|one_time)/module/billing_period/price/currency/trial_days/
  gateway_plan_id (Paystack plan code) + the tier-grant hook (`knowledge_tier_grant`,
  `research_tier_grant`) consumed by Membership (D-080/C-2).
- `module='membership'` for membership plans; `is_active` toggles availability. tenant_id → per-tenant
  plans (C-4). No schema change needed.

## 2. BILLING SUBSCRIPTIONS (billing_subscriptions — exists) — the entitlement source

- user_id + plan_id + **status** (trial/active/past_due/cancelled/expired) + period
  (current_period_start/end, trial_ends_at, ends_at) + gateway_subscription_id/customer_id.
- **Entitlement (C-3):** a subscription grants its plan's tier ONLY while status ∈ {trial, active}.
  The MembershipTierResolver (D-080) reads LIVE status — no cached grant.
- Owner-scoped (user_id) + tenant-scoped (C-4).

## 3. INVOICE MODEL (billing_invoices/items/sequences — exists)

- Invoice = invoice_number (INV-YYYY-NNNNNN via billing_invoice_sequences, the same race-safe per-year
  pattern as training certs) + user/subscription + status (draft→issued→paid→overdue/cancelled/refunded)
  + amounts + currency + pdf_path. Items are polymorphic (billable_type/id) so any module can bill.
- For Membership: each billing cycle issues an invoice for the subscription; paid on payment success.

## 4. REVENUE TRACKING (billing_payments + analytics)

- billing_payments: gateway + **gateway_transaction_id UNIQUE (idempotency key)** + amount + status
  (pending/success/failed/refunded/chargeback) + paid_at + gateway_response JSON.
- analytics_revenue_daily (blueprint) aggregates gross/net/refunds per category — **per-tenant** dimension
  (D-078-B). MRR/ARR/churn from subscriptions + payments.

## 5. PAYSTACK INTEGRATION (D-037 config-only)

- Gateway is config/driver-selected (gateway enum paystack/flutterwave/stripe; D-037 — driver from .env).
- Plans map to Paystack plan codes (gateway_plan_id); subscriptions to gateway_subscription_id.
- Initialise transaction → redirect/checkout → webhook confirms. **Payment execution may run in Paystack
  TEST MODE first** (D-083: lifecycle required, live execution sandboxable).
- Secrets via env only (never in settings JSON, per core_tenants rule); keys per environment.

## 6. WEBHOOK PROCESSING (billing_webhooks — exists) — Mandatory Test E (idempotency)

- billing_webhooks: gateway + event_type + **gateway_event_id (idempotency)** + payload + **signature_valid**
  + **processed** + processed_at + error_message. Append-only.
- **Idempotency (E):** every webhook is recorded with gateway_event_id; processing checks
  `processed=0` AND a not-seen gateway_event_id before acting → a duplicate delivery is a no-op.
- **Signature verification FIRST:** reject/flag (signature_valid=0) before processing (D-039 security).
- Webhook handlers transition the subscription state machine + reconcile payments/invoices.
- Failed processing → error_message + retry (idempotent on replay).

## 7. SUBSCRIPTION STATE MACHINE (Mandatory Test B — immediate revocation)

```
trial ──(payment success)──► active ──(renewal success)──► active
  │                            │
  │ (trial end, no pay)        │ (payment failed) ──► past_due ──(grace fail / cancel)──► cancelled/expired
  ▼                            │ (user/admin cancel) ──► cancelled
expired                        │ (refund/chargeback) ──► cancelled (+ entitlement removed)
```
- **Entitlement is LIVE (C-3/B):** the resolver grants tier ONLY for {trial, active}. The MOMENT status
  leaves that set (past_due/cancelled/expired/refund), the tier elevation is gone — no cached grant,
  immediate revocation. past_due may keep a short grace (config) but is a policy decision (default: no
  content grant in past_due to be safe).

## 8. REFUND HANDLING (Mandatory Test B)

- Refund/chargeback webhook → billing_payments.status=refunded/chargeback → subscription → cancelled →
  **immediate entitlement removal** (C-3) → invoice status=refunded. Audited HIGH (refund-driven
  revocation, D-081). Reconciliation + (future) dunning.

## 9. AUDIT REQUIREMENTS (D-046)

- Propose `AuditCategory::BILLING_MANAGEMENT` (or reuse MEMBERSHIP_MANAGEMENT for membership subs).
  Audited: subscription created/activated/cancelled/expired, refund/chargeback (HIGH), manual override
  (HIGH, D-081), plan changes. Payment success/failure recorded (billing_payments is the financial record;
  audit the governance transitions).
- billing_webhooks is the immutable inbound-event log (forensic).

## 10. TENANT COMPATIBILITY (Mandatory Test C / C-4)

- billing_plans + billing_subscriptions + billing_invoices + billing_payments carry tenant_id (blueprint).
  On implementation, **add the billing models to the TenancyServiceProvider registry** → TenantScoped.
- billing_webhooks is gateway-inbound (not user-scoped); resolve tenant from the subscription/customer it
  references (a webhook may arrive without a tenant context — process in a system/super-tenant context,
  then reconcile to the subscription's tenant). billing_invoice_sequences is per (tenant, year).

## 11. ANALYTICS COMPATIBILITY (D-025 / W4-9 / D-078-B)

- Own aggregator: MRR/ARR, active subs, churn, trial→paid conversion, revenue by module/plan — **per-tenant**
  (consistent with the D-078-B tenant-dimension verification). No card/PII; financial aggregates only.

## 12. FAILURE RECOVERY

- **Idempotent webhooks** (replay-safe, E). **Reconciliation job:** periodically reconcile Paystack
  subscription/payment status vs local (catch missed webhooks) — the scheduled-job pattern (routes/console).
- **Dunning** (retry failed payments) — future. **Pending payment timeout** → failed. **Orphan invoice**
  cleanup. All recovery paths are idempotent + audited.

---

## MANDATORY VALIDATION

| Test | Result |
|---|---|
| **A** — D-031 remains authoritative | ✅ — this IS the D-031 substrate; no new billing decision; schema as blueprinted |
| **B** — immediate revocation (D-081/C-3) | ✅ — entitlement = live {trial,active} status; refund/cancel/expire/fail → instant removal; no cache |
| **C** — TenantScope compatible | ✅ — tenant_id on plans/subs/invoices/payments; add to registry; webhooks reconcile to the sub's tenant |
| **D** — ContentAccessService integration | ✅ — MembershipTierResolver reads active subs → tier grants; ContentAccessService elevate-only (C-1) |
| **E** — webhook idempotency | ✅ — gateway_event_id idempotency + processed flag + signature_valid; duplicate = no-op |

---

## FINDINGS

| ID | Severity | Finding | Disposition |
|---|---|---|---|
| **B-1** | HIGH | Entitlement must be LIVE-status (no cached grant); past_due grants no content by default | C-3; resolver reads status |
| **B-2** | HIGH | Webhook signature verified BEFORE processing; invalid flagged not processed | D-039 |
| **B-3** | HIGH | Webhook idempotency via gateway_event_id + processed; duplicates no-op | E |
| **B-4** | HIGH | Refund/chargeback → immediate cancellation + entitlement removal + HIGH audit | C-3/D-081 |
| **B-5** | MEDIUM | Webhook tenant context — resolve via the referenced subscription; process system-side then scope | C-4 |
| **B-6** | MEDIUM | Reconciliation job catches missed webhooks (idempotent) | failure recovery |
| **B-7** | MEDIUM | Secrets via env only; gateway driver config-only (D-037) | impl |
| **B-8** | LOW | Payment execution may run Paystack TEST MODE first (lifecycle required, execution sandboxable) | D-083 |
| **B-9** | LOW | Dunning/retry deferred | future |

### Minimum substrate for Membership (D-083)
Implement: **billing_plans, billing_subscriptions, billing_webhooks** (+ the subscription state machine
+ MembershipTierResolver wiring) as the entitlement core; **billing_invoices/items/sequences +
billing_payments** for the financial record. analytics_revenue_daily + dunning are follow-ons.

### Schema
**No schema changes** — the billing tables are fully blueprinted. Add the billing models to the
TenancyServiceProvider registry (C-4) and the D-078-A reference classification (plans are TENANT-OWNED
when per-tenant pricing is offered, else GLOBAL).

---

## FINAL VERDICT

**SOUND.** The Billing substrate is already fully modelled (D-031), webhook-driven, idempotent
(gateway_event_id), tenant-aware, and integrates with Membership via the live-status MembershipTier
Resolver feeding the elevate-only ContentAccessService hook. The minimum subset (plans + subscriptions
+ webhooks + invoices/payments) is sufficient for Membership entitlement, with payment execution
sandboxable initially. All five mandatory tests pass.

Proposed decisions to ratify on approval (NOT now):
- **D-084** — Billing subscription substrate = billing_plans + billing_subscriptions + billing_webhooks
  (state machine) + invoices/payments; webhook-driven, signature-verified, idempotent (gateway_event_id);
  no schema change.
- **D-085** — `AuditCategory::BILLING_MANAGEMENT` (subscription/refund/override events; refund-revocation
  + manual override HIGH). [MEMBERSHIP_MANAGEMENT (D-081) covers membership-subscription entitlement.]
- **D-086** — Billing models join the TenantScope registry; webhooks reconcile to the subscription's
  tenant; invoice sequences per (tenant, year).

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Finance / Billing | | | | |
| Security/Compliance | | | | |

**Status:** Awaiting Approval. **Do NOT implement Billing or Membership until approved and
D-084..D-086 are decided.**
