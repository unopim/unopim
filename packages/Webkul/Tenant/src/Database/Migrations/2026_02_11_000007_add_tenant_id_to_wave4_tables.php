<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wave 4 Dependent tables that receive tenant_id.
     *
     * These are child/translation/pivot tables whose parent tables
     * already have tenant_id from Waves 1-3. Adding tenant_id here
     * provides defense-in-depth isolation for direct queries.
     */
    private array $tables = [
        // Operational
        'job_track_batches',
        'audits',

        // Translation tables
        'attribute_translations',
        'attribute_option_translations',
        'attribute_family_translations',
        'attribute_group_translations',
        'category_field_translations',
        'category_field_option_translations',
        'channel_translations',

        // Product pivot/mapping tables
        'product_relations',
        'product_super_attributes',
    ];

    /**
     * Run the migrations.
     *
     * Add tenant_id to Wave 4 Dependent Tables.
     * Backfill existing rows to tenant_id = 1 (default tenant, D2).
     * Add composite index (tenant_id, id) where applicable.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $hasIdColumn = Schema::hasColumn($table, 'id');

            Schema::table($table, function (Blueprint $blueprint) use ($table, $hasIdColumn) {
                $afterColumn = $hasIdColumn ? 'id' : null;

                $col = $blueprint->unsignedBigInteger('tenant_id')->nullable();

                if ($afterColumn) {
                    $col->after($afterColumn);
                }

                if ($hasIdColumn) {
                    $blueprint->index(['tenant_id', 'id'], "{$table}_tenant_id_id_index");
                } else {
                    $blueprint->index(['tenant_id'], "{$table}_tenant_id_index");
                }

                $blueprint->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->nullOnDelete();
            });

            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $hasIdColumn = Schema::hasColumn($table, 'id');

            Schema::table($table, function (Blueprint $blueprint) use ($table, $hasIdColumn) {
                $blueprint->dropForeign(["{$table}_tenant_id_foreign"]);

                if ($hasIdColumn) {
                    $blueprint->dropIndex("{$table}_tenant_id_id_index");
                } else {
                    $blueprint->dropIndex("{$table}_tenant_id_index");
                }

                $blueprint->dropColumn('tenant_id');
            });
        }
    }
};
