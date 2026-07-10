<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('association_type_field_option_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('association_type_field_option_id')->unsigned();
            $table->string('locale');
            $table->text('label')->nullable();

            $table->unique(['association_type_field_option_id', 'locale'], 'fields_options_locale_unique');
            $table->foreign('association_type_field_option_id', DB::getTablePrefix().'fk_assoc_field_opt_translations')->references('id')->on('association_type_field_options')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('association_type_field_option_translations');
    }
};
