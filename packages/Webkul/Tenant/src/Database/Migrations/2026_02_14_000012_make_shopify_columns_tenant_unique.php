<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert global UNIQUE constraint on `shopUrl` to composite
 * (tenant_id, shopUrl) so different tenants can connect the same
 * Shopify store independently.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = 'wk_shopify_credentials_config';

        if (! Schema::hasColumn($table, 'tenant_id')) {
            return;
        }

        $driver = DB::getDriverName();
        $globalIndex = "{$table}_shopurl_unique";
        $compositeIndex = "{$table}_tenant_shopurl_unique";

        if ($driver === 'sqlite') {
            try {
                DB::statement("DROP INDEX IF EXISTS {$globalIndex}");
            } catch (\Throwable) {
                // Index may not exist or have a different name
            }

            DB::statement(
                "CREATE UNIQUE INDEX IF NOT EXISTS {$compositeIndex} ON {$table} (tenant_id, shopUrl)"
            );
        } else {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropUnique(['shopUrl']);
            });

            Schema::table($table, function (Blueprint $blueprint) use ($compositeIndex) {
                $blueprint->unique(['tenant_id', 'shopUrl'], $compositeIndex);
            });
        }
    }

    public function down(): void
    {
        $table = 'wk_shopify_credentials_config';

        if (! Schema::hasColumn($table, 'tenant_id')) {
            return;
        }

        $driver = DB::getDriverName();
        $globalIndex = "{$table}_shopurl_unique";
        $compositeIndex = "{$table}_tenant_shopurl_unique";

        if ($driver === 'sqlite') {
            try {
                DB::statement("DROP INDEX IF EXISTS {$compositeIndex}");
            } catch (\Throwable) {
                // Ignore
            }

            DB::statement(
                "CREATE UNIQUE INDEX IF NOT EXISTS {$globalIndex} ON {$table} (shopUrl)"
            );
        } else {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($compositeIndex) {
                    $blueprint->dropUnique($compositeIndex);
                });
            } catch (\Throwable) {
                // Index may not exist or have a different name
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unique('shopUrl');
            });
        }
    }
};
