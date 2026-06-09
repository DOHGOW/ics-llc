<?php

/*
| Migration: backfill_tenant_id_default  (Wave FT-1 / D-077)
| ADDITIVE + REVERSIBLE backfill: assign existing rows to the ROOT default tenant so single-tenant
| behaviour is unchanged after activation and no row is left tenant-less. NO destructive change.
| down() reverses (nulls back ONLY the default-tenant backfill). Also adds tenant_id indexes
| (guarded — skipped if already present) for TenantScope query performance.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tenant-scoped tables to backfill + index (the finding-F parents + core_users/audit for hierarchy). */
    private array $tables = [
        'core_users', 'core_audit_logs',
        'content_pages', 'content_articles', 'content_media', 'content_engagement_events',
        'crm_accounts', 'crm_contacts', 'crm_leads', 'crm_opportunities', 'crm_activities',
        'client_projects', 'client_tickets',
        'partner_profiles', 'partner_referrals', 'partner_agreements',
        'knowledge_categories', 'knowledge_articles', 'knowledge_resources',
        'research_categories', 'research_authors', 'research_publications',
        'training_instructors', 'training_courses', 'training_lessons', 'training_enrollments', 'training_certificates',
        'community_profiles',
        'marketplace_listings', 'marketplace_applications', 'marketplace_listing_reports',
        'startup_profiles', 'startup_programs', 'program_cohorts', 'program_events',
    ];

    public function up(): void
    {
        $default = (int) config('ics.tenancy.default_tenant_id', 1);

        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $default]);
            $this->ensureTenantIndex($table);
        }
    }

    public function down(): void
    {
        $default = (int) config('ics.tenancy.default_tenant_id', 1);

        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            // Reverse ONLY the default-tenant backfill (data-safe rollback, D-077).
            DB::table($table)->where('tenant_id', $default)->update(['tenant_id' => null]);
        }
    }

    /** Add an index on tenant_id if one is not already present (guarded — no duplicate-index error). */
    private function ensureTenantIndex(string $table): void
    {
        $index = 'idx_'.$table.'_tenant';
        try {
            Schema::table($table, function ($t) use ($index) {
                $t->index('tenant_id', $index);
            });
        } catch (Throwable $e) {
            // Index already exists (e.g. CRM tables) — ignore.
        }
    }
};
