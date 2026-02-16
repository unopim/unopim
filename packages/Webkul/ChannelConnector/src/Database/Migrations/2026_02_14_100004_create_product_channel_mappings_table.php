<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_channel_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('channel_connector_id');
            $table->unsignedBigInteger('product_id');
            $table->string('external_id');
            $table->string('external_variant_id')->nullable();
            $table->string('entity_type', 20)->default('product');
            $table->string('sync_status', 20)->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->string('data_hash', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'id'], 'idx_product_mapping_tenant_id');
            $table->index(['channel_connector_id', 'external_id'], 'idx_product_mapping_connector_external');
            $table->index(['sync_status'], 'idx_product_mapping_status');
            $table->index(['product_id'], 'idx_product_mapping_product');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('channel_connector_id')
                ->references('id')
                ->on('channel_connectors')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        // Tenant-scoped unique: SQLite-compatible raw SQL
        DB::statement('CREATE UNIQUE INDEX idx_product_mapping_unique ON product_channel_mappings (channel_connector_id, product_id, entity_type)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_channel_mappings');
    }
};
