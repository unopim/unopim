<?php

use Illuminate\Support\Facades\Artisan;
use Webkul\Installer\Helpers\DatabaseManager;

describe('DatabaseManager::generateKey idempotence (issue #867)', function () {
    it('skips key:generate when APP_KEY is already set so the UI installer does not rotate the session-cookie cipher on retry', function () {
        config(['app.key' => 'base64:ZHVtbXkta2V5LXRoYXQtaXMtMzItYnl0ZXMtbG9uZw==']);

        Artisan::shouldReceive('call')->with('key:generate')->never();

        app(DatabaseManager::class)->generateKey();
    });

    it('runs key:generate exactly once when APP_KEY is empty', function () {
        config(['app.key' => null]);
        // Also clear the env() cache for APP_KEY so the new guard sees an unset key.
        putenv('APP_KEY');
        $_ENV['APP_KEY'] = '';
        $_SERVER['APP_KEY'] = '';

        Artisan::shouldReceive('call')->with('key:generate')->once();

        app(DatabaseManager::class)->generateKey();
    });
});
