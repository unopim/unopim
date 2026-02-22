<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F-003: Add optimistic locking version column to all pricing tables.
 *
 * This prevents concurrent edits from silently overwriting each other.
 * Controllers check the version before updating and return HTTP 409 on mismatch.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = ['product_costs', 'channel_costs', 'margin_protection_events', 'pricing_strategies'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('version')->default(0)->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        $tables = ['product_costs', 'channel_costs', 'margin_protection_events', 'pricing_strategies'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }
    }
};
