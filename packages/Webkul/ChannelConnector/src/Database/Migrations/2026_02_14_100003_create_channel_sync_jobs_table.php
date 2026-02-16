<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('channel_connector_id');
            $table->string('job_id', 36);
            $table->string('status', 20)->default('pending');
            $table->string('sync_type', 20);
            $table->integer('total_products')->default(0);
            $table->integer('synced_products')->default(0);
            $table->integer('failed_products')->default(0);
            $table->json('error_summary')->nullable();
            $table->unsignedBigInteger('retry_of_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'id'], 'idx_sync_job_tenant_id');
            $table->index(['tenant_id', 'status'], 'idx_sync_job_tenant_status');
            $table->index(['channel_connector_id', 'created_at'], 'idx_sync_job_connector_created');
            $table->index(['retry_of_id'], 'idx_sync_job_retry');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('channel_connector_id')
                ->references('id')
                ->on('channel_connectors')
                ->onDelete('cascade');

            $table->foreign('retry_of_id')
                ->references('id')
                ->on('channel_sync_jobs')
                ->onDelete('set null');
        });

        // Tenant-scoped unique: SQLite-compatible raw SQL
        DB::statement('CREATE UNIQUE INDEX idx_sync_job_tenant_job_id ON channel_sync_jobs (tenant_id, job_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_sync_jobs');
    }
};
