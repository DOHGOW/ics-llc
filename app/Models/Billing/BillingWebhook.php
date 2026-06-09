<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

/**
 * Inbound gateway webhook (billing_webhooks, D-084). APPEND-ONLY forensic log. NOT tenant-scoped
 * (gateway-inbound; tenant is resolved from the referenced subscription DURING processing, D-086).
 * Idempotency via (gateway, gateway_event_id) unique + the `processed` flag (replay safety).
 */
class BillingWebhook extends Model
{
    protected $table = 'billing_webhooks';

    public $timestamps = false;

    protected $fillable = [
        'gateway', 'event_type', 'gateway_event_id', 'payload', 'signature_valid', 'processed',
        'processed_at', 'error_message', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array', 'signature_valid' => 'boolean', 'processed' => 'boolean',
            'processed_at' => 'datetime', 'created_at' => 'datetime',
        ];
    }
}
