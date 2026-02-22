<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unified_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('channel_id');
            $table->string('channel_order_id');
            $table->string('channel_order_number')->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'partially_paid', 'refunded'])->default('unpaid');
            $table->enum('fulfillment_status', ['unfulfilled', 'partially_fulfilled', 'fulfilled', 'cancelled'])->default('unfulfilled');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('shipping_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->char('currency_code', 3)->default('USD');
            $table->dateTime('ordered_at');
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'id'], 'idx_unified_orders_tenant_id');
            $table->index(['tenant_id', 'channel_id'], 'idx_unified_orders_tenant_channel');
            $table->index(['tenant_id', 'status'], 'idx_unified_orders_tenant_status');
            $table->index(['tenant_id', 'ordered_at'], 'idx_unified_orders_tenant_ordered');
            $table->index(['channel_order_id'], 'idx_unified_orders_channel_order_id');

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
        DB::statement('CREATE UNIQUE INDEX idx_unified_orders_unique ON unified_orders (tenant_id, channel_id, channel_order_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_orders');
    }
};
