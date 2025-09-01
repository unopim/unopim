<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $driver = DB::getDriverName();

        Schema::create('attribute_option_translations', function (Blueprint $table) use ($driver) {
            $table->id();

            switch ($driver) {
                case 'mysql':
                    $table->unsignedBigInteger('attribute_option_id');
                    break;

                case 'pgsql':
                default:
                    $table->bigInteger('attribute_option_id');
                    break;
            }

            $table->string('locale');
            $table->text('label')->nullable();

            $table->unique(['attribute_option_id', 'locale']);

            $table->foreign('attribute_option_id')
                  ->references('id')
                  ->on('attribute_options')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attribute_option_translations');
    }
};