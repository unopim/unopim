<?php

use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\RequestMemo;
use Webkul\Installer\Helpers\DatabaseManager;

it('asks the database once per request whether the app is installed', function () {
    $manager = app(DatabaseManager::class);

    expect($manager->isInstalled())->toBeTrue();

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($manager->isInstalled())->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('reads the mail settings only once a mailer is resolved', function () {
    CoreConfig::updateOrCreate(
        ['code' => 'emails.configure.email_settings.mail_host'],
        ['value' => 'smtp.example.test']
    );

    Cache::flush();

    config(['mail.default' => 'log']);

    app(RequestMemo::class)->forget('mail.configured');

    expect(config('mail.default'))->toBe('log');

    app(MailManager::class);

    expect(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.example.test');
});
