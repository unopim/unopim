<?php

namespace Webkul\Installer\Helpers;

use Closure;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\RequestMemo;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as UnoPimDatabaseSeeder;

class DatabaseManager
{
    const INSTALLED_MEMO_KEY = 'installer.is_installed';

    /**
     * Check Database Connection.
     *
     * `CanInstall` asks on every request and the check costs a schema lookup
     * plus a count, so a positive answer is memoized for the rest of the
     * request. A negative one is not: the installer turns it positive mid-flow.
     */
    public function isInstalled(): bool
    {
        $memo = resolve(RequestMemo::class);

        if ($memo->has(self::INSTALLED_MEMO_KEY)) {
            return true;
        }

        if (! $this->resolveInstalled()) {
            return false;
        }

        $memo->set(self::INSTALLED_MEMO_KEY, true);

        return true;
    }

    private function resolveInstalled(): bool
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
     * Drop the tables of the configured table prefix and migrate in the database
     *
     * @return void|string
     */
    public function migration()
    {
        try {
            $this->dropCurrentPrefixTables();

            Artisan::call('migrate');
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Drops the tables of the configured prefix without CASCADE, so PostgreSQL refuses instead of removing
     * views or keys of other installs that depend on them. MySQL drops with the key checks off, so foreign
     * keys from tables outside the prefix are looked up first and stop the drop.
     */
    public function dropCurrentPrefixTables(): void
    {
        $connection = DB::connection();
        $schema = $connection->getSchemaBuilder();

        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            $schema->dropAllTables();

            return;
        }

        $tables = $this->prefixTables();

        if ($tables->isEmpty()) {
            return;
        }

        $this->ensureNoOutsideForeignKeys($connection, $tables->pluck('name')->all());

        $schema->withoutForeignKeyConstraints(fn () => $connection->statement(
            'drop table '.implode(', ', $connection->getSchemaGrammar()->escapeNames($tables->pluck('schema_qualified_name')->all()))
        ));
    }

    /**
     * @return array<int, string>
     */
    public function currentPrefixTables(): array
    {
        return $this->prefixTables()->pluck('schema_qualified_name')->all();
    }

    /**
     * @return array<int, string>
     */
    public function otherInstallPrefixes(): array
    {
        $connection = DB::connection();
        $schema = $connection->getSchemaBuilder();
        $migrationsTable = config('database.migrations.table', 'migrations');
        $comparable = $this->comparableName($connection);
        $tableNames = collect($schema->getTableListing($schema->getCurrentSchemaListing(), false))->map($comparable);

        return $tableNames
            ->filter(fn (string $table): bool => str_ends_with($table, $migrationsTable))
            ->map(fn (string $table): string => substr($table, 0, -strlen($migrationsTable)))
            ->reject(fn (string $prefix): bool => $prefix === $comparable($connection->getTablePrefix()))
            ->filter(fn (string $prefix): bool => $tableNames->contains($prefix.'admins')
                && $this->isMigrationsTable($connection, $prefix.$migrationsTable))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function prefixTables(): Collection
    {
        $connection = DB::connection();
        $schema = $connection->getSchemaBuilder();
        $comparable = $this->comparableName($connection);
        $prefix = $comparable($connection->getTablePrefix());
        $prefixes = [...$this->otherInstallPrefixes(), $prefix];

        return collect($schema->getTables($schema->getCurrentSchemaListing()))
            ->filter(fn (array $table): bool => $this->owningPrefix($comparable($table['name']), $prefixes) === $prefix)
            ->values();
    }

    /**
     * @param  array<int, string>  $prefixes
     */
    protected function owningPrefix(string $table, array $prefixes): string
    {
        return collect($prefixes)
            ->filter(fn (string $prefix): bool => str_starts_with($table, $prefix))
            ->sortByDesc(fn (string $prefix): int => strlen($prefix))
            ->first() ?? '';
    }

    /**
     * MySQL and MariaDB store table names in lower case when `lower_case_table_names` is on, so a `3X_`
     * prefix is then matched against `3x_attributes`. PostgreSQL keeps the case of quoted names.
     */
    protected function comparableName(Connection $connection): Closure
    {
        return $this->ignoresTableNameCase($connection)
            ? strtolower(...)
            : fn (string $name): string => $name;
    }

    protected function ignoresTableNameCase(Connection $connection): bool
    {
        if ($connection->getDriverName() === 'pgsql') {
            return false;
        }

        return (int) $connection->scalar('select @@lower_case_table_names') !== 0;
    }

    protected function isMigrationsTable(Connection $connection, string $table): bool
    {
        $columns = $connection->getPostProcessor()->processColumns(
            $connection->selectFromWriteConnection($connection->getSchemaGrammar()->compileColumns(null, $table))
        );

        return array_diff(['migration', 'batch'], array_column($columns, 'name')) === [];
    }

    /**
     * @param  array<int, string>  $tables
     */
    protected function ensureNoOutsideForeignKeys(Connection $connection, array $tables): void
    {
        if ($connection->getDriverName() === 'pgsql') {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($tables), '?'));

        $references = $connection->select(
            "select distinct TABLE_NAME as table_name, REFERENCED_TABLE_NAME as referenced_table_name
                from information_schema.KEY_COLUMN_USAGE
                where TABLE_SCHEMA = database() and REFERENCED_TABLE_SCHEMA = database()
                and REFERENCED_TABLE_NAME in ({$placeholders}) and TABLE_NAME not in ({$placeholders})",
            [...$tables, ...$tables]
        );

        throw_if($references !== [], Exception::class, 'Tables outside the table prefix reference the tables to drop: '.collect($references)
            ->map(fn (object $reference): string => "{$reference->table_name} -> {$reference->referenced_table_name}")
            ->implode(', ').'. Remove these foreign keys first.');
    }

    /**
     * Create the configured database if it does not already exist.
     *
     * `migrate` will not create the schema itself. The name is validated
     * against a strict pattern before interpolation to avoid SQL injection.
     */
    public function createDatabaseIfNotExists(): void
    {
        $connection = config('database.default');

        // Branch on the driver, not the connection name, which may be customized.
        $driver = config("database.connections.{$connection}.driver", $connection);

        // Only server-based drivers need an explicit CREATE DATABASE; skip others (e.g. sqlite).
        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
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
        Artisan::call('storage:link', ['--relative' => true]);
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
