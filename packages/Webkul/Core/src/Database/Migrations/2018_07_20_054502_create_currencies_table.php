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
                Schema::create('currencies', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('code');
                    $table->string('symbol')->nullable();
                    $table->boolean('status')->default(0);
                    $table->integer('decimal')->unsigned()->default(2);
                    $table->timestamps();
                });
                break;

            case 'pgsql':
                Schema::create('currencies', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('code');
                    $table->string('symbol')->nullable();
                    $table->boolean('status')->default(0);
                    $table->integer('decimal')->default(2);
                    $table->timestamps();
                });
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('currencies');
    }
};
