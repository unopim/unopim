<?php

namespace Webkul\Installer\Helpers\Upgrade;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Writes and verifies the pre-migration database dump.
 *
 * A dump that fails, or that lands empty, aborts the upgrade. Continuing past a
 * failed backup is what turns a recoverable migration problem into data loss.
 */
class BackupManager
{
    /**
     * Smallest plausible dump. Both `mysqldump` and `pg_dump` emit several
     * hundred bytes of header before any data, so anything under this means the
     * command produced nothing usable.
     */
    protected const MINIMUM_BYTES = 1024;

    /**
     * @throws \RuntimeException when the dump cannot be produced or verified.
     */
    public function dump(?string $directory = null): string
    {
        $connection = DB::connection();

        $driver = $connection->getDriverName();

        $directory ??= storage_path('app/upgrade-backups');

        File::ensureDirectoryExists($directory);

        $path = $directory.'/'.$connection->getDatabaseName().'-'.now()->format('Y-m-d_H-i-s').'.sql';

        $process = $this->processFor($driver, $connection->getConfig(), $path);

        $process->setTimeout(null);

        $process->run();

        if (! $process->isSuccessful()) {
            File::delete($path);

            throw new \RuntimeException(trans('installer::app.upgrade.backup.failed', [
                'error' => trim($process->getErrorOutput()) ?: $process->getExitCodeText(),
            ]));
        }

        $this->verify($path);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function processFor(string $driver, array $config, string $path): Process
    {
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '');
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        /**
         * `--no-tablespaces` keeps mysqldump 8 from reading INFORMATION_SCHEMA
         * tablespace data, which needs the PROCESS privilege that a
         * least-privileged application user is not granted. MariaDB has no
         * tablespace metadata and rejects the option outright, so it is sent
         * only to MySQL.
         */
        $command = match ($driver) {
            'mysql', 'mariadb' => [
                $this->mysqlDumpBinary($driver),
                '--host='.$host,
                '--port='.$port,
                '--user='.$username,
                '--single-transaction',
                ...($driver === 'mysql' ? ['--no-tablespaces'] : []),
                '--routines',
                '--result-file='.$path,
                $database,
            ],
            'pgsql' => ['pg_dump', '--host='.$host, '--port='.$port, '--username='.$username, '--file='.$path, $database],
            default => throw new \RuntimeException(trans('installer::app.upgrade.backup.unsupported-driver', ['driver' => $driver])),
        };

        /**
         * Passed through the environment rather than the argument list so the
         * password never appears in the host's process table.
         */
        $env = match ($driver) {
            'mysql', 'mariadb' => ['MYSQL_PWD' => $password],
            'pgsql'            => ['PGPASSWORD' => $password],
        };

        return new Process($command, base_path(), $env + ['PATH' => getenv('PATH') ?: '/usr/bin:/bin']);
    }

    /**
     * MariaDB 11 renamed its client binaries and ships the `mysqldump` alias
     * only as a deprecated shim, so the modern name is preferred where the
     * server is MariaDB and the alias kept as the fallback.
     */
    protected function mysqlDumpBinary(string $driver): string
    {
        if ($driver !== 'mariadb') {
            return 'mysqldump';
        }

        return (new ExecutableFinder)->find('mariadb-dump') ? 'mariadb-dump' : 'mysqldump';
    }

    /**
     * @throws \RuntimeException
     */
    protected function verify(string $path): void
    {
        $size = File::exists($path) ? File::size($path) : 0;

        if ($size >= self::MINIMUM_BYTES) {
            return;
        }

        File::delete($path);

        throw new \RuntimeException(trans('installer::app.upgrade.backup.empty'));
    }
}
