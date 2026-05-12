<?php

use Webkul\Installer\Console\Commands\Installer;

/**
 * Replace the Installer command with a subclass that captures every
 * envUpdate(key, value) call into the given array reference, and mute
 * downstream `call()` invocations so the test focuses purely on prompt
 * → envUpdate flow.
 */
function bindInstallerCapturingEnvUpdate(array &$captured): Closure
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

it('trims surrounding whitespace on DB_PREFIX so "  uno  " never reaches envUpdate as a quote-wrapped value (issue #538)', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim')
        ->expectsQuestion('Please enter the database prefix', '  uno  ')
        ->expectsQuestion('Please enter your database username', 'root')
        ->expectsQuestion('Please enter your database password', 'root')
        ->assertExitCode(0);

    expect($captured)->toHaveKey('DB_PREFIX')
        ->and($captured['DB_PREFIX'])->toBe('uno');
});

it('rejects a DB_PREFIX containing an internal space ("a a") so it never reaches envUpdate or .env (issue #538)', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingEnvUpdate($captured));

    // Laravel Prompts in non-interactive mode re-prompts on validation
    // failure until a fresh answer is provided or stdin is exhausted —
    // since we never supply a follow-up answer, the prompt aborts and
    // the artisan command exits non-zero. That's the contract here:
    // "a a" must NOT make it through, and DB_PREFIX must NOT have been
    // captured as a quote-wrapped corruption.
    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim')
        ->expectsQuestion('Please enter the database prefix', 'a a')
        ->assertExitCode(1);

    expect($captured)->not->toHaveKey('DB_PREFIX');
});

it('writes an empty DB_PREFIX to envUpdate so a stale prefix can be cleared from .env (issue #538)', function () {
    $captured = [];
    $this->app->extend(Installer::class, bindInstallerCapturingEnvUpdate($captured));

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim')
        ->expectsQuestion('Please enter the database prefix', '')
        ->expectsQuestion('Please enter your database username', 'root')
        ->expectsQuestion('Please enter your database password', 'root')
        ->assertExitCode(0);

    // DB_PREFIX must still be written — otherwise the loop's `if ($value)`
    // guard skips the write and leaves the previous DB_PREFIX (e.g. the
    // corrupt `"a a"` from a botched earlier run) stuck in .env.
    expect($captured)->toHaveKey('DB_PREFIX')
        ->and($captured['DB_PREFIX'])->toBe('');
});
