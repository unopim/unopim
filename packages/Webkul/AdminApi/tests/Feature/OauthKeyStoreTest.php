<?php

use Illuminate\Support\Facades\File;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use Webkul\AdminApi\Providers\AdminApiServiceProvider;
use Webkul\AdminApi\Services\OauthKeyStore;

beforeEach(function () {
    $this->keyPath = storage_path('framework/testing/oauth-'.uniqid());

    config()->set('api.oauth_key_path', $this->keyPath);
});

afterEach(function () {
    File::deleteDirectory($this->keyPath);

    if ($this->createdLegacyKeys ?? false) {
        $legacyPath = (new OauthKeyStore)->legacyPath();

        File::delete([
            $legacyPath.'/oauth-private.key',
            $legacyPath.'/oauth-public.key',
        ]);
    }
});

/**
 * Puts a keypair at the location releases before the move wrote to, leaving a
 * developer's real keys alone when they are already there.
 */
function seedLegacyKeys(): string
{
    if (! is_dir(OauthKeyStore::LEGACY_PATH)) {
        File::makeDirectory(OauthKeyStore::LEGACY_PATH, 0755, true);
    }

    $legacyPath = (new OauthKeyStore)->legacyPath();

    if (! file_exists($legacyPath.'/oauth-private.key')) {
        test()->createdLegacyKeys = true;

        config()->set('api.oauth_key_path', $legacyPath);

        (new OauthKeyStore)->generate();

        config()->set('api.oauth_key_path', test()->keyPath);
    }

    return $legacyPath;
}

it('keeps the signing keys on the storage volume by default', function () {
    config()->set('api.oauth_key_path', null);

    expect(app(OauthKeyStore::class)->path())->toBe(storage_path('app/private/oauth'));
});

it('survives the application code being replaced', function () {
    $this->artisan('unopim:passport:keys')->assertSuccessful();

    $publicKey = file_get_contents($this->keyPath.'/oauth-public.key');

    $this->artisan('unopim:passport:keys')->assertSuccessful();

    expect(file_get_contents($this->keyPath.'/oauth-public.key'))->toBe($publicKey);
});

it('points passport at the resolved key path', function () {
    $this->artisan('unopim:passport:keys')->assertSuccessful();

    app()->register(AdminApiServiceProvider::class, true);

    expect(Passport::keyPath('oauth-public.key'))->toBe($this->keyPath.'/oauth-public.key');
});

it('never signs with a keypair left at the previous location', function (): void {
    seedLegacyKeys();

    app()->register(AdminApiServiceProvider::class, true);

    expect(Passport::keyPath('oauth-public.key'))->toBe($this->keyPath.'/oauth-public.key')
        ->and(app(OauthKeyStore::class)->resolvedPath())->toBe($this->keyPath);
});

it('refuses to build the oauth servers until the keys exist', function (string $server): void {
    seedLegacyKeys();

    app()->register(AdminApiServiceProvider::class, true);

    app()->forgetInstance($server);

    expect(fn (): object => app($server))->toThrow(RuntimeException::class, trans('admin::app.configuration.integrations.oauth-keys-missing'));
})->with([AuthorizationServer::class, ResourceServer::class]);

it('generates a fresh keypair instead of adopting one from the previous location', function (): void {
    $legacyPath = seedLegacyKeys();

    $legacyPublicKey = file_get_contents($legacyPath.'/oauth-public.key');

    $this->artisan('unopim:passport:keys')->assertSuccessful();

    expect(file_get_contents($this->keyPath.'/oauth-public.key'))->not->toBe($legacyPublicKey)
        ->and(file_get_contents($legacyPath.'/oauth-public.key'))->toBe($legacyPublicKey);
});

/**
 * Writes a keypair at the configured path and binds a key store that treats it as revoked.
 */
function revokeStoredKeys(): string
{
    (new OauthKeyStore)->generate();

    $publicKey = (string) file_get_contents(test()->keyPath.'/oauth-public.key');
    $fingerprint = hash('sha256', (string) preg_replace('/\s+/', '', $publicKey));

    app()->instance(OauthKeyStore::class, new class($fingerprint) extends OauthKeyStore
    {
        public function __construct(private string $fingerprint) {}

        protected function revokedPublicKeys(): array
        {
            return [$this->fingerprint];
        }
    });

    return $publicKey;
}

it('refuses to build the oauth servers with a revoked keypair', function (string $server): void {
    File::makeDirectory($this->keyPath, 0700, true);

    revokeStoredKeys();

    app()->register(AdminApiServiceProvider::class, true);

    app()->forgetInstance($server);

    expect(fn (): object => app($server))->toThrow(RuntimeException::class, trans('admin::app.configuration.integrations.oauth-keys-missing'));
})->with([AuthorizationServer::class, ResourceServer::class]);

it('replaces a revoked keypair', function (): void {
    File::makeDirectory($this->keyPath, 0700, true);

    $revokedPublicKey = revokeStoredKeys();

    $this->artisan('unopim:passport:keys')->assertSuccessful();

    expect(file_get_contents($this->keyPath.'/oauth-public.key'))->not->toBe($revokedPublicKey)
        ->and(app(OauthKeyStore::class)->isRevoked())->toBeFalse();
});

it('reports a failure instead of throwing when the key path is unwritable', function () {
    $readOnly = storage_path('framework/testing/oauth-readonly-'.uniqid());

    File::makeDirectory($readOnly, 0500, true);

    config()->set('api.oauth_key_path', $readOnly.'/keys');

    $this->artisan('unopim:passport:keys')->assertFailed();

    chmod($readOnly, 0700);

    File::deleteDirectory($readOnly);
})->skip(fn () => posix_geteuid() === 0, 'root ignores directory permissions');

it('does not write to disk while booting the provider', function () {
    $this->artisan('unopim:passport:keys')->assertSuccessful();

    $before = File::allFiles($this->keyPath);

    File::delete($this->keyPath.'/oauth-private.key');

    app()->register(AdminApiServiceProvider::class, true);

    expect(File::exists($this->keyPath.'/oauth-private.key'))->toBeFalse()
        ->and($before)->toHaveCount(2);
});
