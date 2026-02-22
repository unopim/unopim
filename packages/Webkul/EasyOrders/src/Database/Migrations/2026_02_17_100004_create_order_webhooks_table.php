<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('channel_id');
            $table->string('webhook_url');
            $table->enum('event_type', ['order.created', 'order.updated', 'order.cancelled', 'order.fulfilled'])->default('order.created');
            $table->boolean('is_active')->default(true);
            $table->string('secret_key')->nullable();
            $table->dateTime('last_triggered_at')->nullable();
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'id'], 'idx_order_webhooks_tenant_id');
            $table->index(['tenant_id', 'channel_id'], 'idx_order_webhooks_tenant_channel');
            $table->index(['tenant_id', 'is_active'], 'idx_order_webhooks_tenant_active');
            $table->index(['tenant_id', 'event_type'], 'idx_order_webhooks_tenant_event');
            $table->index(['channel_id'], 'idx_order_webhooks_channel_id');
            $table->index(['is_active'], 'idx_order_webhooks_active');

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('channel_id')
                ->references('id')
                ->on('channel_connectors')
                ->onDelete('cascade');
        });

        // Tenant-scoped unique: SQLite-compatible raw SQL
        DB::statement('CREATE UNIQUE INDEX idx_order_webhooks_unique ON order_webhooks (tenant_id, channel_id, webhook_url)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_webhooks');
    }
};
