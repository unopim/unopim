<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Database\Schema\PrefixedIndexNameBlueprint;

/**
 * MySQL and MariaDB already index the column through its foreign key; PostgreSQL
 * does not, so the in-use check on a variant structure would scan the products table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('products', ['variant_structure_id'])) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->index('variant_structure_id', 'products_variant_structure_id_idx');
        });
    }

    public function down(): void
    {
        if (! PrefixedIndexNameBlueprint::indexExists('products', 'products_variant_structure_id_idx')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_variant_structure_id_idx');
        });
    }
};
