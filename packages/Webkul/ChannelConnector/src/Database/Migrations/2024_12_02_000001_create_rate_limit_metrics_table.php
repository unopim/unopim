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
        Schema::create('rate_limit_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connector_id')->constrained('channel_connectors')->onDelete('cascade');
            $table->string('adapter_type', 50)->index(); // shopify, salla, amazon, etc
            $table->string('endpoint')->nullable(); // API endpoint called
            $table->integer('requests_made')->default(0);
            $table->integer('limit_total')->default(0);
            $table->integer('limit_remaining')->default(0);
            $table->timestamp('reset_at')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->enum('status', ['ok', 'warning', 'critical', 'exceeded'])->default('ok');
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['connector_id', 'recorded_at']);
            $table->index(['adapter_type', 'recorded_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_limit_metrics');
    }
};
