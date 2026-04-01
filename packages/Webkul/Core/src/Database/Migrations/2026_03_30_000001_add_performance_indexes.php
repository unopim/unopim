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
        Schema::table('wk_channels', function (Blueprint $table) {
            $table->index('code', 'wk_channels_code_idx');
        });

        Schema::table('wk_locales', function (Blueprint $table) {
            $table->index('status', 'wk_locales_status_idx');
        });

        Schema::table('wk_currencies', function (Blueprint $table) {
            $table->index('status', 'wk_currencies_status_idx');
        });

        Schema::table('wk_core_config', function (Blueprint $table) {
            $table->index(['code', 'channel_code', 'locale_code'], 'wk_core_config_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wk_channels', function (Blueprint $table) {
            $table->dropIndex('wk_channels_code_idx');
        });

        Schema::table('wk_locales', function (Blueprint $table) {
            $table->dropIndex('wk_locales_status_idx');
        });

        Schema::table('wk_currencies', function (Blueprint $table) {
            $table->dropIndex('wk_currencies_status_idx');
        });

        Schema::table('wk_core_config', function (Blueprint $table) {
            $table->dropIndex('wk_core_config_lookup_idx');
        });
    }
};
