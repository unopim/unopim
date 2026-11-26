<?php

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\RequestMemo;

function passwordConfigCode(): string
{
    return 'emails.configure.email_settings.mail_password';
}

function storedPasswordConfig(): ?string
{
    return CoreConfig::query()->where('code', passwordConfigCode())->value('value');
}

function readPasswordConfig(): mixed
{
    resolve(RequestMemo::class)->forget('core_config.');

    return core()->getConfigData(passwordConfigCode());
}

function encryptPasswordConfigMigration(): object
{
    return require base_path('packages/Webkul/Core/src/Database/Migrations/2026_11_26_000000_encrypt_password_config_values.php');
}

it('stores a password-type setting encrypted and reads it back in plain text', function (): void {
    app(CoreConfigRepository::class)->create([
        'emails' => ['configure' => ['email_settings' => ['mail_password' => 'smtp-secret']]],
    ]);

    expect(storedPasswordConfig())->not->toBe('smtp-secret')
        ->and(Crypt::decryptString(storedPasswordConfig()))->toBe('smtp-secret')
        ->and(readPasswordConfig())->toBe('smtp-secret');
});

it('keeps an empty password-type setting empty', function (): void {
    app(CoreConfigRepository::class)->create([
        'emails' => ['configure' => ['email_settings' => ['mail_password' => '']]],
    ]);

    expect(storedPasswordConfig())->toBe('')
        ->and(readPasswordConfig())->toBe('');
});

it('reads a password-type setting stored before encryption as it is', function (): void {
    CoreConfig::query()->updateOrCreate(['code' => passwordConfigCode()], ['value' => 'legacy-plain-secret']);

    expect(readPasswordConfig())->toBe('legacy-plain-secret');
});

it('reads a payload encrypted with another application key as empty', function (): void {
    $foreign = (new Encrypter(random_bytes(32), 'aes-256-cbc'))->encryptString('other-key-secret');

    CoreConfig::query()->updateOrCreate(['code' => passwordConfigCode()], ['value' => $foreign]);

    expect(readPasswordConfig())->toBe('');
});

it('encrypts plain password-type settings on migrate and decrypts them on rollback', function (): void {
    CoreConfig::query()->updateOrCreate(['code' => passwordConfigCode()], ['value' => 'legacy-plain-secret']);

    $text = CoreConfig::query()->updateOrCreate(['code' => 'emails.configure.email_settings.mail_host'], ['value' => 'smtp.example.test']);

    $migration = encryptPasswordConfigMigration();

    $migration->up();

    $encrypted = storedPasswordConfig();

    $migration->up();

    expect(Crypt::decryptString($encrypted))->toBe('legacy-plain-secret')
        ->and(storedPasswordConfig())->toBe($encrypted)
        ->and($text->fresh()->value)->toBe('smtp.example.test');

    $migration->down();

    expect(storedPasswordConfig())->toBe('legacy-plain-secret');
});
