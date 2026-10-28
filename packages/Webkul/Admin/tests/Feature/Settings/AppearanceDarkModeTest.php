<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Requests\AppearanceForm;
use Webkul\Core\Models\CoreConfig;

function storeAppearanceConfig(string $field, string $value): void
{
    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.design.admin_logo.'.$field, 'channel_code' => null, 'locale_code' => null],
        ['value' => $value],
    );
}

function appearanceConfigValue(string $field): ?string
{
    return CoreConfig::query()->where('code', 'general.design.admin_logo.'.$field)->value('value');
}

it('stores an uploaded dark mode logo and favicon without touching the light ones', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    storeAppearanceConfig('logo_image', 'configuration/light-logo.png');

    $this->put(route('admin.settings.appearance.update'), [
        'logo_image'      => 'configuration/light-logo.png',
        'logo_image_dark' => [UploadedFile::fake()->image('dark-logo.png', 192, 50)],
        'favicon_dark'    => [UploadedFile::fake()->image('dark-favicon.png', 16, 16)],
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.system.index'));

    expect(appearanceConfigValue('logo_image'))->toBe('configuration/light-logo.png')
        ->and(appearanceConfigValue('logo_image_dark'))->toMatch('/^configuration\/.+\/[A-Za-z0-9]{40}\.png$/')
        ->and(appearanceConfigValue('favicon_dark'))->toMatch('/^configuration\/.+\/[A-Za-z0-9]{40}\.png$/')
        ->and(appearanceConfigValue('favicon'))->toBeNull();

    Storage::assertExists(appearanceConfigValue('logo_image_dark'));
    Storage::assertExists(appearanceConfigValue('favicon_dark'));
});

it('removes the dark mode logo and favicon and their stored files', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    Storage::put('configuration/dark-logo.png', 'binary');
    Storage::put('configuration/dark-favicon.png', 'binary');

    storeAppearanceConfig('logo_image_dark', 'configuration/dark-logo.png');
    storeAppearanceConfig('favicon_dark', 'configuration/dark-favicon.png');

    $this->put(route('admin.settings.appearance.update'), [])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.system.index'));

    expect(appearanceConfigValue('logo_image_dark'))->toBeNull()
        ->and(appearanceConfigValue('favicon_dark'))->toBeNull();

    Storage::assertMissing('configuration/dark-logo.png');
    Storage::assertMissing('configuration/dark-favicon.png');
});

it('keeps the dark mode logo when it is resubmitted unchanged', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    storeAppearanceConfig('logo_image_dark', 'configuration/dark-logo.png');

    $this->put(route('admin.settings.appearance.update'), [
        'logo_image_dark' => 'configuration/dark-logo.png',
    ])->assertSessionHasNoErrors();

    expect(appearanceConfigValue('logo_image_dark'))->toBe('configuration/dark-logo.png');
});

it('rejects invalid dark mode logo and favicon uploads', function (string $field, UploadedFile $file) {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    $this->put(route('admin.settings.appearance.update'), [$field => [$file]])
        ->assertSessionHasErrors($field.'.0');

    $this->put(route('admin.settings.appearance.update'), [$field => $file])
        ->assertSessionHasErrors($field);

    expect(appearanceConfigValue($field))->toBeNull();
})->with([
    'php logo'           => fn () => ['logo_image_dark', UploadedFile::fake()->create('malware.php', 10, 'application/x-php')],
    'html-named logo'    => fn () => ['logo_image_dark', UploadedFile::fake()->image('payload.html')],
    'svg logo'           => fn () => ['logo_image_dark', UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml')],
    'php favicon'        => fn () => ['favicon_dark', UploadedFile::fake()->create('malware.php', 10, 'application/x-php')],
    'svg favicon'        => fn () => ['favicon_dark', UploadedFile::fake()->create('favicon.svg', 4, 'image/svg+xml')],
]);

it('offers dark mode logo and favicon uploads constrained to the accepted extensions', function () {
    $this->loginAsAdmin();

    $content = $this->get(route('admin.settings.appearance.index'))
        ->assertOk()
        ->assertSee(trans('admin::app.settings.appearance.dark-logo'))
        ->assertSee(trans('admin::app.settings.appearance.dark-favicon'))
        ->getContent();

    preg_match_all('/<v-media-image\s[^>]*>/s', $content, $tags);

    $advertised = collect($tags[0])
        ->mapWithKeys(function ($tag) {
            preg_match('/name="([a-z_]+)"/', $tag, $name);
            preg_match('/:accepted-extensions=\'([^\']*)\'/', $tag, $extensions);

            return [$name[1] => json_decode(html_entity_decode($extensions[1] ?? ''), true)];
        });

    expect($advertised->get('logo_image_dark'))->toBe(AppearanceForm::LOGO_EXTENSIONS)
        ->and($advertised->get('favicon_dark'))->toBe(AppearanceForm::FAVICON_EXTENSIONS);
});

it('swaps the custom light and dark logos with the theme in the header and on the login page', function () {
    storeAppearanceConfig('logo_image', 'configuration/light-logo.png');
    storeAppearanceConfig('logo_image_dark', 'configuration/dark-logo.png');

    $light = preg_quote(Storage::url('configuration/light-logo.png'), '/');
    $dark = preg_quote(Storage::url('configuration/dark-logo.png'), '/');

    $login = $this->get(route('admin.session.create'))->assertOk()->getContent();

    expect($login)->toMatch('/class="[^"]*dark:hidden[^"]*"\s+src="'.$light.'"/')
        ->toMatch('/class="[^"]*hidden dark:block[^"]*"\s+src="'.$dark.'"/')
        ->not->toContain('dark_logo');

    $this->loginAsAdmin();

    $dashboard = $this->get(route('admin.dashboard.index'))->assertOk()->getContent();

    expect(substr_count($dashboard, 'src="'.Storage::url('configuration/dark-logo.png').'"'))->toBe(2)
        ->and($dashboard)->toMatch('/class="[^"]*hidden dark:block[^"]*"\s+src="'.$dark.'"/');
});

it('falls back to the custom light logo in dark mode when no dark logo is set', function () {
    storeAppearanceConfig('logo_image', 'configuration/light-logo.png');

    $url = Storage::url('configuration/light-logo.png');

    $login = $this->get(route('admin.session.create'))->assertOk()->getContent();

    expect(substr_count($login, 'src="'.$url.'"'))->toBe(1)
        ->and($login)->not->toContain('dark_logo')
        ->and($login)->toMatch('/class="h-10"\s+src="'.preg_quote($url, '/').'"/');
});

it('keeps the default logo swap when no custom logo is set', function () {
    $login = $this->get(route('admin.session.create'))->assertOk()->getContent();

    expect($login)->toMatch('/class="[^"]*block dark:hidden[^"]*"\s+src="[^"]*\/logo[^"]*\.svg"/')
        ->toMatch('/class="[^"]*hidden dark:block[^"]*"\s+src="[^"]*\/dark_logo[^"]*\.svg"/');
});

it('shows a dark logo with the default light logo when only the dark logo is set', function () {
    storeAppearanceConfig('logo_image_dark', 'configuration/dark-logo.png');

    $login = $this->get(route('admin.session.create'))->assertOk()->getContent();

    expect($login)->toMatch('/class="[^"]*dark:hidden[^"]*"\s+src="[^"]*\/logo[^"]*\.svg"/')
        ->toMatch('/class="[^"]*hidden dark:block[^"]*"\s+src="'.preg_quote(Storage::url('configuration/dark-logo.png'), '/').'"/')
        ->not->toContain('dark_logo');
});

it('serves the dark mode favicon to dark colour schemes', function () {
    storeAppearanceConfig('favicon', 'configuration/favicon.png');
    storeAppearanceConfig('favicon_dark', 'configuration/favicon-dark.png');

    $light = preg_quote(Storage::url('configuration/favicon.png'), '/');
    $dark = preg_quote(Storage::url('configuration/favicon-dark.png'), '/');

    $login = $this->get(route('admin.session.create'))->assertOk()->getContent();

    expect($login)->toMatch('/href="'.$light.'"[^>]*media="\(prefers-color-scheme: light\)"/')
        ->toMatch('/href="'.$dark.'"[^>]*media="\(prefers-color-scheme: dark\)"/');

    $this->loginAsAdmin();

    expect($this->get(route('admin.dashboard.index'))->assertOk()->getContent())
        ->toMatch('/href="'.$dark.'"[^>]*media="\(prefers-color-scheme: dark\)"/');
});

it('serves a single favicon for every colour scheme when no dark favicon is set', function () {
    storeAppearanceConfig('favicon', 'configuration/favicon.png');

    $login = $this->get(route('admin.session.create'))->assertOk()->getContent();

    expect($login)->toContain('href="'.Storage::url('configuration/favicon.png').'"')
        ->not->toContain('prefers-color-scheme: dark)"');
});
