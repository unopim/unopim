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
        if (Schema::hasTable('channels')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->index('code', 'channels_code_idx');
            });
        }

        if (Schema::hasTable('locales')) {
            Schema::table('locales', function (Blueprint $table) {
                $table->index('status', 'locales_status_idx');
            });
        }

        if (Schema::hasTable('currencies')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->index('status', 'currencies_status_idx');
            });
        }

        if (Schema::hasTable('core_config')) {
            Schema::table('core_config', function (Blueprint $table) {
                $table->index(['code', 'channel_code', 'locale_code'], 'core_config_lookup_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('channels')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->dropIndex('channels_code_idx');
            });
        }

        if (Schema::hasTable('locales')) {
            Schema::table('locales', function (Blueprint $table) {
                $table->dropIndex('locales_status_idx');
            });
        }

        if (Schema::hasTable('currencies')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->dropIndex('currencies_status_idx');
            });
        }

        if (Schema::hasTable('core_config')) {
            Schema::table('core_config', function (Blueprint $table) {
                $table->dropIndex('core_config_lookup_idx');
            });
        }
    }
};
