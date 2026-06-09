<?php

/*
| Migration: create_startup_ownership_transfers_table  (Wave 5A / H-2 / D-064)
| IMMUTABLE founder-ownership transfer history. A startup can never be orphaned: ownership
| transfer is mandatory before founder removal. Append-only (no update/delete path in code);
| each transfer is audited HIGH (STARTUP_MANAGEMENT).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_ownership_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('startup_id');
            $table->unsignedBigInteger('from_founder_id')->nullable();
            $table->unsignedBigInteger('to_founder_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('startup_id', 'idx_startup_transfers_startup');
            $table->foreign('startup_id', 'fk_startup_transfers_startup')
                ->references('id')->on('startup_profiles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_ownership_transfers');
    }
};
