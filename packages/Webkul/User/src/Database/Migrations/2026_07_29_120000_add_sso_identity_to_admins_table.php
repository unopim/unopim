<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('sso_provider')->nullable()->after('type');
            $table->string('sso_identifier')->nullable()->after('sso_provider');

            $table->unique(['sso_provider', 'sso_identifier'], 'admins_sso_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique('admins_sso_identity_unique');

            $table->dropColumn(['sso_provider', 'sso_identifier']);
        });
    }
};
