<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_connectors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('code');
            $table->string('name');
            $table->string('channel_type', 50);
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->string('status', 20)->default('disconnected');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'id'], 'idx_connector_tenant_id');
            $table->index(['tenant_id', 'channel_type'], 'idx_connector_tenant_type');
            $table->index(['status'], 'idx_connector_status');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });

        // Tenant-scoped unique: SQLite-compatible raw SQL
        DB::statement('CREATE UNIQUE INDEX idx_connector_tenant_code ON channel_connectors (tenant_id, code)');
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_connectors');
    }
};
