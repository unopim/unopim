<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $driver = DB::getDriverName();

        Schema::create('products', function (Blueprint $table) use ($driver) {
            $table->id();
            $table->string('sku')->unique();

            if ($driver === 'pgsql') {
                $table->string('type')->default('simple');
            } else {
                $table->string('type');
            }

            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('attribute_family_id')->nullable();

            if ($driver === 'pgsql') {
                $table->jsonb('values')->nullable();
                $table->jsonb('additional')->nullable();
            } else {
                $table->json('values')->nullable();
                $table->json('additional')->nullable();
            }

            $table->timestamps();

            $table->foreign('attribute_family_id')
                ->references('id')
                ->on('attribute_families')
                ->onDelete('restrict');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('sku');
            $table->index('type');
            $table->index('attribute_family_id');
            $table->index('parent_id');
            $table->index(['attribute_family_id', 'parent_id'], 'attribute_family_parent_idx');
        });

        Schema::create('product_relations', function (Blueprint $table) {
            $table->unsignedInteger('parent_id');
            $table->unsignedInteger('child_id');

            $table->foreign('parent_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('products')->onDelete('cascade');

            $table->unique(['parent_id', 'child_id']);
        });

        Schema::create('product_super_attributes', function (Blueprint $table) {
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('attribute_id');

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('restrict');

            $table->unique(['product_id', 'attribute_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('product_super_attributes');
        Schema::dropIfExists('product_relations');
        Schema::dropIfExists('products');
    }
};
