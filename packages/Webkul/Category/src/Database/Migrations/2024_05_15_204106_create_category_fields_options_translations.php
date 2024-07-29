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
        Schema::create('category_field_option_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_field_option_id')->unsigned();
            $table->string('locale');
            $table->text('label')->nullable();

            $table->unique(['category_field_option_id', 'locale'], 'fields_options_locale_unique');
            $table->foreign('category_field_option_id', 'fk_category_field_option_translations')->references('id')->on('category_field_options')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_field_option_translations');
    }
};
