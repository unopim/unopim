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
        Schema::create('category_field_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_field_id')->unsigned();
            $table->string('locale');
            $table->text('name')->nullable();

            $table->unique(['category_field_id', 'locale']);
            $table->foreign('category_field_id')->references('id')->on('category_fields')->onDelete('cascade');

            $table->index('category_field_id');
            $table->index(['locale', 'category_field_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_field_translations');
    }
};
