<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('channel_id');
            $table->decimal('commission_percentage', 5, 2)->default(0);
            $table->decimal('fixed_fee_per_order', 10, 2)->default(0);
            $table->decimal('payment_processing_percentage', 5, 2)->default(0);
            $table->decimal('payment_fixed_fee', 10, 2)->default(0);
            $table->json('shipping_cost_per_zone')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'channel_id'], 'idx_channel_costs_tenant_channel');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->onDelete('cascade');
        });

        // Tenant-scoped unique: SQLite-compatible raw SQL
        DB::statement('CREATE UNIQUE INDEX idx_channel_costs_unique ON channel_costs (tenant_id, channel_id, effective_from)');
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_costs');
    }
};
