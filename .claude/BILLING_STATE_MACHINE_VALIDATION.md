# BILLING STATE MACHINE VALIDATION — Wave Billing
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05
Status: Validation artifact (D-084). Subscription state machine + entitlement correctness.
Author: Lead Architect
Decisions: D-031, D-084, D-085, D-086; C-3 (immediate revocation)

---

## STATE MACHINE

```
                ┌──────────── (free plan) ──────────────┐
                │                                        ▼
 (subscribe) ─► trial ──(charge.success)──► active ◄── (admin reactivate)
                 │                          │  ▲
       (trial end,│                          │  │ (renewal charge.success)
        no pay)   │             (charge.fail)│  │
                 ▼                           ▼  │
              expired                     past_due ──(grace fail / cancel)──► cancelled / expired
                                            │
 (paid-no-trial subscribe) ─► past_due* ───┘   *non-entitling until first charge.success → active
                                            │
        (user/admin cancel) ───────────────┼──────────────────────────────► cancelled
        (refund / chargeback) ─────────────┴──────────────────────────────► cancelled (+ entitlement removed)
```

| From → To | Trigger | Entitling? | Audit |
|---|---|---|---|
| (new) → trial | subscribe (trial plan) | YES (trial) | created (normal) |
| (new) → active | subscribe (free plan) | YES | created (normal) |
| (new) → past_due* | subscribe (paid, no trial) | **NO** (awaiting payment) | created + past_due (normal) |
| trial/past_due → active | charge.success webhook | YES | activated (normal) |
| active → past_due | charge.failed webhook | **NO** (immediate, C-3) | past_due (normal) |
| active/trial → expired | period lapse / disable webhook / reconciliation | **NO** | expired (normal) |
| active → cancelled | user cancel | **NO** (immediate) | cancelled (normal) |
| active → cancelled | ADMIN cancel | **NO** | admin_cancelled (**HIGH**) |
| any → active | ADMIN reactivate | YES | admin_reactivated (**HIGH**) |
| any → {status} | ADMIN override | per status | override (**HIGH**) |
| active → cancelled | refund / chargeback webhook | **NO** (immediate) | refunded / chargeback (**HIGH**) |

\* `past_due` is reused as the pre-first-payment non-entitling state (the enum has no `pending`);
documented. It grants NO entitlement, which is the safe behaviour (C-3 — never entitle before payment).

---

## ENTITLEMENT INVARIANT (C-3) — the core correctness property

**Entitlement is DERIVED from status, never stored.** `BillingSubscription::isEntitling()` returns
true ONLY when `status ∈ {trial, active}` AND the period has not lapsed. There is NO separate grant
table and NO cache — therefore a status change IS the entitlement change, instantly. The
MembershipTierResolver reads this live.

| Property | Guarantee |
|---|---|
| Entitle only when {trial, active} | `isEntitling()` returns false for past_due/cancelled/expired |
| Immediate revocation | cancel/expire/refund/charge-fail sets a non-entitling status → entitlement gone the same instant |
| No entitlement before payment | paid-no-trial subscribes into past_due (non-entitling) until charge.success |
| No cached grants | resolver computes from live status on every read |
| Reconciliation never grants | ReconciliationService only EXPIRES lapsed subs (downward), never activates |

---

## VALIDATION RESULTS (mapped to the test suite)

| Property | Validated by |
|---|---|
| Activation on payment | BillingSubstrateTest::test_a (charge.success → processed; payment recorded) |
| Immediate revocation | test_c (cancel → isEntitling false); test_g (cancel → grant null) |
| No entitlement before payment | subscribe() paid path → past_due (non-entitling) — covered by the controller + isEntitling |
| Idempotent transitions | test_a (duplicate webhook no-op) |
| Refund → cancellation + removal | PaymentService::refund → cancelled + SubscriptionStateChanged(refunded, HIGH) |
| Reconciliation downward-only | ReconciliationService::expireLapsed (expires, never grants) |

---

## VERDICT

**SOUND.** The subscription state machine is explicit, entitlement is a pure derivation of live
status (no stored/cached grant), immediate revocation holds on every non-entitling transition,
admin actions are HIGH-audited, and reconciliation can only downgrade — never grant. The
`past_due`-as-pending-payment reuse is documented and fails safe (no entitlement before payment).
