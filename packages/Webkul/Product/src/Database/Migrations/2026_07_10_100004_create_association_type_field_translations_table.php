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
        Schema::create('association_type_field_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('association_type_field_id')->unsigned();
            $table->string('locale');
            $table->text('name')->nullable();

            $table->unique(['association_type_field_id', 'locale'], 'assoc_field_translations_field_id_locale_unique');
            $table->foreign('association_type_field_id', 'assoc_field_translations_field_id_foreign')->references('id')->on('association_type_fields')->onDelete('cascade');

            $table->index('association_type_field_id', 'assoc_field_translations_field_id_index');
            $table->index(['locale', 'association_type_field_id'], 'assoc_field_translations_locale_field_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('association_type_field_translations');
    }
};
