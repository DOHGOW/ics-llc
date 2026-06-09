<?php

/*
| Migration: create_partner_tiers_table  (Wave 2)
| Reference data (tier benefits, min_referrals, commission_rate). NOT org-owned — public-ish
| catalogue read by the portal; managed by ICS staff.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->json('benefits')->nullable();
            $table->unsignedInteger('min_referrals')->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0); // % → commission (D-031)
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_tiers');
    }
};
