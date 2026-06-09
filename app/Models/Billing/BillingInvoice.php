<?php

namespace App\Models\Billing;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Invoice (billing_invoices, D-086). Tenant-safe number INV-{TENANT}-{YYYY}-{NNNNNN}. */
class BillingInvoice extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'billing_invoices';

    public const STATUSES = ['draft', 'issued', 'paid', 'overdue', 'cancelled', 'refunded'];

    protected $fillable = [
        'tenant_id', 'invoice_number', 'user_id', 'subscription_id', 'status', 'issue_date', 'due_date',
        'paid_at', 'subtotal', 'discount_amount', 'tax_amount', 'total', 'currency', 'notes', 'pdf_path', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'due_date' => 'date', 'paid_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingInvoiceItem::class, 'invoice_id');
    }
}
