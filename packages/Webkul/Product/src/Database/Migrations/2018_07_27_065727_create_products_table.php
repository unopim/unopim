<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sku')->unique();
            $table->string('type');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('attribute_family_id')->unsigned()->nullable();

            $table->json('values')->nullable();
            $table->json('additional')->nullable();

            $table->timestamps();

            $table->foreign('attribute_family_id')->references('id')->on('attribute_families')->onDelete('restrict');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('products')->onDelete('cascade');
            /** Indexes */
            $table->index('sku');
            $table->index('type');
            $table->index('attribute_family_id');
            $table->index('parent_id');
            $table->index(['attribute_family_id', 'parent_id'], 'attribute_family_parent_idx');
        });

        Schema::create('product_relations', function (Blueprint $table) {
            $table->integer('parent_id')->unsigned();
            $table->integer('child_id')->unsigned();

            $table->foreign('parent_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('products')->onDelete('cascade');

            $table->unique(['parent_id', 'child_id']);
        });

        Schema::create('product_super_attributes', function (Blueprint $table) {
            $table->integer('product_id')->unsigned();
            $table->integer('attribute_id')->unsigned();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('restrict');

            $table->unique(['product_id', 'attribute_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_super_attributes');

        Schema::dropIfExists('product_relations');

        Schema::dropIfExists('products');
    }
};
