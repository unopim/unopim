<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Database\Schema\PrefixedIndexNameBlueprint;

return new class extends Migration
{
    /**
     * The pre-level-column unique key that must not survive alongside the
     * (variant_structure_id, level, position) key added by the previous
     * migration. Note: that composite key is left alone here — it also
     * backs the `variant_structure_id` foreign key, so dropping it would
     * fail with "Cannot drop index ...: needed in a foreign key constraint".
     */
    private const string LEGACY_INDEX = 'vsax_structure_position_unique';

    public function up(): void
    {
        if (! Schema::hasTable('variant_structure_axes')) {
            return;
        }

        if (PrefixedIndexNameBlueprint::indexExists('variant_structure_axes', self::LEGACY_INDEX)) {
            Schema::table('variant_structure_axes', function (Blueprint $table): void {
                $table->dropUnique(self::LEGACY_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('variant_structure_axes')) {
            return;
        }

        if (! PrefixedIndexNameBlueprint::indexExists('variant_structure_axes', self::LEGACY_INDEX)) {
            Schema::table('variant_structure_axes', function (Blueprint $table): void {
                $table->unique(['variant_structure_id', 'position'], self::LEGACY_INDEX);
            });
        }
    }

    /**
     * Check whether an index exists (prefix-aware, mysql + pgsql).
     */
};
