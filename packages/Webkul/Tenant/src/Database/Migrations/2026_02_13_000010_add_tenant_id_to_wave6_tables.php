<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wave 6 Auth/Pivot tables that receive tenant_id.
     *
     * - oauth_access_tokens (C7)  – Passport access tokens
     * - oauth_refresh_tokens (C8) – Passport refresh tokens
     * - admin_password_resets (C12) – Password reset tokens
     * - attribute_family_group_mappings (C9) – Family-to-group pivot
     * - attribute_group_mappings (H17) – Group-to-attribute pivot
     */
    private array $tables = [
        'oauth_access_tokens',
        'oauth_refresh_tokens',
        'admin_password_resets',
        'attribute_family_group_mappings',
        'attribute_group_mappings',
    ];

    /**
     * Run the migrations.
     *
     * Add tenant_id to Wave 6 Auth/Pivot Tables.
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
