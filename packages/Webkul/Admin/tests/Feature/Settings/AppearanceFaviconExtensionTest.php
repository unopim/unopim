<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Requests\AppearanceForm;
use Webkul\Core\Models\CoreConfig;

it('constrains the appearance upload controls to the extensions the server accepts', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.appearance.index'));

    $response->assertOk();

    preg_match_all('/<v-media-image\s[^>]*>/s', $response->getContent(), $tags);

    $advertised = collect($tags[0])
        ->filter(fn ($tag) => preg_match('/name="(logo_image|favicon)"/', $tag))
        ->mapWithKeys(function ($tag) {
            preg_match('/name="(logo_image|favicon)"/', $tag, $name);
            preg_match('/:accepted-extensions=\'([^\']*)\'/', $tag, $extensions);

            return [$name[1] => json_decode(html_entity_decode($extensions[1] ?? ''), true)];
        });

    expect($advertised->get('favicon'))->toBe(AppearanceForm::FAVICON_EXTENSIONS)
        ->and($advertised->get('logo_image'))->toBe(AppearanceForm::LOGO_EXTENSIONS);
});

it('stores a jpeg favicon submitted by the media component', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    $this->put(route('admin.settings.appearance.update'), [
        'favicon' => [UploadedFile::fake()->image('watch.jpeg', 16, 16)],
    ])->assertSessionHasNoErrors();

    $path = CoreConfig::query()
        ->where('code', 'general.design.admin_logo.favicon')
        ->value('value');

    expect($path)->not->toBeEmpty();

    Storage::assertExists($path);
});

it('stores a png favicon submitted by the media component', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    $this->put(route('admin.settings.appearance.update'), [
        'favicon' => [UploadedFile::fake()->image('favicon.png', 16, 16)],
    ])->assertSessionHasNoErrors();

    expect(CoreConfig::query()->where('code', 'general.design.admin_logo.favicon')->value('value'))
        ->not->toBeEmpty();
});

it('rejects a favicon whose type is outside the supported list', function () {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    $existing = CoreConfig::query()->where('code', 'general.design.admin_logo.favicon')->value('value');

    $this->put(route('admin.settings.appearance.update'), [
        'favicon' => [UploadedFile::fake()->create('favicon.svg', 4, 'image/svg+xml')],
    ])->assertSessionHasErrors('favicon.0');

    expect(CoreConfig::query()->where('code', 'general.design.admin_logo.favicon')->value('value'))
        ->toBe($existing);
});
