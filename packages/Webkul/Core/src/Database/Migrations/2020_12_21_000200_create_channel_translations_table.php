<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'mysql':
                Schema::create('channel_translations', function (Blueprint $table) {
                    $table->id();
                    $table->integer('channel_id')->unsigned();
                    $table->string('locale')->index();
                    $table->string('name');
                    $table->timestamps();

                    $table->unique(['channel_id', 'locale']);
                    $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
                });
                break;

            case 'pgsql':
                Schema::create('channel_translations', function (Blueprint $table) {
                    $table->bigIncrements('id'); 
                    $table->integer('channel_id');
                    $table->string('locale')->index();
                    $table->string('name');
                    $table->timestamps();

                    $table->unique(['channel_id', 'locale']);
                    $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
                });
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('channel_translations');
    }
};
