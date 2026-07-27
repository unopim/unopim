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

            $table->uuid('uuid')->unique('pub_uuid_uq');

            // Reserved for a future GS1/other identifier without a later ALTER.
            $table->string('alias_identifier')->nullable()->unique('pub_alias_uq');

            // restrictOnDelete: an attested passport outlives its catalog record.
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();

            $table->unsignedInteger('channel_id');
            $table->foreign('channel_id')->references('id')->on('channels')->restrictOnDelete();
            $table->index('channel_id', 'pub_channel_idx');

            $table->string('type');

            $table->string('status')->default('draft');

            $table->timestamps();

            // Explicit names: auto names include the table prefix and overrun
            // MySQL's 64-char identifier limit on prefixed installs.
            $table->unique(['product_id', 'channel_id', 'type'], 'pub_prod_channel_type_uq');
            $table->index(['type', 'status'], 'pub_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
