<?php

use Webkul\Installer\Console\Commands\Installer;

function bindInstallerCapturingDbNameEnvUpdate(array &$captured): Closure
{
    return function () use (&$captured) {
        return new class($captured) extends Installer
        {
            private array $captured;

            public function __construct(array &$captured)
            {
                parent::__construct();
                $this->captured = &$captured;
            }

            public function call($command, array $arguments = [], $output = null): int
            {
                return 0;
            }

            protected function envUpdate(string $key, string $value): void
            {
                $this->captured[$key] = $value;
            }

            public function handle(): int
            {
                $this->askForDatabaseDetails();

                return 0;
            }
        };
    };
}

it('rejects DB_DATABASE containing a dot so DROP TABLE wk_db.table cannot be parsed as a multi-segment identifier', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingDbNameEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', '1.2.3')
        ->assertExitCode(1);

    expect($captured)->not->toHaveKey('DB_DATABASE');
});

it('rejects DB_DATABASE containing a dash', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingDbNameEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'my-db')
        ->assertExitCode(1);

    expect($captured)->not->toHaveKey('DB_DATABASE');
});

it('accepts valid alphanumeric DB_DATABASE ', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingDbNameEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim_v2')
        ->expectsQuestion('Please enter the database prefix', '')
        ->expectsQuestion('Please enter your database username', 'root')
        ->expectsQuestion('Please enter your database password', 'root')
        ->assertExitCode(0);

    expect($captured['DB_DATABASE'])->toBe('unopim_v2');
});
