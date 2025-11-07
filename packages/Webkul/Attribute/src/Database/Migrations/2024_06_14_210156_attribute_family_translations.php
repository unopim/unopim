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
        Schema::create('attribute_family_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('attribute_family_id')->unsigned();

            $table->string('locale');
            $table->text('name')->nullable();

            $table->unique(['attribute_family_id', 'locale']);
            $table->foreign('attribute_family_id')->references('id')->on('attribute_families')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_family_translations');
    }
};
