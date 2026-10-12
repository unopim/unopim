<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        if (Schema::hasTable('audits') && ! $this->indexExists('audits', 'audits_tags_history_id_index')) {
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
        if (Schema::hasTable('audits') && $this->indexExists('audits', 'audits_tags_history_id_index')) {
            Schema::table('audits', function (Blueprint $table): void {
                $table->dropIndex('audits_tags_history_id_index');
            });
        }
    }

    /**
     * Check whether an index exists (prefix-aware, mysql + pgsql).
     */
    private function indexExists(string $table, string $index): bool
    {
        $prefix = DB::getTablePrefix();

        if (DB::getDriverName() === 'pgsql') {
            return (bool) DB::select('SELECT 1 FROM pg_indexes WHERE indexname = ?', [$index]);
        }

        foreach (DB::select("SHOW INDEX FROM `{$prefix}{$table}`") as $row) {
            if (($row->Key_name ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};
