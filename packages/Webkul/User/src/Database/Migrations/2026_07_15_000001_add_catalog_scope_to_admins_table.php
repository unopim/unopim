<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * An admin's catalog scope is distinct from `ui_locale_id`: the UI locale is the language the
     * admin panel is rendered in, the catalog locale is the language catalog *content* is authored
     * in. An admin may read the panel in English while authoring a Dutch catalogue, so the two are
     * never conflated. Both columns are nullable — null means "no preference", and the scope
     * resolver falls through to the channel/config default.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->unsignedInteger('catalog_locale_id')->nullable()->after('ui_locale_id')->comment('locale catalog content is authored in');
            $table->unsignedInteger('default_channel_id')->nullable()->after('catalog_locale_id')->comment('channel the admin works in by default');

            $table->foreign('catalog_locale_id')->references('id')->on('locales')->nullOnDelete();
            $table->foreign('default_channel_id')->references('id')->on('channels')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeign(['catalog_locale_id']);
            $table->dropForeign(['default_channel_id']);

            $table->dropColumn(['catalog_locale_id', 'default_channel_id']);
        });
    }
};
