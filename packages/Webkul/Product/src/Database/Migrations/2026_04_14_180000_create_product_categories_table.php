<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot table linking products to categories many-to-many.
 *
 * Before this migration UnoPim had no direct product↔category pivot —
 * categories were effectively a navigation tree attached to a channel
 * via channels.root_category_id, with no way to assign a product to a
 * category. This made demo data (and real catalogues) unable to
 * answer "which products are in the Dairy category?"
 *
 * This migration adds the pivot as a minimal, optional addition. If
 * the table already exists (e.g. upgraded install already has it)
 * the migration is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_categories')) {
            return;
        }

        Schema::create('product_categories', function (Blueprint $table) {
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('category_id');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');

            $table->unique(['product_id', 'category_id']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
