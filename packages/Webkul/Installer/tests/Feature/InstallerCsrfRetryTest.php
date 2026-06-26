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

    // env-file-setup now aborts(403) once installation is complete; simulate
    // an in-progress install by removing the marker and restoring it after.
    $this->marker = storage_path('installed');
    $this->markerExisted = file_exists($this->marker);
    $this->markerContents = $this->markerExisted ? file_get_contents($this->marker) : null;

    if ($this->markerExisted) {
        unlink($this->marker);
    }
});

afterEach(function () {
    if ($this->markerExisted) {
        file_put_contents($this->marker, $this->markerContents);
    } elseif (file_exists($this->marker)) {
        unlink($this->marker);
    }
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
