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

            // Nullable and unused today: reserved so a future GS1/other identifier
            // scheme can be attached to an existing passport without a
            // multi-terabyte ALTER once this table is at production scale.
            $table->string('alias_identifier')->nullable()->unique();

            // Retention obligation outlives the catalog record: a product with an
            // attested passport cannot be deleted out from under it.
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();

            // Same rationale: a channel cannot be deleted while it still carries
            // attested passports. Declared explicitly (not just left to the FK)
            // because MySQL auto-indexes FK columns but PostgreSQL does not.
            $table->unsignedInteger('channel_id');
            $table->foreign('channel_id')->references('id')->on('channels')->restrictOnDelete();
            $table->index('channel_id');

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
