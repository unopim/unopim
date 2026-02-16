<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('channel_connector_id');
            $table->unsignedBigInteger('channel_sync_job_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('conflict_type', 30);
            $table->json('conflicting_fields');
            $table->timestamp('pim_modified_at')->nullable();
            $table->timestamp('channel_modified_at')->nullable();
            $table->string('resolution_status', 20)->default('pending');
            $table->json('resolution_details')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'id'], 'idx_conflict_tenant_id');
            $table->index(['tenant_id', 'resolution_status'], 'idx_conflict_tenant_status');
            $table->index(['channel_connector_id'], 'idx_conflict_connector');
            $table->index(['channel_sync_job_id'], 'idx_conflict_job');
            $table->index(['product_id'], 'idx_conflict_product');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('channel_connector_id')
                ->references('id')
                ->on('channel_connectors')
                ->onDelete('cascade');

            $table->foreign('channel_sync_job_id')
                ->references('id')
                ->on('channel_sync_jobs')
                ->nullOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

            $table->foreign('resolved_by')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_sync_conflicts');
    }
};
