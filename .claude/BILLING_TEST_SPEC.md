# BILLING TEST SPEC — Wave Billing
# ICS Enterprise Ecosystem Platform

Version: 1.0
Date: 2026-06-05
Status: Test specification (mandatory release gate, D-084). Tests authored; must run GREEN in CI.
Author: Lead Architect
Decisions: D-084, D-085, D-086; C-3

> Authored suite: `tests/Feature/Billing/BillingSubstrateTest.php` (verification A–G) +
> `tests/Feature/Tenancy/CrossTenantIsolationTest.php` (tenant isolation, shared with FT-1).
> These are GREEN-CI release gates (R-012/R-013).

---

## MANDATORY VERIFICATION A–G

| # | Requirement | Test | Asserts |
|---|---|---|---|
| **A** | Webhook idempotency | test_a_webhook_idempotency | first delivery `processed`; duplicate `duplicate_noop`; only ONE payment row |
| **B** | Signature validation | test_b_signature_validation | wrong signature → `rejected_signature`, not processed |
| **C** | Immediate entitlement revocation | test_c_immediate_revocation | active sub entitling; after cancel → not entitling |
| **D** | TenantScope isolation | test_d_tenant_isolation | tenant 1 cannot see tenant 2's subscription (enabled) |
| **E** | Invoice sequence uniqueness | test_e_invoice_sequence_uniqueness | 5 allocations unique + INV-{TENANT}- prefix |
| **F** | Duplicate payment protection | test_f_duplicate_payment_protection | repeat gateway_transaction_id → ONE row |
| **G** | Membership integration hook | test_g_membership_hook | active membership → grant tier; cancel → grant removed |

---

## ADDITIONAL COVERAGE (recommended for CI completeness)

| Area | Scenario |
|---|---|
| Webhook signature (valid) | correct HMAC-SHA512 in non-sandbox → processed |
| charge.failed | active → past_due; entitlement removed |
| subscription.disable | active → expired |
| refund.processed | payment refunded + subscription cancelled + HIGH audit |
| charge.dispute.create | chargeback + cancellation |
| Reconciliation | lapsed active → expired; never re-activates |
| Admin override/reactivate | HIGH-sensitivity audit recorded under BILLING_MANAGEMENT |
| Invoice numbering per tenant | two tenants get independent sequences (INV-{T}-{YYYY}-000001 each) |
| Paid-no-trial subscribe | created non-entitling (past_due) until charge.success |
| Tenant isolation — invoices/payments | tenant A cannot read tenant B invoices/payments |

---

## AUDIT ASSERTIONS (D-085)

| Event | Sensitivity to assert |
|---|---|
| created / activated / past_due / expired / user cancel | normal |
| admin cancel / reactivate / override | HIGH |
| refund / chargeback | HIGH |
| (reconciliation downgrade) | normal (system) |

Routine payment-success → normal. All under category `billing_management`, append-only.

---

## RUN

```
php artisan test --filter=BillingSubstrateTest
php artisan test --filter=CrossTenantIsolationTest
```

GREEN required before Membership implementation (separate gate) and before any production billing.
MySQL recommended in CI (FULLTEXT/enum/locking parity); the suite uses RefreshDatabase.
