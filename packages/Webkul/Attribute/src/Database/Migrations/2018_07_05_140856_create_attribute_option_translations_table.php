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
        Schema::create('attribute_option_translations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('attribute_option_id')->unsigned();
            $table->string('locale');
            $table->text('label')->nullable();

            $table->unique(['attribute_option_id', 'locale'], 'attr_opt_translations_opt_id_locale_unique');
            $table->foreign('attribute_option_id')->references('id')->on('attribute_options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_option_translations');
    }
};
