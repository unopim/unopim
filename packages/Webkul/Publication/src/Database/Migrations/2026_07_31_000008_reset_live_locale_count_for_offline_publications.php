<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\Publication\Enums\PublicationStatus;

/**
 * `live_locale_count` used to be recomputed on publish alone, so a passport
 * withdrawn or redacted after publishing kept counting locales the public can
 * no longer reach. The counter is derived, so a single set-based reset is
 * enough — the listener maintains it from here on.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('publications')
            ->where('status', '!=', PublicationStatus::Published->value)
            ->where('live_locale_count', '>', 0)
            ->update(['live_locale_count' => 0]);
    }

    public function down(): void {}
};
