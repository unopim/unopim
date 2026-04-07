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
        Schema::create('magic_ai_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('provider', 50);
            $table->string('api_url', 500)->nullable();
            $table->text('api_key')->nullable();
            $table->text('models');
            $table->json('extras')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_ai_platforms');
    }
};
