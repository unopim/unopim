<?php

namespace Webkul\Installer\Helpers;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as UnoPimDatabaseSeeder;

class DatabaseManager
{
    /**
     * Check Database Connection.
     */
    public function isInstalled()
    {
        if (! file_exists(base_path('.env'))) {
            return false;
        }

        try {
            DB::connection()->getPDO();

            $isConnected = (bool) DB::connection()->getDatabaseName();

            if (! $isConnected) {
                return false;
            }

            $hasTable = Schema::hasTable('admins');

            if (! $hasTable) {
                return false;
            }

            $userCount = DB::table('admins')->count();

            if (! $userCount) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Drop all the tables and migrate in the database
     *
     * @return void|string
     */
    public function migration()
    {
        try {
            Artisan::call('migrate:fresh');
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Create the configured database if it does not already exist.
     *
     * `migrate:fresh` will not create the schema itself, so connect to the
     * server without a selected database and create it first. The database
     * name is validated against a strict pattern before being interpolated
     * into the statement to avoid SQL injection from the .env value.
     */
    public function createDatabaseIfNotExists(): void
    {
        $connection = config('database.default');

        $database = config("database.connections.{$connection}.database");

        if (! $database) {
            return;
        }

        if (! preg_match('/^[A-Za-z0-9_]+$/', (string) $database)) {
            throw new Exception("The database name '{$database}' is invalid. Use only letters, numbers, and underscores.");
        }

        /**
         * Connect to the server without the target database. MySQL allows a
         * null database; PostgreSQL requires connecting to an existing
         * maintenance database ("postgres") to issue CREATE DATABASE.
         */
        config(["database.connections.{$connection}.database" => $connection === 'pgsql' ? 'postgres' : null]);

        DB::purge($connection);

        try {
            if ($connection === 'pgsql') {
                $exists = DB::connection($connection)->select('SELECT 1 FROM pg_database WHERE datname = ?', [$database]);

                if (empty($exists)) {
                    // CREATE DATABASE cannot run inside a transaction on PostgreSQL.
                    DB::connection($connection)->getPdo()->exec("CREATE DATABASE \"{$database}\" ENCODING 'UTF8'");
                }
            } else {
                DB::connection($connection)->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        } finally {
            // Restore the database selection for the subsequent migration.
            config(["database.connections.{$connection}.database" => $database]);

            DB::purge($connection);
        }
    }

    /**
     * Seed the database.
     *
     * @return void|string
     */
    public function seeder($data)
    {
        try {
            app(UnoPimDatabaseSeeder::class)->run($data['parameter']);

            $this->storageLink();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Storage Link.
     */
    private function storageLink()
    {
        Artisan::call('storage:link');
    }

    /**
     * Generate New Application Key
     */
    public function generateKey()
    {
        try {
            Artisan::call('key:generate');
        } catch (Exception $e) {
        }
    }
}
