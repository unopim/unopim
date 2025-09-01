<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $driver = DB::getDriverName();

        Schema::create('categories', function (Blueprint $table) use ($driver) {
            $table->id();
            $table->string('code')->unique();
            NestedSet::columns($table);

            switch($driver) {
                case 'pgsql':
                    $table->unsignedBigInteger('parent_id')->nullable()->change();
                    break;
                case 'mysql':
                    $table->unsignedInteger('parent_id')->nullable()->change();
                    break;
            }
            
            $table->timestamps();
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
