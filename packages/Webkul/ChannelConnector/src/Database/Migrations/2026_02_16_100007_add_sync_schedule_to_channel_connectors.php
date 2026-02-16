<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_connectors', function (Blueprint $table) {
            $table->json('sync_schedule')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('channel_connectors', function (Blueprint $table) {
            $table->dropColumn('sync_schedule');
        });
    }
};
