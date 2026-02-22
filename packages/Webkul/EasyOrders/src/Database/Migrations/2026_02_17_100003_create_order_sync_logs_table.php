<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('channel_id');
            $table->enum('sync_type', ['manual', 'webhook', 'scheduled'])->default('manual');
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->integer('orders_synced')->default(0);
            $table->integer('orders_failed')->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'id'], 'idx_order_sync_logs_tenant_id');
            $table->index(['tenant_id', 'channel_id'], 'idx_order_sync_logs_tenant_channel');
            $table->index(['tenant_id', 'status'], 'idx_order_sync_logs_tenant_status');
            $table->index(['tenant_id', 'started_at'], 'idx_order_sync_logs_tenant_started');
            $table->index(['channel_id'], 'idx_order_sync_logs_channel_id');
            $table->index(['status'], 'idx_order_sync_logs_status');

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
    }

    public function down(): void
    {
        Schema::dropIfExists('order_sync_logs');
    }
};
