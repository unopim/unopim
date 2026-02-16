<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('channel_connector_id');
            $table->string('webhook_event_id', 255)->nullable();
            $table->string('event_type', 100);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'id'], 'idx_webhook_tenant_id');
            $table->index(['tenant_id', 'channel_connector_id'], 'idx_webhook_tenant_connector');
            $table->unique(['tenant_id', 'channel_connector_id', 'webhook_event_id'], 'idx_webhook_unique_event');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('channel_connector_id')
                ->references('id')
                ->on('channel_connectors')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_webhook_events');
    }
};
