<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Webkul\AdminApi\Http\Controllers\API\Catalog\MediaFileController;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

beforeEach(function (): void {
    $this->headers = $this->getAuthenticationHeaders();

    Storage::fake();
    Storage::fake('public');

    $this->product = Product::factory()->create();
});

/**
 * Create a media attribute of the given type and scope.
 */
function apiGalleryMediaAttribute(string $type, bool $perChannel = false, bool $perLocale = false): Attribute
{
    return Attribute::factory()->create([
        'type'              => $type,
        'value_per_channel' => $perChannel,
        'value_per_locale'  => $perLocale,
    ]);
}

/**
 * Upload one or more files to a product media attribute through the REST API.
 *
 * @param  array<int, string>  $fileNames
 * @param  array<string, string>  $scope
 */
function apiGalleryUploadMedia(string $sku, string $attributeCode, array $fileNames, array $scope = []): TestResponse
{
    $files = array_map(fn (string $name): UploadedFile => UploadedFile::fake()->image($name), $fileNames);

    return test()->withHeaders(test()->headers)->post(route('admin.api.media-files.product.store'), [
        'sku'       => $sku,
        'attribute' => $attributeCode,
        'file'      => count($files) === 1 ? $files[0] : $files,
        ...$scope,
    ])->assertOk();
}

/**
 * Store a product value directly, the way the admin form or an earlier release saved it.
 */
function apiGalleryStoreValue(Product $product, string $attributeCode, mixed $value): void
{
    $product->values = array_merge($product->values ?? [], ['common' => [...($product->values['common'] ?? []), $attributeCode => $value]]);
    $product->save();
}

/**
 * The file names of gallery paths, after checking each one sits in its own hashed folder under the base path.
 *
 * @param  array<int, string>  $paths
 * @return array<int, string>
 */
function apiGalleryUploadedNames(array $paths, string $base): array
{
    foreach ($paths as $path) {
        expect($path)->toMatch('#^'.preg_quote($base, '#').'/[A-Za-z0-9]{40}/[^/]+$#');
    }

    return array_map(basename(...), $paths);
}

it('keeps every image when gallery images are uploaded one request at a time', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['first.png']);
    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['second.png']);

    $base = 'product/'.$this->product->id.'/'.$gallery->code;

    expect(apiGalleryUploadedNames($this->product->fresh()->values['common'][$gallery->code], $base))->toBe(['first.png', 'second.png']);
});

it('appends an upload to a gallery saved as a list by the admin form', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryStoreValue($this->product, $gallery->code, ['product/existing/one.png', 'product/existing/two.png']);

    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['third.png']);

    $stored = $this->product->fresh()->values['common'][$gallery->code];

    expect(array_slice($stored, 0, 2))->toBe(['product/existing/one.png', 'product/existing/two.png'])
        ->and(apiGalleryUploadedNames(array_slice($stored, 2), 'product/'.$this->product->id.'/'.$gallery->code))->toBe(['third.png']);
});

it('converts a comma separated gallery written by an earlier release into a list when appending', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryStoreValue($this->product, $gallery->code, 'product/legacy/one.png,product/legacy/two.png');

    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['three.png']);

    $stored = $this->product->fresh()->values['common'][$gallery->code];

    expect(array_slice($stored, 0, 2))->toBe(['product/legacy/one.png', 'product/legacy/two.png'])
        ->and(apiGalleryUploadedNames(array_slice($stored, 2), 'product/'.$this->product->id.'/'.$gallery->code))->toBe(['three.png']);
});

it('appends every file of a multi file upload to the existing gallery', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryStoreValue($this->product, $gallery->code, ['product/existing/one.png']);

    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['two.png', 'three.png']);

    $stored = $this->product->fresh()->values['common'][$gallery->code];

    expect($stored[0])->toBe('product/existing/one.png')
        ->and(apiGalleryUploadedNames(array_slice($stored, 1), 'product/'.$this->product->id.'/'.$gallery->code))->toBe(['two.png', 'three.png']);
});

it('keeps two different files uploaded under the same name as two gallery images', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['same.png']);
    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['same.png']);

    $stored = $this->product->fresh()->values['common'][$gallery->code];

    expect(apiGalleryUploadedNames($stored, 'product/'.$this->product->id.'/'.$gallery->code))->toBe(['same.png', 'same.png'])
        ->and($stored[0])->not->toBe($stored[1]);

    Storage::assertExists($stored);
});

it('does not list the same gallery path twice', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryStoreValue($this->product, $gallery->code, ['product/existing/one.png', 'product/existing/one.png']);

    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['two.png']);

    $stored = $this->product->fresh()->values['common'][$gallery->code];

    expect($stored)->toHaveCount(2)
        ->and($stored[0])->toBe('product/existing/one.png');
});

it('appends gallery uploads at the scope the attribute is stored in', function (bool $perChannel, bool $perLocale, string $scopePath): void {
    $gallery = apiGalleryMediaAttribute('gallery', $perChannel, $perLocale);

    $channel = core()->getDefaultChannelCode();
    $locale = core()->getDefaultLocaleCodeFromDefaultChannel();
    $scope = ['channel' => $channel, 'locale' => $locale];

    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['first.png'], $scope);
    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['second.png'], $scope);

    $base = 'product/'.$this->product->id.'/'.$gallery->code;
    $values = $this->product->fresh()->values;

    expect(apiGalleryUploadedNames(data_get($values, [...explode('.', strtr($scopePath, ['{channel}' => $channel, '{locale}' => $locale])), $gallery->code]), $base))->toBe(['first.png', 'second.png'])
        ->and($values['common'][$gallery->code] ?? null)->toBeNull();
})->with([
    'per channel and locale' => [true, true, 'channel_locale_specific.{channel}.{locale}'],
    'per channel'            => [true, false, 'channel_specific.{channel}'],
    'per locale'             => [false, true, 'locale_specific.{locale}'],
]);

it('rejects a channel or locale that does not exist without touching the product', function (string $field, string $code): void {
    $gallery = apiGalleryMediaAttribute('gallery', perChannel: true, perLocale: true);

    $valuesBefore = $this->product->fresh()->values;

    $this->withHeaders($this->headers)->post(route('admin.api.media-files.product.store'), [
        'sku'       => $this->product->sku,
        'attribute' => $gallery->code,
        'file'      => UploadedFile::fake()->image('first.png'),
        $field      => $code,
    ])->assertUnprocessable()->assertJsonValidationErrors([$field]);

    expect($this->product->fresh()->values)->toBe($valuesBefore);
})->with([
    'channel path'   => ['channel', 'default.sku'],
    'unknown locale' => ['locale', 'xx_XX'],
]);

it('still replaces the value of an image attribute on a new upload', function (): void {
    $image = apiGalleryMediaAttribute('image');

    apiGalleryUploadMedia($this->product->sku, $image->code, ['first.png']);
    apiGalleryUploadMedia($this->product->sku, $image->code, ['second.png']);

    expect($this->product->fresh()->values['common'][$image->code])->toBe('product/'.$this->product->id.'/'.$image->code.'/second.png');
});

it('returns every path of a gallery saved as a list', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryStoreValue($this->product, $gallery->code, ['product/existing/one.png', 'product/existing/two.png']);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.media-files.product.get', ['sku' => $this->product->sku, 'attribute' => $gallery->code]))
        ->assertOk()
        ->assertExactJson(['data' => ['product/existing/one.png', 'product/existing/two.png']]);
});

it('deletes every file of a gallery saved as a list', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    $paths = ['product/existing/one.png', 'product/existing/two.png'];

    foreach ($paths as $path) {
        Storage::put($path, 'x');
    }

    apiGalleryStoreValue($this->product, $gallery->code, $paths);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.product.delete', ['sku' => $this->product->sku, 'attribute' => $gallery->code]))
        ->assertOk();

    Storage::assertMissing($paths[0]);
    Storage::assertMissing($paths[1]);

    expect($this->product->fresh()->values['common'][$gallery->code] ?? null)->toBeNull();
});

it('refuses an upload that would take a gallery past its file limit and keeps the gallery', function (): void {
    config(['media.gallery.max_files' => 2]);

    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryStoreValue($this->product, $gallery->code, ['product/existing/one.png', 'product/existing/two.png']);

    $this->withHeaders($this->headers)->post(route('admin.api.media-files.product.store'), [
        'sku'       => $this->product->sku,
        'attribute' => $gallery->code,
        'file'      => UploadedFile::fake()->image('three.png'),
    ])->assertUnprocessable();

    expect($this->product->fresh()->values['common'][$gallery->code])->toBe(['product/existing/one.png', 'product/existing/two.png'])
        ->and(Storage::allFiles('product/'.$this->product->id))->toBe([]);
});

it('accepts an upload that fills a gallery exactly to its file limit', function (): void {
    config(['media.gallery.max_files' => 2]);

    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryStoreValue($this->product, $gallery->code, ['product/existing/one.png']);

    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['two.png']);

    expect($this->product->fresh()->values['common'][$gallery->code])->toHaveCount(2);
});

it('refuses an upload that would take a gallery past its total size limit', function (): void {
    config(['media.gallery.max_total_size_kilobytes' => 10]);

    $gallery = apiGalleryMediaAttribute('gallery');

    Storage::put('product/existing/large.png', str_repeat('x', 10 * 1024));
    apiGalleryStoreValue($this->product, $gallery->code, ['product/existing/large.png']);

    $this->withHeaders($this->headers)->post(route('admin.api.media-files.product.store'), [
        'sku'       => $this->product->sku,
        'attribute' => $gallery->code,
        'file'      => UploadedFile::fake()->image('two.png'),
    ])->assertUnprocessable();

    expect($this->product->fresh()->values['common'][$gallery->code])->toBe(['product/existing/large.png'])
        ->and(Storage::allFiles('product/'.$this->product->id))->toBe([]);
});

it('merges into the gallery stored now, not the one the request loaded', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    $loaded = Product::query()->findOrFail($this->product->id);

    apiGalleryStoreValue($this->product, $gallery->code, ['product/existing/one.png']);

    $controller = app(MediaFileController::class);

    (new ReflectionMethod($controller, 'assignMediaToProductAttribute'))->invoke($controller, $loaded, $gallery->code, 'product/'.$this->product->id.'/'.$gallery->code.'/two.png');

    expect($this->product->fresh()->values['common'][$gallery->code])->toBe([
        'product/existing/one.png',
        'product/'.$this->product->id.'/'.$gallery->code.'/two.png',
    ]);
});

it('rejects a locale the channel does not hold for a channel and locale scoped attribute', function (string $method, string $route): void {
    $gallery = apiGalleryMediaAttribute('gallery', perChannel: true, perLocale: true);
    $locale = Locale::factory()->create(['status' => 1]);

    $payload = ['sku' => $this->product->sku, 'attribute' => $gallery->code, 'channel' => core()->getDefaultChannelCode(), 'locale' => $locale->code];

    if ($method === 'POST') {
        $payload['file'] = UploadedFile::fake()->image('first.png');
    }

    $this->withHeaders($this->headers)->json($method, route($route), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['locale']);
})->with([
    'upload' => ['POST', 'admin.api.media-files.product.store'],
    'get'    => ['GET', 'admin.api.media-files.product.get'],
    'delete' => ['DELETE', 'admin.api.media-files.product.delete'],
]);

it('accepts a locale outside the channel for an attribute stored per locale only', function (): void {
    $gallery = apiGalleryMediaAttribute('gallery', perLocale: true);
    $locale = Locale::factory()->create(['status' => 1]);

    apiGalleryUploadMedia($this->product->sku, $gallery->code, ['first.png'], ['locale' => $locale->code]);

    expect($this->product->fresh()->values['locale_specific'][$locale->code][$gallery->code])->toHaveCount(1);
});

it('rejects an unknown channel or locale when reading or deleting media', function (string $method, string $route, string $field, string $code): void {
    $gallery = apiGalleryMediaAttribute('gallery');

    apiGalleryStoreValue($this->product, $gallery->code, ['product/existing/one.png']);

    $this->withHeaders($this->headers)
        ->json($method, route($route, ['sku' => $this->product->sku, 'attribute' => $gallery->code, $field => $code]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);

    expect($this->product->fresh()->values['common'][$gallery->code])->toBe(['product/existing/one.png']);
})->with([
    'get unknown channel'    => ['GET', 'admin.api.media-files.product.get', 'channel', 'default.sku'],
    'get unknown locale'     => ['GET', 'admin.api.media-files.product.get', 'locale', 'xx_XX'],
    'delete unknown channel' => ['DELETE', 'admin.api.media-files.product.delete', 'channel', 'default.sku'],
    'delete unknown locale'  => ['DELETE', 'admin.api.media-files.product.delete', 'locale', 'xx_XX'],
]);
