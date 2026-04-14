<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the minimal product-association tables the UnoPim demo catalogs
 * and real B2B catalogs need:
 *
 *  - `association_types` — registry of association codes (upsell,
 *    crosssell, substitution, case_contains, pack_size_variant, …).
 *  - `product_associations` — the directed link between two products
 *    with a type reference and an optional quantity qualifier (used
 *    by `case_contains` to say "this case of 24 contains 24 consumer
 *    units").
 *
 * Both tables are optional additions — if they already exist (e.g. an
 * upgrade install already has them) the migration is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('association_types')) {
            Schema::create('association_types', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_associations')) {
            Schema::create('product_associations', function (Blueprint $table) {
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('linked_product_id');
                $table->unsignedInteger('association_type_id');
                $table->unsignedInteger('quantity')->nullable();
                $table->timestamps();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->onDelete('cascade');

                $table->foreign('linked_product_id')
                    ->references('id')
                    ->on('products')
                    ->onDelete('cascade');

                $table->foreign('association_type_id')
                    ->references('id')
                    ->on('association_types')
                    ->onDelete('cascade');

                $table->unique(
                    ['product_id', 'linked_product_id', 'association_type_id'],
                    'product_association_unique_triple'
                );
                $table->index('linked_product_id');
                $table->index('association_type_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_associations');
        Schema::dropIfExists('association_types');
    }
};
