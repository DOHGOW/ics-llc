<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\DB;

/**
 * Tenant-safe invoice numbering (D-086): INV-{TENANT}-{YYYY}-{NNNNNN}. Per (tenant, year)
 * sequence allocated under a row lock inside a transaction (race-safe — same pattern as training
 * certificates). Guarantees uniqueness within a tenant+year (Test E).
 */
class InvoiceNumberAllocator
{
    public function next(?int $tenantId): string
    {
        $year = (int) now()->year;
        $tenantKey = $tenantId ?? (int) config('ics.tenancy.default_tenant_id', 1);

        $seq = DB::transaction(function () use ($tenantId, $year): int {
            $row = DB::table('billing_invoice_sequences')
                ->where('tenant_id', $tenantId)->where('year', $year)->lockForUpdate()->first();

            if ($row === null) {
                DB::table('billing_invoice_sequences')->insert([
                    'tenant_id' => $tenantId, 'year' => $year, 'last_sequence' => 1,
                ]);

                return 1;
            }

            $next = $row->last_sequence + 1;
            DB::table('billing_invoice_sequences')->where('id', $row->id)->update(['last_sequence' => $next]);

            return $next;
        });

        return sprintf('INV-%d-%d-%06d', $tenantKey, $year, $seq);
    }
}
