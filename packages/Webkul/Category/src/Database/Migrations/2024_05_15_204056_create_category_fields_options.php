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
        Schema::create('category_field_options', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->nullable();
            $table->integer('sort_order')->nullable();
            $table->integer('category_field_id')->unsigned();

            $table->unique(['code', 'category_field_id'], 'unique_code_category_field_id_index');

            $table->foreign('category_field_id')->references('id')->on('category_fields')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_field_options');
    }
};
