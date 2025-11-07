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
        Schema::create('completeness_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('family_id');
            $table->foreign('family_id')->references('id')->on('attribute_families')->cascadeOnDelete();

            $table->unsignedInteger('attribute_id');
            $table->foreign('attribute_id')->references('id')->on('attributes')->cascadeOnDelete();

            $table->unsignedInteger('channel_id');
            $table->foreign('channel_id')->references('id')->on('channels')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('completeness_settings');
    }
};
