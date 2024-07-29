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
        Schema::create('history', function (Blueprint $table) {
            $table->increments('id');
            $table->string('entity_type');
            $table->integer('entity_id');
            $table->integer('version_id');
            $table->json('snapshot');
            $table->json('old_value');
            $table->json('new_value');
            $table->integer('user_id')->unsigned();

            $table->foreign('user_id')->references('id')->on('admins');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history');
    }
};
