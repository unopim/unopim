<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_view_stats', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('publication_id');
            $table->foreign('publication_id', 'pubvs_pub_fk')->references('id')->on('publications')->cascadeOnDelete();
            $table->index('publication_id', 'pubvs_pub_idx');

            $table->unsignedInteger('locale_id')->nullable();

            $table->date('viewed_on');

            // GDPR-safe daily aggregate: a count only, never one row per visitor and never a raw IP.
            $table->unsignedInteger('views')->default(0);

            $table->timestamps();

            // One row per (publication, locale, day). Explicit short name: auto names include the prefix and overrun MySQL's 64-char identifier limit on prefixed installs.
            $table->unique(['publication_id', 'locale_id', 'viewed_on'], 'pubvs_pub_loc_day_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_view_stats');
    }
};
