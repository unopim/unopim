<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\EnvironmentManager;

beforeEach(function () {

    $this->withoutMiddleware();

    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');

    app()->instance(EnvironmentManager::class, new class(app(DatabaseManager::class)) extends EnvironmentManager
    {
        public function generateEnv($request)
        {

            $this->databaseManager->generateKey();

            return true;
        }
    });
});

describe('UI installer env-file-setup retries do not rotate APP_KEY', function () {
    it('skips key:generate on retry when APP_KEY is already set', function () {

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        Artisan::shouldReceive('call')->with('key:generate')->never();
        $first = $this->postJson('/install/api/env-file-setup', [
            'db_prefix'     => '',
            'db_hostname'   => '127.0.0.1',
            'db_port'       => 3306,
            'db_connection' => 'mysql',
            'db_name'       => 'unopim',
            'db_username'   => 'root',
            'db_password'   => 'wrong',
        ]);

        $first->assertOk();

        $second = $this->postJson('/install/api/env-file-setup', [
            'db_prefix'     => '',
            'db_hostname'   => '127.0.0.1',
            'db_port'       => 3306,
            'db_connection' => 'mysql',
            'db_name'       => 'unopim',
            'db_username'   => 'root',
            'db_password'   => 'correct',
        ]);

        $second->assertOk();
        expect($second->status())->not->toBe(419);
    });
});
