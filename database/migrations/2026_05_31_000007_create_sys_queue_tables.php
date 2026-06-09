<?php

/*
|--------------------------------------------------------------------------
| Migration: create_sys_queue_tables           (Task T-3.7)
|--------------------------------------------------------------------------
| Purpose:       Database-driven queue backend for the shared-hosting runtime
|                profile (D-037): sys_jobs (pending) + sys_failed_jobs.
|                Processed by cron `queue:work --stop-when-empty` on shared.
| Decision IDs:  D-037 (shared profile = database queue), D-022 (queued
|                notifications), Governance §7 (heavy listeners ShouldQueue).
| Security:      Job `payload` is serialized — never enqueue secrets in plain
|                payloads. `sys_failed_jobs.exception` may contain sensitive
|                detail; restrict access to admins.
| Dependencies:  None.
| Extension pts: On VPS, QUEUE_CONNECTION=redis bypasses these tables entirely
|                (config-only, D-037). `job_batches` table can be added if job
|                batching is adopted (blueprint amendment).
| Companion cfg: REQUIRED wiring — config/queue.php database connection
|                'table' => 'sys_jobs' and 'failed' => ['table' =>
|                'sys_failed_jobs']. Applied as a config step (not a migration).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue');
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');

            $table->index(['queue', 'reserved_at', 'available_at'], 'idx_sys_jobs_queue');
        });

        Schema::create('sys_failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_failed_jobs');
        Schema::dropIfExists('sys_jobs');
    }
};
