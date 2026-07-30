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
    /**
     * MySQL drops each foreign key's own index once these cover it, leaving them as the only index
     * serving that key, so the keys have to be detached before the indexes can go.
     */
    public function down(): void
    {
        Schema::table('product_completeness', function (Blueprint $table): void {
            $table->dropForeign(['channel_id']);
            $table->dropForeign(['locale_id']);
            $table->dropIndex('pc_channel_product_idx');
            $table->dropIndex('pc_locale_product_idx');
            $table->foreign('channel_id')->references('id')->on('channels')->cascadeOnDelete();
            $table->foreign('locale_id')->references('id')->on('locales')->cascadeOnDelete();
        });
    }
};
