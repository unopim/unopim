<?php

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Webkul\Installer\Console\Commands\Installer;

describe('Installer::loadEnvConfigAtRuntime DB default override (issue #797)', function () {
    it('overrides database.default to match the .env DB_CONNECTION at runtime', function () {
        config(['database.default' => 'mysql']);

        $original = $_ENV;

        $_ENV['DB_CONNECTION'] = 'pgsql';
        $_ENV['DB_HOST'] = '127.0.0.1';
        $_ENV['DB_PORT'] = '5432';
        $_ENV['DB_DATABASE'] = 'unopim';
        $_ENV['DB_USERNAME'] = 'postgres';
        $_ENV['DB_PASSWORD'] = 'postgres';
        $_ENV['DB_PREFIX'] = '';
        $_ENV['APP_ENV'] = 'local';
        $_ENV['APP_NAME'] = 'UnoPim';
        $_ENV['APP_URL'] = 'http://localhost';
        $_ENV['APP_TIMEZONE'] = 'UTC';
        $_ENV['APP_LOCALE'] = 'en_US';
        $_ENV['APP_CURRENCY'] = 'USD';

        try {
            $cmd = app(Installer::class);
            $cmd->setLaravel(app());
            $cmd->setInput(new ArrayInput([]));
            $cmd->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

            $reflection = new ReflectionMethod($cmd, 'loadEnvConfigAtRuntime');
            $reflection->setAccessible(true);
            $reflection->invoke($cmd);

            expect(config('database.default'))->toBe('pgsql');
        } finally {
            $_ENV = $original;
        }
    });
});
