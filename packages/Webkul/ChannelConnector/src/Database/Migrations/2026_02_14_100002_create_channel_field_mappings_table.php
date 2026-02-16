<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('channel_connector_id');
            $table->string('unopim_attribute_code');
            $table->string('channel_field');
            $table->string('direction', 10)->default('export');
            $table->json('transformation')->nullable();
            $table->json('locale_mapping')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'id'], 'idx_field_mapping_tenant_id');
            $table->index(['channel_connector_id'], 'idx_field_mapping_connector');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('channel_connector_id')
                ->references('id')
                ->on('channel_connectors')
                ->onDelete('cascade');
        });

        // Tenant-scoped unique: SQLite-compatible raw SQL
        DB::statement('CREATE UNIQUE INDEX idx_field_mapping_unique ON channel_field_mappings (channel_connector_id, unopim_attribute_code, channel_field)');
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_field_mappings');
    }
};
