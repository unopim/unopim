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
            $table->foreignId('attribute_family_id')->constrained('attribute_families')->cascadeOnDelete();
            $table->foreignId('attribute_group_id')->constrained('attribute_groups')->cascadeOnDelete();

            $table->integer('position')->nullable();
        });

        Schema::create('attribute_group_mappings', function (Blueprint $table) {
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->foreignId('attribute_family_group_id')->constrained('attribute_family_group_mappings')->cascadeOnDelete();

            $table->integer('position')->nullable();

            $table->primary(['attribute_id', 'attribute_family_group_id']);
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
