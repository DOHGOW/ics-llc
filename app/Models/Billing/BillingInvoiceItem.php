<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Invoice line item (billing_invoice_items). Polymorphic billable. Inherits tenancy via invoice. */
class BillingInvoiceItem extends Model
{
    protected $table = 'billing_invoice_items';

    protected $fillable = [
        'invoice_id', 'description', 'quantity', 'unit_price', 'subtotal', 'discount_pct',
        'module', 'billable_type', 'billable_id',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }
}
