<?php

/*
| Migration: create_client_ticket_replies_table  (Wave 2 / W2-1 / W2-4)
| CHILD of client_tickets — parent-isolated (W2-1). `is_internal=1` replies are STAFF-ONLY
| and MUST be filtered from every client-facing path (query + policy + resource, W2-4).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('author_id');
            $table->text('body');
            $table->boolean('is_internal')->default(false); // W2-4: hidden from clients
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index('ticket_id', 'idx_ticket_replies_ticket');

            $table->foreign('ticket_id', 'fk_ticket_replies_ticket')
                ->references('id')->on('client_tickets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_ticket_replies');
    }
};
