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
        Schema::create('salla_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('connector_id')->constrained('channel_connectors')->onDelete('cascade');
            $table->string('external_id')->nullable();
            $table->string('external_sku')->nullable();
            $table->string('external_parent_id')->nullable();
            $table->json('variant_data')->nullable();
            $table->string('sync_status')->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'connector_id'], 'salla_product_connector_unique');
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salla_product_mappings');
    }
};
