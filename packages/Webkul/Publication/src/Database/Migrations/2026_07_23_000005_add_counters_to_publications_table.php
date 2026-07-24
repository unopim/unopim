<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table): void {
            // Denormalised, not attested content — maintained by
            // SyncPublicationCounters after every publish.
            $table->unsignedInteger('live_locale_count')->default(0);
            $table->dateTime('last_published_at')->nullable();

            // Explicit name: auto names include the table prefix and overrun
            // MySQL's 64-char identifier limit on prefixed installs.
            $table->index(['type', 'last_published_at'], 'pub_type_pubat_idx');
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table): void {
            $table->dropIndex('pub_type_pubat_idx');
            $table->dropColumn(['live_locale_count', 'last_published_at']);
        });
    }
};
