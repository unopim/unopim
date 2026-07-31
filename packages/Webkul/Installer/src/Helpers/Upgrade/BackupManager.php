<?php

namespace Webkul\Installer\Helpers\Upgrade;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

        $command = match ($driver) {
            'mysql' => ['mysqldump', '--host='.$host, '--port='.$port, '--user='.$username, '--single-transaction', '--routines', '--result-file='.$path, $database],
            'pgsql' => ['pg_dump', '--host='.$host, '--port='.$port, '--username='.$username, '--file='.$path, $database],
            default => throw new \RuntimeException(trans('installer::app.upgrade.backup.unsupported-driver', ['driver' => $driver])),
        };

        /**
         * Passed through the environment rather than the argument list so the
         * password never appears in the host's process table.
         */
        $env = match ($driver) {
            'mysql' => ['MYSQL_PWD' => $password],
            'pgsql' => ['PGPASSWORD' => $password],
        };

        return new Process($command, base_path(), $env + ['PATH' => getenv('PATH') ?: '/usr/bin:/bin']);
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
