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
                Schema::table('oauth_clients', function (Blueprint $table) {
                    $table->uuid('id')->change();
                });
                break;

            case 'pgsql':
                // PostgreSQL: safe default is to leave id as bigint
                // Optional: uncomment to convert to UUID (requires pgcrypto extension)
                /*
                DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');
                DB::statement('ALTER TABLE oauth_clients ALTER COLUMN id DROP DEFAULT;');
                DB::statement('ALTER TABLE oauth_clients ALTER COLUMN id TYPE uuid USING gen_random_uuid();');
                */
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            //
        });
    }
};
