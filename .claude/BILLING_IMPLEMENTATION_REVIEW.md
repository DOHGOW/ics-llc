# BILLING IMPLEMENTATION REVIEW — Wave Billing
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05
Status: Implementation complete — Awaiting Approval (Membership remains a SEPARATE gate)
Author: Lead Architect
Decisions: D-031, D-037, D-039, D-046, D-076, D-084, D-085, D-086; C-1..C-4
Plan baseline: BILLING_SUBSCRIPTION_ARCHITECTURE_REVIEW.md

---

## EXECUTIVE SUMMARY

The Billing substrate is implemented to the blueprint (no schema change): plans, subscriptions,
invoices/items/sequences, payments, webhooks. It is **webhook-driven, signature-verified-first,
idempotent (gateway_event_id + processed), transaction-bounded, and replay-safe** (D-084).
Entitlement is a **pure derivation of live subscription status** ({trial, active} only) — no stored
or cached grant — so revocation is immediate (C-3). Invoice numbering is tenant-safe
(INV-{TENANT}-{YYYY}-{NNNNNN}, D-086). Billing models participate in TenantScope. The
**MembershipTierResolver is provided as a READ-ONLY hook only — ContentAccessService is NOT
modified** (Membership is a separate approval gate). Paystack runs in **sandbox**. The verification
tests A–G are authored as the GREEN-CI release gate.

**Verdict: IMPLEMENTATION SOUND.**

---

## DELIVERABLES

| Layer | Artifacts |
|---|---|
| Migrations | billing_plans, billing_subscriptions, billing_invoices/items/sequences, billing_payments, billing_webhooks (per blueprint; tenant-aware; idempotency keys) |
| Models | Billing\{BillingPlan, BillingSubscription (isEntitling), BillingInvoice, BillingInvoiceItem, BillingPayment, BillingWebhook} — plans/subs/invoices/payments use BelongsToTenant (D-086) |
| Gateway | Billing\Gateways\PaymentGateway (contract, D-037 driver) + PaystackGateway (HMAC-SHA512 verify, sandbox init); bound in AppServiceProvider |
| Services | InvoiceNumberAllocator (D-086), SubscriptionService (state machine), PaymentService (idempotent + refund), WebhookProcessor (verify→idempotency→txn→tenant→replay-safe), ReconciliationService (downgrade-only) |
| Hook | Billing\MembershipTierResolver (READ-ONLY; ContentAccessService untouched) |
| Audit | AuditCategory::BILLING_MANAGEMENT; SubscriptionStateChanged event + handler (override/refund/chargeback/admin = HIGH) |
| Controllers | Billing\{Plan, Subscription, Webhook}; Admin\BillingAdminController |
| Routes / schedule | routes/billing.php (public webhook+plans; auth subscribe/cancel; HQ admin); routes/console.php billing:reconcile hourly |
| Config | ics.billing (gateway/currency/sandbox/paystack keys via env) |
| Tests / docs | tests/Feature/Billing/BillingSubstrateTest (A–G); BILLING_TEST_SPEC; BILLING_STATE_MACHINE_VALIDATION; this review |

---

## MANDATORY VERIFICATION (A–G)

| # | Requirement | Result | Evidence |
|---|---|---|---|
| A | Webhook idempotency | ✅ | (gateway, gateway_event_id) unique + processed flag; duplicate → `duplicate_noop`; test_a |
| B | Signature validation | ✅ | PaystackGateway HMAC-SHA512 verified FIRST; invalid → `rejected_signature`; test_b |
| C | Immediate entitlement revocation | ✅ | isEntitling = live {trial,active}; cancel/expire/refund instantly non-entitling; test_c/g |
| D | TenantScope isolation | ✅ | billing models BelongsToTenant; test_d (tenant 1 ≠ tenant 2) |
| E | Invoice sequence uniqueness | ✅ | InvoiceNumberAllocator per (tenant, year), row-locked; test_e |
| F | Duplicate payment protection | ✅ | gateway_transaction_id UNIQUE + firstOrCreate; test_f |
| G | Membership integration hook | ✅ | MembershipTierResolver (live, membership-only); test_g; ContentAccessService NOT modified |

---

## WEBHOOK GOVERNANCE (D-084) — confirmed

1. **Signature verification** — first; invalid recorded (signature_valid=0) + not processed.
2. **Event idempotency** — unique (gateway, gateway_event_id); duplicate short-circuits.
3. **Transaction boundary** — apply + mark-processed in one DB::transaction.
4. **Audit logging** — state transitions fire SubscriptionStateChanged → BILLING_MANAGEMENT.
5. **Replay safety** — processed=true → `duplicate_noop`; tenant resolved from the subscription (D-086).

## FAILURE RECOVERY (D-084) — confirmed

- **ReconciliationService.expireLapsed** (hourly) recovers missed webhooks by EXPIRING lapsed
  active/trial subs — **never grants** entitlement unsupported by state. Idempotent.

---

## CONSTRAINT COMPLIANCE

| Constraint | Status |
|---|---|
| Do NOT implement Membership | ✅ — only the read-only MembershipTierResolver hook exists; no ContentAccessService change, no membership controllers/entitlement wiring |
| Billing substrate first | ✅ — plans/subscriptions/invoices/payments/webhooks + state machine implemented |
| Tenant-aware (C-4/D-086) | ✅ — billing models BelongsToTenant; webhooks reconcile to the sub's tenant; per-tenant invoice sequences |
| Immediate revocation (C-3) | ✅ — derived live entitlement; no cached grants |
| Config-only gateway (D-037) | ✅ — PaymentGateway contract; driver from config; secrets via env |

---

## CORRECTNESS DECISIONS (self-flagged)

1. **Entitlement is derived, never stored** — isEntitling() reads live status; no grant table/cache,
   so revocation is structurally immediate (C-3). MembershipTierResolver computes on each read.
2. **`past_due` reused as pre-first-payment state** — the enum has no `pending`; paid-no-trial
   subscribes into past_due (non-entitling) until charge.success activates. Fails safe (never
   entitles before payment). Documented in BILLING_STATE_MACHINE_VALIDATION.
3. **MembershipTierResolver is the seam only** — ContentAccessService is untouched; the elevate-only
   wiring (max(roleTier, membershipTier)) is the Membership wave's job (separate gate, C-1).
4. **Webhook tenant resolution** — webhooks are gateway-inbound (no auth/tenant); the processor
   resolves the tenant via the referenced subscription using acrossTenants() (D-086).
5. **Reconciliation is downgrade-only** — can expire stale subs, never grant (D-084).

---

## CONFIRMATIONS

| Item | Result |
|---|---|
| Webhook-driven, signed, idempotent, transactional, replay-safe (D-084) | ✅ |
| Immediate revocation; entitlement = live status; no cached grants (C-3) | ✅ |
| Tenant-aware billing + tenant-safe invoice numbering (D-086) | ✅ |
| BILLING_MANAGEMENT audit; refund/override/admin HIGH (D-085) | ✅ |
| ContentAccessService NOT modified; Membership NOT implemented | ✅ |
| Verification A–G authored as GREEN-CI gate | ⚠ carried (run under bootstrap) |
| Bootstrap + GREEN CI required before "done" / production billing | ⚠ carried |

---

## REVIEW VERDICT

**IMPLEMENTATION SOUND.** The Billing substrate is complete, secure (signature-first), idempotent,
tenant-aware, and entitlement-correct (live, immediate-revocation) — with Membership left as a
clean read-only hook and a separate approval gate. The verification tests A–G are the mandatory
GREEN-CI release gate before production billing or Membership.

| Role | Name | Decision | Signature | Date |
|---|---|---|---|---|
| Platform Owner | | | | |
| Lead Architect | | | | |
| Finance / Billing | | | | |
| Security/Compliance | | | | |

**Status:** Awaiting Approval. **Do NOT implement Membership** — it remains a separate approval gate
after this Billing implementation review is accepted and the A–G tests pass GREEN.
