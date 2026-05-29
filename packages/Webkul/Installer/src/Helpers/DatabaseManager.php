<?php

namespace Webkul\Installer\Helpers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as UnoPimDatabaseSeeder;

class DatabaseManager
{
    /**
     * Check Database Connection.
     */
    public function isInstalled(): bool
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

            return (bool) $userCount;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Drop all the tables and migrate in the database
     */
    public function migration(): ?JsonResponse
    {
        try {
            Artisan::call('migrate:fresh');
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }

        return null;
    }

    /**
     * Seed the database.
     */
    public function seeder(array $data): ?string
    {
        try {
            app(UnoPimDatabaseSeeder::class)->run($data['parameter']);

            $this->storageLink();
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * Storage Link.
     */
    private function storageLink(): void
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
    public function generateKey(): void
    {
        if (! empty(config('app.key'))) {
            return;
        }

        try {
            Artisan::call('key:generate');
        } catch (Exception) {
        }
    }
}
