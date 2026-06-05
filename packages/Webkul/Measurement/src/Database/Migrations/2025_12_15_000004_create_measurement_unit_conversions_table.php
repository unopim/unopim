<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('measurement_unit_id');
            $table->string('value')->nullable();
            $table->string('operator');
            $table->unsignedInteger('position')->default(0);

            $table->foreign('measurement_unit_id', 'muc_unit_foreign')
                ->references('id')
                ->on('measurement_units')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_unit_conversions');
    }
};
