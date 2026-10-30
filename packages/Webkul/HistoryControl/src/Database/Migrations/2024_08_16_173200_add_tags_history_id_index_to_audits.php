<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Database\Schema\PrefixedIndexNameBlueprint;

return new class extends Migration
{
    /**
     * The audit_before_insert trigger runs two queries on every INSERT that
     * filter by (tags, history_id). Without an index these are full table
     * scans, so audit-heavy operations (e.g. creating an attribute, which
     * inserts one row per active locale) become extremely slow as the audits
     * table grows. This composite index turns those scans into index lookups.
     */
    public function up(): void
    {
        if (Schema::hasTable('audits') && ! PrefixedIndexNameBlueprint::indexExists('audits', 'audits_tags_history_id_index')) {
            Schema::table('audits', function (Blueprint $table): void {
                $table->index(['tags', 'history_id'], 'audits_tags_history_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('audits') && PrefixedIndexNameBlueprint::indexExists('audits', 'audits_tags_history_id_index')) {
            Schema::table('audits', function (Blueprint $table): void {
                $table->dropIndex('audits_tags_history_id_index');
            });
        }
    }

    /**
     * Check whether an index exists (prefix-aware, mysql + pgsql).
     */
};
