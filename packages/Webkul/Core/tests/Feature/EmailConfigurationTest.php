<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\Core\Repositories\CoreConfigRepository;

it('registers the email settings section with SMTP fields in the configuration tree', function () {
    $section = collect(config('core'))->firstWhere('key', 'emails.configure.email_settings');

    expect($section)->not->toBeNull();

    $fields = collect($section['fields'])->pluck('name');

    expect($fields)->toContain('sender_name')
        ->and($fields)->toContain('shop_email_from')
        ->and($fields)->toContain('mail_host')
        ->and($fields)->toContain('mail_password')
        ->and($fields)->toContain('mail_encryption');
});

it('applies saved SMTP settings to the mailer transport', function () {
    // The environment already seeds default email settings, so update the existing
    // rows (matched on code) instead of creating duplicates that would shadow them.
    CoreConfig::updateOrCreate(['code' => 'emails.configure.email_settings.mail_host'], ['value' => 'smtp.example.test']);
    CoreConfig::updateOrCreate(['code' => 'emails.configure.email_settings.mail_port'], ['value' => '2525']);
    CoreConfig::updateOrCreate(['code' => 'emails.configure.email_settings.mail_encryption'], ['value' => 'ssl']);
    CoreConfig::updateOrCreate(['code' => 'emails.configure.email_settings.shop_email_from'], ['value' => 'noreply@example.test']);

    // The core-config repository caches lookups; flush so the just-seeded rows are read.
    Cache::flush();

    $provider = new CoreServiceProvider(app());

    $method = new ReflectionMethod($provider, 'overrideMailConfiguration');
    $method->setAccessible(true);
    $method->invoke($provider);

    expect(config('mail.mailers.smtp.host'))->toBe('smtp.example.test')
        ->and((string) config('mail.mailers.smtp.port'))->toBe('2525')
        ->and(config('mail.mailers.smtp.encryption'))->toBe('ssl')
        ->and(config('mail.from.address'))->toBe('noreply@example.test');
});

it('routes mail through the smtp transport once a host is configured in the admin', function () {
    config(['mail.default' => 'log']);

    CoreConfig::updateOrCreate(['code' => 'emails.configure.email_settings.mail_host'], ['value' => 'smtp.example.test']);

    Cache::flush();

    applyMailOverride();

    expect(config('mail.default'))->toBe('smtp');
});

it('leaves the environment mailer untouched when no host is configured in the admin', function () {
    CoreConfig::where('code', 'emails.configure.email_settings.mail_host')->delete();

    Cache::flush();

    config(['mail.default' => 'log']);

    applyMailOverride();

    expect(config('mail.default'))->toBe('log');
});

/**
 * A password field posts back a fixed-length asterisk mask, which never matches
 * the length of the stored secret. Treating only equal-length masks as "unchanged"
 * meant every re-save of the email settings overwrote the SMTP password with
 * literal asterisks, so mail worked once and then silently broke.
 */
it('keeps the stored password when the masked value is posted back', function (string $mask) {
    CoreConfig::updateOrCreate(
        ['code' => 'emails.configure.email_settings.mail_password'],
        ['value' => 'a-long-secret-password']
    );

    Cache::flush();

    app(CoreConfigRepository::class)->create([
        'emails' => ['configure' => ['email_settings' => ['mail_password' => $mask]]],
    ]);

    expect(CoreConfig::where('code', 'emails.configure.email_settings.mail_password')->value('value'))
        ->toBe('a-long-secret-password');
})->with(['****', '********', '************']);

it('stores a genuinely new password instead of treating it as unchanged', function () {
    CoreConfig::updateOrCreate(
        ['code' => 'emails.configure.email_settings.mail_password'],
        ['value' => 'a-long-secret-password']
    );

    Cache::flush();

    app(CoreConfigRepository::class)->create([
        'emails' => ['configure' => ['email_settings' => ['mail_password' => 'a-brand-new-secret']]],
    ]);

    expect(CoreConfig::where('code', 'emails.configure.email_settings.mail_password')->value('value'))
        ->toBe('a-brand-new-secret');
});

function applyMailOverride(): void
{
    $provider = new CoreServiceProvider(app());

    $method = new ReflectionMethod($provider, 'overrideMailConfiguration');
    $method->setAccessible(true);
    $method->invoke($provider);
}
