<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_strategies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->enum('scope_type', ['global', 'category', 'channel', 'product'])->default('global');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->decimal('minimum_margin_percentage', 5, 2)->default(15.00);
            $table->decimal('target_margin_percentage', 5, 2)->default(25.00);
            $table->decimal('premium_margin_percentage', 5, 2)->default(40.00);
            $table->boolean('psychological_pricing')->default(true);
            $table->enum('round_to', ['0.99', '0.95', '0.00', 'none'])->default('0.99');
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('priority')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active'], 'idx_pricing_strategies_tenant_active');
            $table->index(['priority'], 'idx_pricing_strategies_priority');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });

        // Tenant-scoped unique: SQLite-compatible raw SQL
        DB::statement('CREATE UNIQUE INDEX idx_pricing_strategies_unique ON pricing_strategies (tenant_id, scope_type, scope_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_strategies');
    }
};
