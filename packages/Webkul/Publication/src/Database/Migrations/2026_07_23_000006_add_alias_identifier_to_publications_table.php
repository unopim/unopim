<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('publications', 'alias_identifier')) {
            return;
        }

        Schema::table('publications', function (Blueprint $table): void {
            $table->string('alias_identifier')->nullable()->unique('pub_alias_uq')->after('uuid');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('publications', 'alias_identifier')) {
            return;
        }

        Schema::table('publications', function (Blueprint $table): void {
            $table->dropUnique('pub_alias_uq');
            $table->dropColumn('alias_identifier');
        });
    }
};
