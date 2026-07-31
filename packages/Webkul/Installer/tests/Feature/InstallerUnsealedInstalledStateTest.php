<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Reproduces the `.env` reset incident: on an installed instance whose marker
 * and installer.installed flag were both lost, an unauthenticated env-file-setup
 * POST used to rewrite a live .env. The abortIfDatabasePopulated() guard blocks it.
 */
beforeEach(function () {
    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');

    $this->marker = storage_path('installed');
    $this->markerExisted = file_exists($this->marker);
    $this->markerContents = $this->markerExisted ? file_get_contents($this->marker) : null;

    if ($this->markerExisted) {
        unlink($this->marker);
    }

    DB::table('core_config')->where('code', 'installer.installed')->delete();

    // Guard the real .env from any mutation the vulnerable path performs.
    $this->envPath = base_path('.env');
    $this->envBackup = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;
});

afterEach(function () {
    if ($this->markerExisted) {
        file_put_contents($this->marker, $this->markerContents);
    } elseif (file_exists($this->marker)) {
        unlink($this->marker);
    }

    DB::table('core_config')->where('code', 'installer.installed')->delete();

    if ($this->envBackup !== null) {
        file_put_contents($this->envPath, $this->envBackup);
    }
});

it('proves the DB is installed even though both seal signals are absent', function () {
    expect(DB::table('admins')->where('id', 1)->exists())->toBeTrue();
    expect(config('app.key'))->not->toBeEmpty();
    expect(file_exists($this->marker))->toBeFalse();
    expect(DB::table('core_config')->where('code', 'installer.installed')->exists())->toBeFalse();
});

it('forbids env-file-setup when the live DB is installed but marker and flag are absent', function () {
    $this->withoutMiddleware()
        ->postJson('/install/api/env-file-setup', ['db_prefix' => 'ab'])
        ->assertForbidden();
});

it('does not rewrite APP_URL via an unauthenticated env-file-setup on an installed instance', function () {
    $before = $this->envBackup;

    $this->withoutMiddleware()
        ->postJson('/install/api/env-file-setup', [
            'db_connection' => 'mysql',
            'db_hostname'   => '127.0.0.1',
            'db_port'       => '3306',
            'db_name'       => 'unopim',
            'db_username'   => 'root',
            'db_password'   => '',
            'db_prefix'     => '',
            'app_name'      => 'UnoPim',
            'app_url'       => 'http://attacker.example',
            'app_currency'  => 'USD',
            'app_locale'    => 'en_US',
            'app_timezone'  => 'UTC',
        ]);

    $after = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;

    expect($after)->toBe($before);
});
