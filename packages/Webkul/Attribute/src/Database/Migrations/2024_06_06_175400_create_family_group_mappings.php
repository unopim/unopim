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
        Schema::create('attribute_family_group_mappings', function (Blueprint $table) {
            $table->id();
            $table->integer('attribute_family_id')->unsigned();
            $table->integer('attribute_group_id')->unsigned();
            $table->integer('position')->nullable();

            $table->foreign('attribute_family_id')->references('id')->on('attribute_families')->onDelete('cascade');
            $table->foreign('attribute_group_id')->references('id')->on('attribute_groups')->onDelete('cascade');
        });

        Schema::create('attribute_group_mappings', function (Blueprint $table) {
            $table->integer('attribute_id')->unsigned();
            $table->integer('attribute_family_group_id')->unsigned();
            $table->integer('position')->nullable();

            $table->primary(['attribute_id', 'attribute_family_group_id']);
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
            $table->foreign('attribute_family_group_id')->references('id')->on('attribute_family_group_mappings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_group_mappings');

        Schema::dropIfExists('attribute_family_group_mappings');
    }
};
