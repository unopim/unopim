<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert global UNIQUE constraints on `code`/`email` columns to
 * composite (tenant_id, code) constraints on tenant-scoped tables.
 *
 * Without this, two tenants cannot share the same locale code (en_US),
 * category code (root), attribute code (sku), etc.
 *
 * Follows the same pattern established in migration 000005 for products.sku.
 */
return new class extends Migration
{
    /**
     * Tables to migrate: [table => column].
     */
    private array $tables = [
        'categories'     => 'code',
        'locales'        => 'code',
        'attributes'     => 'code',
        'category_fields' => 'code',
        'admins'         => 'email',
        'job_instances'  => 'code',
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();

        foreach ($this->tables as $table => $column) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $globalIndex = "{$table}_{$column}_unique";
            $compositeIndex = "{$table}_tenant_{$column}_unique";

            if ($driver === 'sqlite') {
                try {
                    DB::statement("DROP INDEX IF EXISTS {$globalIndex}");
                } catch (\Throwable) {
                    // Index may not exist or have a different name
                }

                DB::statement(
                    "CREATE UNIQUE INDEX IF NOT EXISTS {$compositeIndex} ON {$table} (tenant_id, {$column})"
                );
            } else {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropUnique([$column]);
                });

                Schema::table($table, function (Blueprint $blueprint) use ($table, $column, $compositeIndex) {
                    $blueprint->unique(['tenant_id', $column], $compositeIndex);
                });
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        foreach ($this->tables as $table => $column) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $globalIndex = "{$table}_{$column}_unique";
            $compositeIndex = "{$table}_tenant_{$column}_unique";

            if ($driver === 'sqlite') {
                try {
                    DB::statement("DROP INDEX IF EXISTS {$compositeIndex}");
                } catch (\Throwable) {
                    // Ignore
                }

                DB::statement(
                    "CREATE UNIQUE INDEX IF NOT EXISTS {$globalIndex} ON {$table} ({$column})"
                );
            } else {
                Schema::table($table, function (Blueprint $blueprint) use ($compositeIndex) {
                    $blueprint->dropUnique($compositeIndex);
                });

                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->unique($column);
                });
            }
        }
    }
};
