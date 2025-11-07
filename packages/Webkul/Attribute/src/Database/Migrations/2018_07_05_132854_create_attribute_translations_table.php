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
        Schema::create('attribute_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('attribute_id')->unsigned();

            $table->string('locale')->nullable();
            $table->text('name')->nullable();

            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');

            $table->unique(['attribute_id', 'locale']);
            /** Indexes */
            $table->index('attribute_id');
            $table->index(['locale', 'attribute_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attribute_translations');
    }
};
