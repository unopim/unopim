<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support DROP INDEX + ADD composite well.
            // The tenant_id column was already added by the tenant migration.
            // For SQLite, we create a unique index if it doesn't exist.
            try {
                DB::statement('DROP INDEX IF EXISTS products_sku_unique');
            } catch (\Throwable) {
                // Index may not exist
            }

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS products_tenant_sku_unique ON products (tenant_id, sku)');
        } else {
            Schema::table('products', function (Blueprint $table) {
                // Drop the global unique constraint on sku
                $table->dropUnique(['sku']);
            });

            Schema::table('products', function (Blueprint $table) {
                // Add composite unique: tenant_id + sku
                $table->unique(['tenant_id', 'sku'], 'products_tenant_sku_unique');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            try {
                DB::statement('DROP INDEX IF EXISTS products_tenant_sku_unique');
            } catch (\Throwable) {
                // Ignore
            }

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS products_sku_unique ON products (sku)');
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique('products_tenant_sku_unique');
            });

            Schema::table('products', function (Blueprint $table) {
                $table->unique('sku');
            });
        }
    }
};
