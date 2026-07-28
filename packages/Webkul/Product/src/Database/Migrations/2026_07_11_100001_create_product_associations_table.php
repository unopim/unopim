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
        Schema::create('product_associations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->unsigned();
            $table->integer('association_type_id')->unsigned();
            $table->integer('related_product_id')->unsigned();
            $table->integer('position')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->unique(
                ['product_id', 'association_type_id', 'related_product_id'],
                'product_assoc_unique_link'
            );
            $table->index(['product_id', 'association_type_id'], 'product_assoc_product_type_index');

            $table->foreign('product_id', 'product_assoc_product_fk')
                ->references('id')->on('products')->onDelete('cascade');
            $table->foreign('related_product_id', 'product_assoc_related_fk')
                ->references('id')->on('products')->onDelete('cascade');
            $table->foreign('association_type_id', 'product_assoc_type_fk')
                ->references('id')->on('association_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_associations');
    }
};
