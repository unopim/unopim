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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('root_category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('channel_locales', function (Blueprint $table) {
            $table->foreignId('channel_id')
                ->constrained('channels')
                ->cascadeOnDelete();

            $table->foreignId('locale_id')
                ->constrained('locales')
                ->cascadeOnDelete();

            $table->primary(['channel_id', 'locale_id']);
        });

        Schema::create('channel_currencies', function (Blueprint $table) {
            $table->foreignId('channel_id')
                ->constrained('channels')
                ->cascadeOnDelete();
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete();

            $table->primary(['channel_id', 'currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_currencies');
        Schema::dropIfExists('channel_locales');
        Schema::dropIfExists('channels');
    }
};
