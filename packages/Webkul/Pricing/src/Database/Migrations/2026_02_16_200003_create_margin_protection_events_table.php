<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('margin_protection_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->enum('event_type', ['blocked', 'warning', 'approved', 'expired']);
            $table->decimal('proposed_price', 12, 4);
            $table->decimal('break_even_price', 12, 4);
            $table->decimal('minimum_margin_price', 12, 4);
            $table->decimal('target_margin_price', 12, 4)->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('margin_percentage', 5, 2);
            $table->decimal('minimum_margin_percentage', 5, 2);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id'], 'idx_mpe_tenant_product');
            $table->index(['tenant_id', 'event_type'], 'idx_mpe_tenant_event_type');
            $table->index(['tenant_id', 'expires_at'], 'idx_mpe_tenant_expires');
            $table->index(['approved_by'], 'idx_mpe_approved_by');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->onDelete('set null');

            $table->foreign('approved_by')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('margin_protection_events');
    }
};
