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
     *
     * Only generates a key when one is not already set. Rotating APP_KEY on
     * every UI-installer retry would re-encrypt the session cookie with a new
     * cipher key, so the user's existing session (and CSRF token) would be
     * silently discarded on the next request — surfacing as a 419 Page Expired
     */
    public function generateKey()
    {
        if (! empty(config('app.key')) || ! empty(env('APP_KEY'))) {
            return;
        }

        try {
            Artisan::call('key:generate');
        } catch (Exception $e) {
        }
    }
}
