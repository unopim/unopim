<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wave 5 Completeness tables that receive tenant_id.
     */
    private array $tables = [
        'completeness_settings',
        'product_completeness',
    ];

    /**
     * Run the migrations.
     *
     * Add tenant_id to Wave 5 Completeness Tables.
     * Backfill existing rows to tenant_id = 1 (default tenant).
     * Add composite index (tenant_id, id).
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

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('tenant_id')->nullable()->after('id');

                $blueprint->index(['tenant_id', 'id'], "{$table}_tenant_id_id_index");

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

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign(["{$table}_tenant_id_foreign"]);
                $blueprint->dropIndex("{$table}_tenant_id_id_index");
                $blueprint->dropColumn('tenant_id');
            });
        }
    }
};
