<?php

namespace App\Models\Billing;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment (billing_payments, D-084). Tenant-scoped (D-086). gateway_transaction_id is UNIQUE —
 * the duplicate-payment idempotency key (Test F). gateway_response holds the raw verification.
 */
class BillingPayment extends Model
{
    use BelongsToTenant;

    protected $table = 'billing_payments';

    public const STATUSES = ['pending', 'success', 'failed', 'refunded', 'chargeback'];

    protected $fillable = [
        'tenant_id', 'invoice_id', 'user_id', 'gateway', 'gateway_transaction_id', 'gateway_transaction_ref',
        'amount', 'currency', 'status', 'payment_method', 'channel', 'paid_at', 'gateway_response',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'gateway_response' => 'array', 'amount' => 'decimal:2'];
    }
}
