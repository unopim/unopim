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

            $table->foreignId('family_id')->constrained('attribute_families')->cascadeOnDelete();

            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();

            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();

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
