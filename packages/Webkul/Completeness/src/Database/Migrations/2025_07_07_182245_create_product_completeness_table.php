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
        Schema::create('product_completeness', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();

            $table->unsignedInteger('channel_id');
            $table->foreign('channel_id')->references('id')->on('channels')->cascadeOnDelete();

            $table->unsignedInteger('locale_id');
            $table->foreign('locale_id')->references('id')->on('locales')->cascadeOnDelete();

            $table->unsignedInteger('score');

            $table->unsignedInteger('missing_count');

            $table->unique(['product_id', 'channel_id', 'locale_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_completeness');
    }
};
