<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attribute_column_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('attribute_column_id')->unsigned();
            $table->string('locale');
            $table->text('label')->nullable();

            $table->unique(['attribute_column_id', 'locale']);
            $table->foreign('attribute_column_id')->references('id')->on('attribute_columns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attribute_column_translations');
    }
};
