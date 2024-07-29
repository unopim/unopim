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
        Schema::create('attribute_group_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('attribute_group_id')->unsigned();
            $table->string('locale');
            $table->text('name')->nullable();

            $table->unique(['attribute_group_id', 'locale']);
            $table->foreign('attribute_group_id')->references('id')->on('attribute_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_group_translations');
    }
};
