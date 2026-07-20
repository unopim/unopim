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
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Fail-closed check: true only when we can positively confirm the app is not
     * yet installed (core_config readable and the install flag absent). Any
     * uncertainty — missing table or DB error — returns false so destructive
     * installer steps deny rather than treat uncertainty as "not installed".
     */
    public function canConfirmNotInstalled(): bool
    {
        try {
            if (! Schema::hasTable('core_config')) {
                return false;
            }

            return ! DB::table('core_config')
                ->where('code', self::INSTALLED_CONFIG_CODE)
                ->exists();
        } catch (Exception) {
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
        } catch (Exception) {
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
     * Create the configured database if it does not already exist.
     *
     * `migrate:fresh` will not create the schema itself. The name is validated
     * against a strict pattern before interpolation to avoid SQL injection.
     */
    public function createDatabaseIfNotExists(): void
    {
        $connection = config('database.default');

        // Branch on the driver, not the connection name, which may be customized.
        $driver = config("database.connections.{$connection}.driver", $connection);

        // Only server-based drivers need an explicit CREATE DATABASE; skip others (e.g. sqlite).
        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            return;
        }

        $database = config("database.connections.{$connection}.database");

        if (! $database) {
            return;
        }

        throw_unless(preg_match('/^\w+$/', (string) $database), Exception::class, "The database name '{$database}' is invalid. Use only letters, numbers, and underscores.");

        // Connect without the target database (pgsql needs the "postgres" maintenance db).
        config(["database.connections.{$connection}.database" => $driver === 'pgsql' ? 'postgres' : null]);

        DB::purge($connection);

        try {
            if ($driver === 'pgsql') {
                $exists = DB::connection($connection)->select('SELECT 1 FROM pg_database WHERE datname = ?', [$database]);

                if (empty($exists)) {
                    // CREATE DATABASE cannot run inside a transaction on pgsql.
                    DB::connection($connection)->getPdo()->exec("CREATE DATABASE \"{$database}\" ENCODING 'UTF8'");
                }
            } else {
                DB::connection($connection)->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        } finally {
            // Restore the database selection for the migration.
            config(["database.connections.{$connection}.database" => $database]);

            DB::purge($connection);
        }
    }

    /**
     * Seed the database.
     */
    public function seeder(array $data): ?string
    {
        try {
            resolve(UnoPimDatabaseSeeder::class)->run($data['parameter']);

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
