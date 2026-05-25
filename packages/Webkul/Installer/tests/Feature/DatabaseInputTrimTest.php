<?php

use Webkul\Installer\Console\Commands\Installer;

function bindInstallerCapturingDbEnvUpdate(array &$captured): Closure
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

            public function call($command, array $arguments = [], $output = null)
            {
                return 0;
            }

            protected function envUpdate(string $key, string $value): void
            {
                $this->captured[$key] = $value;
            }

            public function handle()
            {
                $this->askForDatabaseDetails();

                return 0;
            }
        };
    };
}

it('trims trailing whitespace on DB_HOST so quote-wrapping in .env never breaks DNS resolution', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingDbEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1     ')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim')
        ->expectsQuestion('Please enter the database prefix', '')
        ->expectsQuestion('Please enter your database username', 'root')
        ->expectsQuestion('Please enter your database password', 'root')
        ->assertExitCode(0);

    expect($captured['DB_HOST'])->toBe('127.0.0.1');
});

it('rejects DB_HOST containing internal whitespace ', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingDbEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0 .1')
        ->assertExitCode(1);

    expect($captured)->not->toHaveKey('DB_HOST');
});

it('trims surrounding whitespace on DB_PORT', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingDbEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '  3306  ')
        ->expectsQuestion('Please enter the database name', 'unopim')
        ->expectsQuestion('Please enter the database prefix', '')
        ->expectsQuestion('Please enter your database username', 'root')
        ->expectsQuestion('Please enter your database password', 'root')
        ->assertExitCode(0);

    expect($captured['DB_PORT'])->toBe('3306');
});

it('rejects a non-numeric DB_PORT so the connection cannot be written with garbage', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingDbEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '33a06')
        ->assertExitCode(1);

    expect($captured)->not->toHaveKey('DB_PORT');
});

it('trims surrounding whitespace on DB_DATABASE so the schema lookup uses the bare name ', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingDbEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim    ')
        ->expectsQuestion('Please enter the database prefix', '')
        ->expectsQuestion('Please enter your database username', 'root')
        ->expectsQuestion('Please enter your database password', 'root')
        ->assertExitCode(0);

    expect($captured['DB_DATABASE'])->toBe('unopim');
});

it('trims surrounding whitespace on DB_USERNAME so PDO auth uses the bare name ', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingDbEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim')
        ->expectsQuestion('Please enter the database prefix', '')
        ->expectsQuestion('Please enter your database username', 'root  ')
        ->expectsQuestion('Please enter your database password', 'root')
        ->assertExitCode(0);

    expect($captured['DB_USERNAME'])->toBe('root');
});
