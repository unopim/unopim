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
        Schema::create('association_type_field_options', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->nullable();
            $table->integer('sort_order')->nullable();
            $table->integer('association_type_field_id')->unsigned();

            $table->unique(['code', 'association_type_field_id'], 'unique_code_assoc_field_id');

            $table->foreign('association_type_field_id', 'assoc_field_options_field_id_foreign')->references('id')->on('association_type_fields')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('association_type_field_options');
    }
};
