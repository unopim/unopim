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
     * Key under which the persistent "installation completed" flag is stored.
     */
    const INSTALLED_CONFIG_CODE = 'installer.installed';

    /**
     * Whether installation was completed, based on a persistent database flag
     * that survives loss of the ephemeral storage/ marker.
     */
    public function isMarkedInstalled(): bool
    {
        try {
            if (! Schema::hasTable('core_config')) {
                return false;
            }

            return DB::table('core_config')
                ->where('code', self::INSTALLED_CONFIG_CODE)
                ->exists();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Persist the "installation completed" flag in the database so the installer
     * stays sealed even if the storage/ marker is lost.
     */
    public function markInstalled(): void
    {
        try {
            if (! Schema::hasTable('core_config')) {
                return;
            }

            DB::table('core_config')->updateOrInsert(
                ['code' => self::INSTALLED_CONFIG_CODE],
                ['value' => '1']
            );
        } catch (Exception $e) {
            // Marker persistence is best-effort; the storage marker still applies.
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
     */
    public function generateKey()
    {
        try {
            Artisan::call('key:generate');
        } catch (Exception $e) {
        }
    }
}
