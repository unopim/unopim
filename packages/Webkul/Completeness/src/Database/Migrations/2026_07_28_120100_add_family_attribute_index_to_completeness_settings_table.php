<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The completeness grid joins these settings by attribute while filtering by
 * family; only the foreign keys existed, so the join scanned the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completeness_settings', function (Blueprint $table): void {
            $table->index(['family_id', 'attribute_id'], 'cs_family_attribute_idx');
        });
    }

    public function down(): void
    {
        Schema::table('completeness_settings', function (Blueprint $table): void {
            $table->dropForeign(['family_id']);
            $table->dropIndex('cs_family_attribute_idx');
            $table->foreign('family_id')->references('id')->on('attribute_families')->cascadeOnDelete();
        });
    }
};
