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
        Schema::create('attribute_options', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('attribute_id')->unsigned();
            $table->string('code');
            $table->integer('sort_order')->nullable();
            $table->string('swatch_value')->nullable();

            $table->unique(['code', 'attribute_id'], 'unique_code_attribute_id_index');

            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_options');
    }
};
