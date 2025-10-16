<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('type');

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('attribute_family_id')
                ->nullable()
                ->constrained('attribute_families');

            $table->json('values')->nullable();
            $table->json('additional')->nullable();

            $table->timestamps();

            /** Indexes */
            $table->index('sku');
            $table->index('type');
            $table->index('attribute_family_id');
            $table->index('parent_id');
            $table->index(['attribute_family_id', 'parent_id'], 'attribute_family_parent_idx');
        });

        Schema::create('product_relations', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('child_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->unique(['parent_id', 'child_id']);
        });

        Schema::create('product_super_attributes', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('attribute_id')
                ->constrained('attributes');

            $table->unique(['product_id', 'attribute_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_super_attributes');
        Schema::dropIfExists('product_relations');
        Schema::dropIfExists('products');
    }
};
