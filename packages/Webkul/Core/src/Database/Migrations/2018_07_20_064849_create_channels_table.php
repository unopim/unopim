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
                Schema::create('channels', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('code');
                    $table->integer('root_category_id')->nullable()->unsigned();
                    $table->timestamps();

                    $table->foreign('root_category_id')->references('id')->on('categories')->onDelete('set null');
                });

                Schema::create('channel_locales', function (Blueprint $table) {
                    $table->integer('channel_id')->unsigned();
                    $table->integer('locale_id')->unsigned();

                    $table->primary(['channel_id', 'locale_id']);
                    $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
                    $table->foreign('locale_id')->references('id')->on('locales')->onDelete('cascade');
                });

                Schema::create('channel_currencies', function (Blueprint $table) {
                    $table->integer('channel_id')->unsigned();
                    $table->integer('currency_id')->unsigned();

                    $table->primary(['channel_id', 'currency_id']);
                    $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
                    $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
                });
                break;

            case 'pgsql':
                Schema::create('channels', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('code');
                    $table->integer('root_category_id')->nullable();
                    $table->timestamps();

                    $table->foreign('root_category_id')->references('id')->on('categories')->onDelete('set null');
                });

                Schema::create('channel_locales', function (Blueprint $table) {
                    $table->integer('channel_id');
                    $table->integer('locale_id');

                    $table->primary(['channel_id', 'locale_id']);
                    $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
                    $table->foreign('locale_id')->references('id')->on('locales')->onDelete('cascade');
                });

                Schema::create('channel_currencies', function (Blueprint $table) {
                    $table->integer('channel_id');
                    $table->integer('currency_id');

                    $table->primary(['channel_id', 'currency_id']);
                    $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
                    $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
                });
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('channel_currencies');
        Schema::dropIfExists('channel_locales');
        Schema::dropIfExists('channels');
    }
};
