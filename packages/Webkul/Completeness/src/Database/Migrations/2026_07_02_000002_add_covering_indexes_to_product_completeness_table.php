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
        Schema::table('product_completeness', function (Blueprint $table): void {
            $table->index(['channel_id', 'product_id'], 'pc_channel_product_idx');
            $table->index(['locale_id', 'product_id'], 'pc_locale_product_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_completeness', function (Blueprint $table): void {
            $table->dropIndex('pc_channel_product_idx');
            $table->dropIndex('pc_locale_product_idx');
        });
    }
};
