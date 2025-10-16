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

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();

            $table->foreignId('locale_id')->constrained('locales')->cascadeOnDelete();

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
