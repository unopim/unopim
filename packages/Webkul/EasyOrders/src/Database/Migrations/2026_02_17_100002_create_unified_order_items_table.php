<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unified_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('unified_order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('channel_product_id')->nullable();
            $table->string('sku');
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('cost_basis', 15, 4)->nullable();
            $table->decimal('profit_amount', 15, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'unified_order_id'], 'idx_order_items_tenant_order');
            $table->index(['tenant_id', 'product_id'], 'idx_order_items_tenant_product');
            $table->index(['tenant_id', 'sku'], 'idx_order_items_tenant_sku');
            $table->index(['unified_order_id'], 'idx_order_items_order_id');
            $table->index(['product_id'], 'idx_order_items_product_id');

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('unified_order_id')
                ->references('id')
                ->on('unified_orders')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_order_items');
    }
};
