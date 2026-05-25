<?php

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Webkul\Installer\Console\Commands\Installer;

describe('Installer::loadEnvConfigAtRuntime DB default override (issue #797)', function () {
    it('overrides database.default to match the .env DB_CONNECTION at runtime', function () {
        config(['database.default' => 'mysql']);

        // Stub getEnvAtRuntime via an anonymous subclass rather than writing
        // to base_path('.env'). Touching the shared on-disk .env in tests
        // would race against sibling workers in parallel mode (and against
        // anything else in this PHP process that reads .env), which is
        // exactly what bit us when this test was first written.
        $cmd = new class extends Installer
        {
            protected static function getEnvAtRuntime(string $key): string|bool
            {
                return match ($key) {
                    'DB_CONNECTION' => 'pgsql',
                    'DB_HOST'       => '127.0.0.1',
                    'DB_PORT'       => '5432',
                    'DB_DATABASE'   => 'unopim',
                    'DB_USERNAME'   => 'postgres',
                    'DB_PASSWORD'   => 'postgres',
                    'DB_PREFIX'     => '',
                    'APP_ENV'       => 'local',
                    'APP_NAME'      => 'UnoPim',
                    'APP_URL'       => 'http://localhost',
                    'APP_TIMEZONE'  => 'UTC',
                    'APP_LOCALE'    => 'en_US',
                    'APP_CURRENCY'  => 'USD',
                    default         => '',
                };
            }
        };

        $cmd->setLaravel(app());
        $cmd->setInput(new ArrayInput([]));
        $cmd->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

        (new ReflectionMethod($cmd, 'loadEnvConfigAtRuntime'))->invoke($cmd);

        expect(config('database.default'))->toBe('pgsql');
    });
});
