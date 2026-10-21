<?php

use Illuminate\Support\Facades\File;
use Laravel\Passport\Passport;
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

it('reads the previous location until the migration command has run', function () {
    $legacyPath = seedLegacyKeys();

    app()->register(AdminApiServiceProvider::class, true);

    expect(Passport::keyPath('oauth-public.key'))->toBe($legacyPath.'/oauth-public.key');

    $this->artisan('unopim:passport:keys')->assertSuccessful();

    app()->register(AdminApiServiceProvider::class, true);

    expect(Passport::keyPath('oauth-public.key'))->toBe($this->keyPath.'/oauth-public.key');
});

it('never writes to the previous location', function () {
    seedLegacyKeys();

    $store = app(OauthKeyStore::class);

    expect($store->path())->toBe($this->keyPath)
        ->and($store->resolvedPath())->toBe($store->legacyPath());
});

it('adopts a keypair left behind by an earlier release', function () {
    $legacyPath = seedLegacyKeys();

    $legacyPublicKey = file_get_contents($legacyPath.'/oauth-public.key');

    $this->artisan('unopim:passport:keys')->assertSuccessful();

    expect(file_get_contents($this->keyPath.'/oauth-public.key'))->toBe($legacyPublicKey);
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
