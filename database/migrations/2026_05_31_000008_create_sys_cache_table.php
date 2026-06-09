<?php

/*
|--------------------------------------------------------------------------
| Migration: create_sys_cache_table            (Task T-3.7)
|--------------------------------------------------------------------------
| Purpose:       Database cache store option for the shared-hosting runtime
|                profile (D-037). Keeps cache off the connection-limited path
|                where APCu is unavailable (LIM-08 trade-off).
| Decision IDs:  D-037 (config-driven cache store).
| Security:      Cached values may hold sensitive computed data; the table is
|                never web-accessible. No PII should be cached unencrypted.
| Dependencies:  None.
| Extension pts: A `sys_cache_locks` table is required by Laravel's database
|                cache ONLY if atomic locks (Cache::lock) are used — it is NOT
|                in DATABASE_BLUEPRINT; add via a blueprint amendment if/when
|                atomic locks are adopted. On VPS, CACHE_STORE=redis bypasses
|                this table (config-only, D-037).
| Companion cfg: REQUIRED wiring — config/cache.php database store
|                'table' => 'sys_cache'. Applied as a config step (not a migration).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_cache');
    }
};
