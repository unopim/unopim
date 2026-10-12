<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Webkul\Installer\Helpers\Upgrade\BackupManager;

/**
 * Continuing past a failed backup is what turns a recoverable migration problem
 * into data loss, so every failure path here must throw rather than warn.
 */
beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/unopim-upgrade-backup-'.uniqid();
});

afterEach(function () {
    File::deleteDirectory($this->directory);
});

it('refuses to back up a driver it has no dump command for', function () {
    config(['database.connections.upgrade_unsupported' => ['driver' => 'sqlite', 'database' => ':memory:']]);

    DB::setDefaultConnection('upgrade_unsupported');

    expect(fn () => app(BackupManager::class)->dump($this->directory))
        ->toThrow(RuntimeException::class, trans('installer::app.upgrade.backup.unsupported-driver', ['driver' => 'sqlite']));
});

it('dumps a mariadb connection with the mariadb client arguments', function () {
    $manager = new class extends BackupManager
    {
        /**
         * @param  array<string, mixed>  $config
         */
        public function exposeProcessFor(string $driver, array $config, string $path): Process
        {
            return $this->processFor($driver, $config, $path);
        }
    };

    $process = $manager->exposeProcessFor('mariadb', [
        'host'     => '127.0.0.1',
        'port'     => '3306',
        'database' => 'unopim',
        'username' => 'unopim',
        'password' => 'secret',
    ], $this->directory.'/unopim.sql');

    expect($process->getCommandLine())->toMatch('/(mariadb-dump|mysqldump)/')
        ->not->toContain('--no-tablespaces');

    expect($process->getEnv())->toHaveKey('MYSQL_PWD');
});

it('throws and leaves no file behind when the dump command fails', function () {
    $connection = config('database.default');

    config(["database.connections.{$connection}.username" => 'a_user_that_cannot_authenticate']);

    DB::purge($connection);

    expect(fn () => app(BackupManager::class)->dump($this->directory))->toThrow(RuntimeException::class);

    expect(File::exists($this->directory) ? File::files($this->directory) : [])->toBeEmpty();
});

it('discards a dump that lands empty', function () {
    File::ensureDirectoryExists($this->directory);

    $manager = new class extends BackupManager
    {
        public function exposeVerify(string $path): void
        {
            $this->verify($path);
        }
    };

    $path = $this->directory.'/empty.sql';

    File::put($path, '');

    expect(fn () => $manager->exposeVerify($path))
        ->toThrow(RuntimeException::class, trans('installer::app.upgrade.backup.empty'));

    expect(File::exists($path))->toBeFalse();
});

it('accepts a dump large enough to hold real content', function () {
    File::ensureDirectoryExists($this->directory);

    $manager = new class extends BackupManager
    {
        public function exposeVerify(string $path): void
        {
            $this->verify($path);
        }
    };

    $path = $this->directory.'/full.sql';

    File::put($path, str_repeat('-- dump header line'.PHP_EOL, 200));

    $manager->exposeVerify($path);

    expect(File::exists($path))->toBeTrue();
});
