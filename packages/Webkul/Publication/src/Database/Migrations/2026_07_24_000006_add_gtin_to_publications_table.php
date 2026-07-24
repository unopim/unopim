<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table): void {
            // Non-unique: one GTIN maps to many channel publications of the same
            // product. Distinct from alias_identifier, which is the unique full
            // GS1 Digital Link URL per publication.
            $table->string('gtin')->nullable()->after('alias_identifier');

            // Explicit name: auto names include the table prefix and overrun
            // MySQL's 64-char identifier limit on prefixed installs.
            $table->index('gtin', 'pub_gtin_idx');
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table): void {
            $table->dropIndex('pub_gtin_idx');
            $table->dropColumn('gtin');
        });
    }
};
