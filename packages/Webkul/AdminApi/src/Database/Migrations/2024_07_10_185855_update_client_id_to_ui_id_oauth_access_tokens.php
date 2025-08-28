<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'mysql':
                Schema::table('oauth_access_tokens', function (Blueprint $table) {
                    $table->uuid('client_id')->change();
                });
                break;

            case 'pgsql':
                DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN client_id DROP DEFAULT;');
                DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN client_id TYPE uuid USING md5(client_id::text)::uuid;');
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'mysql':
                Schema::table('oauth_access_tokens', function (Blueprint $table) {
                    $table->unsignedBigInteger('client_id')->change();
                });
                break;

            case 'pgsql':
                DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN client_id DROP DEFAULT;');
                DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN client_id TYPE bigint USING 1;');
                break;
        }
    }
};
