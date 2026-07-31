<?php

use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\EnvironmentManager;

/**
 * The installer's .env writer takes unauthenticated request input while the
 * install is open. A value carrying a newline must never become a second
 * `KEY=value` line — that would let a request smuggle arbitrary environment
 * keys (APP_DEBUG, filesystem roots, …) past the whitelisted parameter map.
 */
beforeEach(function () {
    $this->envFile = sys_get_temp_dir().'/unopim-env-injection-'.getmypid().'.env';

    file_put_contents($this->envFile, "APP_NAME=UnoPim\nAPP_DEBUG=false\nDB_HOST=127.0.0.1\n");

    $this->manager = new class(app(DatabaseManager::class), $this->envFile) extends EnvironmentManager
    {
        public function __construct(DatabaseManager $databaseManager, private string $testEnvPath)
        {
            parent::__construct($databaseManager);
        }

        protected function envPath(): string
        {
            return $this->testEnvPath;
        }
    };
});

afterEach(function () {
    @unlink($this->envFile);
});

it('does not let a newline in a value append extra env lines', function () {
    $this->manager->setEnvConfiguration([
        'db_hostname'   => "127.0.0.1\nAPP_DEBUG=true",
        'db_name'       => 'unopim',
        'db_username'   => 'root',
        'db_password'   => 'secret',
        'db_connection' => 'mysql',
        'db_port'       => '3306',
    ]);

    $contents = file_get_contents($this->envFile);

    expect(preg_match_all('/^APP_DEBUG=/m', $contents))->toBe(1)
        ->and($contents)->toContain('APP_DEBUG=false')
        ->and($contents)->not->toContain("DB_HOST=\"127.0.0.1\nAPP_DEBUG=true\"");
});

it('strips control characters from every written value', function () {
    $this->manager->setEnvConfiguration([
        'app_name'     => "Uno\rPim\x00",
        'app_url'      => "http://example.test\nMAIL_HOST=evil.test",
        'app_currency' => 'USD',
        'app_locale'   => 'en_US',
        'app_timezone' => 'UTC',
    ]);

    $contents = file_get_contents($this->envFile);

    expect($contents)->not->toContain("\nMAIL_HOST=evil.test")
        ->and($contents)->toContain('APP_URL=http://example.testMAIL_HOST=evil.test')
        ->and($contents)->toContain('APP_NAME=UnoPim');
});
