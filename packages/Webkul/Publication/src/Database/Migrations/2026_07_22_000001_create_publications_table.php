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

            // Retention obligation outlives the catalog record: a product with an
            // attested passport cannot be deleted out from under it.
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();

            // Same rationale: a channel cannot be deleted while it still carries
            // attested passports.
            $table->unsignedInteger('channel_id');
            $table->foreign('channel_id')->references('id')->on('channels')->restrictOnDelete();

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
