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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('api_token', 80)->unique()->nullable()->default(null);
            $table->boolean('status')->default(0);
            $table->integer('role_id')->unsigned();
            $table->string('image')->nullable();
            $table->string('timezone', 40)->default('UTC');
            $table->unsignedInteger('ui_locale_id')->nullable()->comment('for ui locale');
            $table->foreign('ui_locale_id')->references('id')->on('locales');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admins');
    }
};
