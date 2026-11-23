<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Webkul\Attribute\Models\Attribute;
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
function createMediaAttribute(string $type, bool $perChannel = false, bool $perLocale = false): Attribute
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
 * @param  array<string, string>  $scope
 */
function uploadProductMedia(string $sku, string $attributeCode, array $fileNames, array $scope = []): TestResponse
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
function storeProductValue(Product $product, string $attributeCode, mixed $value): void
{
    $product->values = array_merge($product->values ?? [], ['common' => [...($product->values['common'] ?? []), $attributeCode => $value]]);
    $product->save();
}

it('keeps every image when gallery images are uploaded one request at a time', function (): void {
    $gallery = createMediaAttribute('gallery');

    uploadProductMedia($this->product->sku, $gallery->code, ['first.png']);
    uploadProductMedia($this->product->sku, $gallery->code, ['second.png']);

    $base = 'product/'.$this->product->id.'/'.$gallery->code;

    expect($this->product->fresh()->values['common'][$gallery->code])->toBe([$base.'/first.png', $base.'/second.png']);
});

it('appends an upload to a gallery saved as a list by the admin form', function (): void {
    $gallery = createMediaAttribute('gallery');

    storeProductValue($this->product, $gallery->code, ['product/existing/one.png', 'product/existing/two.png']);

    uploadProductMedia($this->product->sku, $gallery->code, ['third.png']);

    expect($this->product->fresh()->values['common'][$gallery->code])->toBe([
        'product/existing/one.png',
        'product/existing/two.png',
        'product/'.$this->product->id.'/'.$gallery->code.'/third.png',
    ]);
});

it('converts a comma separated gallery written by an earlier release into a list when appending', function (): void {
    $gallery = createMediaAttribute('gallery');

    storeProductValue($this->product, $gallery->code, 'product/legacy/one.png,product/legacy/two.png');

    uploadProductMedia($this->product->sku, $gallery->code, ['three.png']);

    expect($this->product->fresh()->values['common'][$gallery->code])->toBe([
        'product/legacy/one.png',
        'product/legacy/two.png',
        'product/'.$this->product->id.'/'.$gallery->code.'/three.png',
    ]);
});

it('appends every file of a multi file upload to the existing gallery', function (): void {
    $gallery = createMediaAttribute('gallery');

    storeProductValue($this->product, $gallery->code, ['product/existing/one.png']);

    uploadProductMedia($this->product->sku, $gallery->code, ['two.png', 'three.png']);

    $base = 'product/'.$this->product->id.'/'.$gallery->code;

    expect($this->product->fresh()->values['common'][$gallery->code])->toBe(['product/existing/one.png', $base.'/two.png', $base.'/three.png']);
});

it('does not list the same gallery path twice when a file is uploaded again', function (): void {
    $gallery = createMediaAttribute('gallery');

    uploadProductMedia($this->product->sku, $gallery->code, ['same.png']);
    uploadProductMedia($this->product->sku, $gallery->code, ['same.png']);

    expect($this->product->fresh()->values['common'][$gallery->code])->toBe(['product/'.$this->product->id.'/'.$gallery->code.'/same.png']);
});

it('appends gallery uploads at the scope the attribute is stored in', function (bool $perChannel, bool $perLocale, string $scopePath): void {
    $gallery = createMediaAttribute('gallery', $perChannel, $perLocale);

    $channel = core()->getDefaultChannelCode();
    $locale = core()->getDefaultLocaleCodeFromDefaultChannel();
    $scope = ['channel' => $channel, 'locale' => $locale];

    uploadProductMedia($this->product->sku, $gallery->code, ['first.png'], $scope);
    uploadProductMedia($this->product->sku, $gallery->code, ['second.png'], $scope);

    $base = 'product/'.$this->product->id.'/'.$gallery->code;
    $values = $this->product->fresh()->values;

    expect(data_get($values, [...explode('.', strtr($scopePath, ['{channel}' => $channel, '{locale}' => $locale])), $gallery->code]))->toBe([$base.'/first.png', $base.'/second.png'])
        ->and($values['common'][$gallery->code] ?? null)->toBeNull();
})->with([
    'per channel and locale' => [true, true, 'channel_locale_specific.{channel}.{locale}'],
    'per channel'            => [true, false, 'channel_specific.{channel}'],
    'per locale'             => [false, true, 'locale_specific.{locale}'],
]);

it('rejects a channel or locale that does not exist without touching the product', function (string $field, string $code): void {
    $gallery = createMediaAttribute('gallery', perChannel: true, perLocale: true);

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
    $image = createMediaAttribute('image');

    uploadProductMedia($this->product->sku, $image->code, ['first.png']);
    uploadProductMedia($this->product->sku, $image->code, ['second.png']);

    expect($this->product->fresh()->values['common'][$image->code])->toBe('product/'.$this->product->id.'/'.$image->code.'/second.png');
});

it('returns every path of a gallery saved as a list', function (): void {
    $gallery = createMediaAttribute('gallery');

    storeProductValue($this->product, $gallery->code, ['product/existing/one.png', 'product/existing/two.png']);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.media-files.product.get', ['sku' => $this->product->sku, 'attribute' => $gallery->code]))
        ->assertOk()
        ->assertExactJson(['data' => ['product/existing/one.png', 'product/existing/two.png']]);
});

it('deletes every file of a gallery saved as a list', function (): void {
    $gallery = createMediaAttribute('gallery');

    $paths = ['product/existing/one.png', 'product/existing/two.png'];

    foreach ($paths as $path) {
        Storage::put($path, 'x');
    }

    storeProductValue($this->product, $gallery->code, $paths);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.media-files.product.delete', ['sku' => $this->product->sku, 'attribute' => $gallery->code]))
        ->assertOk();

    Storage::assertMissing($paths[0]);
    Storage::assertMissing($paths[1]);

    expect($this->product->fresh()->values['common'][$gallery->code] ?? null)->toBeNull();
});
