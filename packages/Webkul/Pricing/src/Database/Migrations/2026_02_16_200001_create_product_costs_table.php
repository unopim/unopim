<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->enum('cost_type', ['cogs', 'operational', 'marketing', 'platform', 'shipping', 'overhead']);
            $table->decimal('amount', 12, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id'], 'idx_product_costs_tenant_product');
            $table->index(['tenant_id', 'cost_type'], 'idx_product_costs_tenant_type');
            $table->index(['effective_from', 'effective_to'], 'idx_product_costs_effective_range');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');
        });

        // Tenant-scoped unique: SQLite-compatible raw SQL
        DB::statement('CREATE UNIQUE INDEX idx_product_costs_unique ON product_costs (tenant_id, product_id, cost_type, effective_from)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_costs');
    }
};
