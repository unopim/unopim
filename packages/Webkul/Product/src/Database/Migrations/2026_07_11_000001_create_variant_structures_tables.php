<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_structures', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('attribute_family_id');
            $table->string('code');
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('levels')->default(1);
            $table->timestamps();

            $table->unique(['attribute_family_id', 'code'], 'vs_family_code_unique');
            $table->foreign('attribute_family_id')->references('id')->on('attribute_families')->onDelete('cascade');
        });

        Schema::create('variant_structure_axes', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('variant_structure_id');
            $table->unsignedInteger('attribute_id');
            $table->enum('level', ['level_1', 'level_2'])->default('level_1');
            $table->unsignedTinyInteger('position')->default(0);

            $table->unique(['variant_structure_id', 'level', 'position'], 'vsax_structure_level_position_unique');
            $table->foreign('variant_structure_id')->references('id')->on('variant_structures')->onDelete('cascade');
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
        });

        Schema::create('variant_structure_attributes', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('variant_structure_id');
            $table->unsignedInteger('attribute_id');
            $table->enum('level', ['common', 'sub_parent', 'variant'])->default('common');

            $table->unique(['variant_structure_id', 'attribute_id'], 'vsa_structure_attribute_unique');
            $table->foreign('variant_structure_id')->references('id')->on('variant_structures')->onDelete('cascade');
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_structure_attributes');
        Schema::dropIfExists('variant_structure_axes');
        Schema::dropIfExists('variant_structures');
    }
};
