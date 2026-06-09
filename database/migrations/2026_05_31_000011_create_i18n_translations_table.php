<?php

/*
|--------------------------------------------------------------------------
| Migration: create_i18n_translations_table    (Task T-3.7)
|--------------------------------------------------------------------------
| Purpose:       Dynamic-content translation store for the i18n-first
|                architecture (D-014). Present from day one; dormant in Phase 1
|                (English via lang files). Populated when French (Phase 2) and
|                Arabic (Phase 3) are introduced — no schema change at that time
|                (D-037: architecture intact, implementation phased).
| Decision IDs:  D-014 (i18n), D-037 (built now, used later).
| Security:      Stores public-facing translatable content; no significant
|                sensitivity. Polymorphic — no FK.
| Dependencies:  None (polymorphic translatable_type/translatable_id).
| Extension pts: `locale` supports any locale (en/fr/ar/...); the composite
|                unique key prevents duplicate translations per field/locale.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('i18n_translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type', 100);
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 10);
            $table->string('field', 100);
            $table->longText('value');
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'uk_i18n_translations');
            $table->index('locale', 'idx_i18n_locale');
            $table->index(['translatable_type', 'translatable_id'], 'idx_i18n_translatable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('i18n_translations');
    }
};
