<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table): void {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();

            $table->unsignedInteger('channel_id');
            $table->foreign('channel_id')->references('id')->on('channels')->cascadeOnDelete();

            $table->string('type');

            $table->string('status')->default('draft');

            $table->timestamps();

            $table->unique(['product_id', 'channel_id', 'type']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
