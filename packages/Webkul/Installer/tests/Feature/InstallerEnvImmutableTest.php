<?php

use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\EnvironmentManager;

/**
 * Build an EnvironmentManager pinned to a throwaway env path, so the tests
 * can assert byte-for-byte that the installer leaves it untouched.
 */
function envTestManager(string $path): EnvironmentManager
{
    return new class($path, resolve(DatabaseManager::class)) extends EnvironmentManager
    {
        public function __construct(private readonly string $path, DatabaseManager $databaseManager)
        {
            parent::__construct($databaseManager);
        }

        protected function envPath(): string
        {
            return $this->path;
        }
    };
}

it('never modifies an existing env file, whatever the request carries', function () {
    $path = tempnam(sys_get_temp_dir(), 'env-immutable-');
    $original = "APP_NAME=Manual\nDB_HOST=10.0.0.9\n";
    file_put_contents($path, $original);

    $result = envTestManager($path)->generateEnv([
        'db_hostname'   => 'attacker-host',
        'db_name'       => 'attacker_db',
        'db_username'   => 'attacker',
        'db_password'   => 'attacker',
        'db_connection' => 'mysql',
        'db_port'       => 3306,
        'app_name'      => 'Overwrite',
        'app_url'       => 'http://evil.example',
    ]);

    expect($result)->toBeTrue()
        ->and(file_get_contents($path))->toBe($original);

    @unlink($path);
});

it('accepts a server-provided environment when no env file exists', function () {
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

    $result = envTestManager(sys_get_temp_dir().'/env-immutable-absent-'.uniqid())->generateEnv([]);

    expect($result)->toBeTrue();
});

it('rejects installation when neither an env file nor an APP_KEY exists', function () {
    config(['app.key' => '']);

    $result = envTestManager(sys_get_temp_dir().'/env-immutable-absent-'.uniqid())->generateEnv([]);

    expect($result)->toBeInstanceOf(Exception::class);
});

it('no longer exposes an env-writing entry point', function () {
    expect(method_exists(EnvironmentManager::class, 'setEnvConfiguration'))->toBeFalse();
});
