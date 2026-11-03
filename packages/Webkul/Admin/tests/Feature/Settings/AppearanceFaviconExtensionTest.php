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

it('advertises the server size limits to the appearance upload controls', function () {
    $this->loginAsAdmin();

    $content = $this->get(route('admin.settings.appearance.index'))->assertOk()->getContent();

    expect($content)
        ->toMatch('/<v-media-image\s[^>]*name="logo_image"[^>]*v-bind:max-size="'.AppearanceForm::LOGO_MAX_SIZE.'"/s')
        ->toMatch('/<v-media-image\s[^>]*name="favicon"[^>]*v-bind:max-size="'.AppearanceForm::FAVICON_MAX_SIZE.'"/s');
});

it('rejects appearance images larger than the allowed size and shows the error', function (string $field, string $code, int $maxSize, string $label) {
    Storage::fake(config('filesystems.default'));

    $this->loginAsAdmin();

    $existing = CoreConfig::query()->where('code', $code)->value('value');

    $this->put(route('admin.settings.appearance.update'), [
        $field => [UploadedFile::fake()->image('large.png', 16, 16)->size($maxSize + 1)],
    ])->assertSessionHasErrors($field.'.0');

    expect(CoreConfig::query()->where('code', $code)->value('value'))->toBe($existing);

    $this->followingRedirects()
        ->from(route('admin.settings.appearance.index'))
        ->put(route('admin.settings.appearance.update'), [
            $field => [UploadedFile::fake()->image('large.png', 16, 16)->size($maxSize + 1)],
        ])
        ->assertSee('<p class="mt-2 text-red-600 text-xs italic">'.e(trans('validation.max.file', ['attribute' => $label, 'max' => $maxSize])), false);
})->with([
    'logo'    => ['logo_image', 'general.design.admin_logo.logo_image', AppearanceForm::LOGO_MAX_SIZE, 'Logo'],
    'favicon' => ['favicon', 'general.design.admin_logo.favicon', AppearanceForm::FAVICON_MAX_SIZE, 'Favicon'],
]);
