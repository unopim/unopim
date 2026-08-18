<?php

namespace Webkul\Installer\Helpers\Upgrade;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Environment and state checks that run before the upgrade touches anything.
 *
 * Every check is read-only, so a failure leaves the installation exactly as it
 * was and the previous release keeps serving traffic.
 */
class PreflightChecker
{
    public function __construct(protected MigrationInspector $migrations) {}

    /**
     * @return array<int, CheckResult>
     */
    public function run(bool $ignoreSourceVersion = false): array
    {
        return [
            $this->checkPhpVersion(),
            $this->checkExtensions(),
            $this->checkDatabaseConnection(),
            $this->checkSourceVersion($ignoreSourceVersion),
            $this->checkPendingMigrations(),
            $this->checkActiveJobs(),
            $this->checkWritablePaths(),
            $this->checkDiskSpace(),
        ];
    }

    protected function checkPhpVersion(): CheckResult
    {
        $minimum = (string) config('upgrade.minimum_php');

        $name = trans('installer::app.upgrade.checks.php-version');

        if (version_compare(PHP_VERSION, $minimum, '>=')) {
            return CheckResult::passed($name, PHP_VERSION);
        }

        return CheckResult::failed(
            $name,
            trans('installer::app.upgrade.checks.php-version-detail', ['required' => $minimum, 'found' => PHP_VERSION]),
            trans('installer::app.upgrade.checks.php-version-remedy', ['required' => $minimum])
        );
    }

    protected function checkExtensions(): CheckResult
    {
        $name = trans('installer::app.upgrade.checks.extensions');

        $missing = array_values(array_filter(
            (array) config('upgrade.required_extensions'),
            fn (string $extension): bool => ! extension_loaded($extension)
        ));

        if ($missing === []) {
            return CheckResult::passed($name);
        }

        return CheckResult::failed(
            $name,
            implode(', ', $missing),
            trans('installer::app.upgrade.checks.extensions-remedy')
        );
    }

    protected function checkDatabaseConnection(): CheckResult
    {
        $name = trans('installer::app.upgrade.checks.database');

        try {
            $connection = $this->connection();

            $connection->getPdo();

            return CheckResult::passed($name, trans('installer::app.upgrade.checks.database-detail', [
                'driver'   => $connection->getDriverName(),
                'database' => (string) $connection->getDatabaseName(),
                'prefix'   => $connection->getTablePrefix() ?: '—',
            ]));
        } catch (\Throwable $e) {
            return CheckResult::failed(
                $name,
                $e->getMessage(),
                trans('installer::app.upgrade.checks.database-remedy')
            );
        }
    }

    protected function checkSourceVersion(bool $ignore): CheckResult
    {
        $name = trans('installer::app.upgrade.checks.source-version');

        $minimum = (string) config('upgrade.minimum_source_version');

        if ($this->migrations->hasRun((string) config('upgrade.source_version_sentinel'))) {
            return CheckResult::passed($name, trans('installer::app.upgrade.checks.source-version-detail', ['version' => $minimum]));
        }

        $detail = trans('installer::app.upgrade.checks.source-version-unsupported', ['version' => $minimum]);

        $remedy = trans('installer::app.upgrade.checks.source-version-remedy', ['version' => $minimum]);

        return $ignore
            ? CheckResult::warning($name, $detail, $remedy)
            : CheckResult::failed($name, $detail, $remedy);
    }

    protected function checkPendingMigrations(): CheckResult
    {
        $name = trans('installer::app.upgrade.checks.pending-migrations');

        try {
            $pending = $this->migrations->pending();
        } catch (\Throwable $e) {
            return CheckResult::failed($name, $e->getMessage(), trans('installer::app.upgrade.checks.pending-migrations-remedy'));
        }

        if ($pending === []) {
            return CheckResult::warning($name, trans('installer::app.upgrade.checks.pending-migrations-none'));
        }

        return CheckResult::passed($name, trans('installer::app.upgrade.checks.pending-migrations-detail', ['count' => count($pending)]));
    }

    protected function checkActiveJobs(): CheckResult
    {
        $name = trans('installer::app.upgrade.checks.active-jobs');

        if (! Schema::hasTable('job_track')) {
            return CheckResult::passed($name);
        }

        $query = DB::table('job_track')->whereIn('state', (array) config('upgrade.active_job_states'));

        if (Schema::hasColumn('job_track', 'heartbeat_at')) {
            $cutoff = now()->subMinutes((int) config('upgrade.stale_job_minutes'));

            $query->where(function ($builder) use ($cutoff): void {
                $builder->whereNull('heartbeat_at')->orWhere('heartbeat_at', '>=', $cutoff);
            });
        }

        $active = $query->count();

        if ($active === 0) {
            return CheckResult::passed($name);
        }

        return CheckResult::failed(
            $name,
            trans('installer::app.upgrade.checks.active-jobs-detail', ['count' => $active]),
            trans('installer::app.upgrade.checks.active-jobs-remedy')
        );
    }

    protected function checkWritablePaths(): CheckResult
    {
        $name = trans('installer::app.upgrade.checks.writable-paths');

        $unwritable = array_values(array_filter(
            (array) config('upgrade.writable_paths'),
            fn (string $path): bool => ! is_writable(base_path($path))
        ));

        if ($unwritable === []) {
            return CheckResult::passed($name);
        }

        return CheckResult::failed(
            $name,
            implode(', ', $unwritable),
            trans('installer::app.upgrade.checks.writable-paths-remedy')
        );
    }

    protected function checkDiskSpace(): CheckResult
    {
        $name = trans('installer::app.upgrade.checks.disk-space');

        $free = @disk_free_space(base_path());

        if ($free === false) {
            return CheckResult::warning($name, trans('installer::app.upgrade.checks.disk-space-unknown'));
        }

        $required = $this->estimatedDatabaseBytes() + (int) config('upgrade.disk_headroom_bytes');

        if ($free >= $required) {
            return CheckResult::passed($name, trans('installer::app.upgrade.checks.disk-space-detail', [
                'free'     => $this->humanBytes((int) $free),
                'required' => $this->humanBytes($required),
            ]));
        }

        return CheckResult::failed(
            $name,
            trans('installer::app.upgrade.checks.disk-space-detail', [
                'free'     => $this->humanBytes((int) $free),
                'required' => $this->humanBytes($required),
            ]),
            trans('installer::app.upgrade.checks.disk-space-remedy')
        );
    }

    /**
     * Best-effort on-disk size of the current database, used to size the dump.
     *
     * Both engines expose this only through vendor-specific catalogs, so an
     * unsupported driver — or a user without catalog access — degrades to zero
     * and the fixed headroom alone applies.
     */
    protected function estimatedDatabaseBytes(): int
    {
        $connection = $this->connection();

        $database = (string) $connection->getDatabaseName();

        try {
            return match ($connection->getDriverName()) {
                'mysql', 'mariadb' => (int) ($connection->selectOne(
                    'select sum(data_length + index_length) as size from information_schema.tables where table_schema = ?',
                    [$database]
                )?->size ?? 0),
                'pgsql' => (int) ($connection->selectOne('select pg_database_size(?) as size', [$database])?->size ?? 0),
                default => 0,
            };
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;

        $power = min($power, count($units) - 1);

        return round($bytes / 1024 ** $power, 1).' '.$units[$power];
    }

    protected function connection(): ConnectionInterface
    {
        return DB::connection();
    }
}
