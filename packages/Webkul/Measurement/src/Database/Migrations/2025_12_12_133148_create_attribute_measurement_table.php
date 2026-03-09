<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_measurement', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('attribute_id');
            $table->string('family_code');
            $table->string('unit_code');
            $table->timestamps();

            $table->foreign('attribute_id')
                ->references('id')
                ->on('attributes')
                ->onDelete('cascade');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_measurement');
    }
};
