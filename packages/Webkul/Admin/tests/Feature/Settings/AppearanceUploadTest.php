<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\CoreConfig;

it('stores an uploaded logo submitted by the media component into core config', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    $this->put(route('admin.settings.appearance.update'), [
        'logo_image' => [UploadedFile::fake()->image('logo.png', 192, 50)],
    ])->assertRedirect(route('admin.settings.system.index'));

    $config = CoreConfig::query()
        ->where('code', 'general.design.admin_logo.logo_image')
        ->first();

    expect($config)->not->toBeNull()
        ->and($config->value)->not->toBeEmpty();
});

it('invalidates the cached core config so an uploaded logo reflects immediately instead of serving the stale value', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    Storage::put('configuration/old/logo.png', 'binary');

    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.design.admin_logo.logo_image', 'channel_code' => null, 'locale_code' => null],
        ['value' => 'configuration/old/logo.png'],
    );

    // Prime the cached repository read with the current (soon-to-be-stale) value.
    expect(core()->getConfigData('general.design.admin_logo.logo_image'))
        ->toBe('configuration/old/logo.png');

    $this->put(route('admin.settings.appearance.update'), [
        'logo_image' => [UploadedFile::fake()->image('logo.png', 192, 50)],
    ])->assertRedirect(route('admin.settings.system.index'));

    $stored = CoreConfig::query()
        ->where('code', 'general.design.admin_logo.logo_image')
        ->first()->value;

    expect($stored)->not->toBe('configuration/old/logo.png')
        ->and(core()->getConfigData('general.design.admin_logo.logo_image'))->toBe($stored);
});

it('stores a replacement logo under a unique path so the browser never serves a stale cached image', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    $this->put(route('admin.settings.appearance.update'), [
        'logo_image' => [UploadedFile::fake()->image('logo.png', 192, 50)],
    ])->assertRedirect(route('admin.settings.system.index'));

    $firstPath = CoreConfig::query()
        ->where('code', 'general.design.admin_logo.logo_image')
        ->first()->value;

    $this->put(route('admin.settings.appearance.update'), [
        'logo_image' => [UploadedFile::fake()->image('logo.png', 192, 50)],
    ])->assertRedirect(route('admin.settings.system.index'));

    $secondPath = CoreConfig::query()
        ->where('code', 'general.design.admin_logo.logo_image')
        ->first()->value;

    expect($secondPath)->not->toBe($firstPath);

    Storage::assertMissing($firstPath);
    Storage::assertExists($secondPath);
});

it('keeps the existing logo when the media component resubmits it as an unchanged string path', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.design.admin_logo.logo_image', 'channel_code' => null, 'locale_code' => null],
        ['value' => 'configuration/existing-logo.png'],
    );

    $this->put(route('admin.settings.appearance.update'), [
        'logo_image' => 'configuration/existing-logo.png',
        'favicon'    => [UploadedFile::fake()->image('favicon.png', 16, 16)],
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.system.index'));

    expect(CoreConfig::query()->where('code', 'general.design.admin_logo.logo_image')->first()->value)
        ->toBe('configuration/existing-logo.png');

    expect(CoreConfig::query()->where('code', 'general.design.admin_logo.favicon')->first()->value)
        ->not->toBeEmpty();
});

it('clears the logo config and stored file when the image is removed, reverting to the default', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    Storage::put('configuration/existing-logo.png', 'binary');

    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.design.admin_logo.logo_image', 'channel_code' => null, 'locale_code' => null],
        ['value' => 'configuration/existing-logo.png'],
    );

    $this->put(route('admin.settings.appearance.update'), [])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.system.index'));

    expect(CoreConfig::query()->where('code', 'general.design.admin_logo.logo_image')->exists())->toBeFalse();

    Storage::assertMissing('configuration/existing-logo.png');
});

it('rejects a non-image logo upload', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    $this->put(route('admin.settings.appearance.update'), [
        'logo_image' => [UploadedFile::fake()->create('malware.php', 10, 'application/x-php')],
    ])->assertSessionHasErrors('logo_image.0');
});
