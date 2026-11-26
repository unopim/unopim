<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;

beforeEach(function (): void {
    $this->headers = $this->getAuthenticationHeaders();

    Storage::fake();
});

it('rejects an uploaded file as swatch_value when creating an attribute option', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);

    $this->withHeaders($this->headers)
        ->post(route('admin.api.attribute_options.store_option', $attribute->code), [
            ['code' => 'swatch_opt', 'swatch_value' => UploadedFile::fake()->create('evil.html', 1, 'text/html')],
        ], ['Accept' => 'application/json'])
        ->assertStatus(422);

    expect(Storage::allFiles('attribute_option'))->toBeEmpty();
});

it('rejects an uploaded file as swatch_value when updating an existing attribute option', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.attribute_options.store_option', $attribute->code), [['code' => 'swatch_opt']])
        ->assertCreated();

    $this->withHeaders($this->headers)
        ->put(route('admin.api.attribute_options.update_option', $attribute->code), [
            ['code' => 'swatch_opt', 'swatch_value' => UploadedFile::fake()->create('evil.html', 1, 'text/html')],
        ], ['Accept' => 'application/json'])
        ->assertStatus(422);

    expect(Storage::allFiles('attribute_option'))->toBeEmpty();
});

it('still accepts a colour swatch_value', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'color']);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.attribute_options.store_option', $attribute->code), [
            ['code' => 'red_opt', 'swatch_value' => '#ff0000'],
        ])
        ->assertCreated();
});

it('rejects an uploaded file as swatch_value in the options of POST /attributes', function (): void {
    $code = 'swatch_create_'.uniqid();

    $this->withHeaders($this->headers)
        ->post(route('admin.api.attributes.store'), [
            'code'        => $code,
            'type'        => 'select',
            'swatch_type' => 'image',
            'options'     => [['code' => 'swatch_opt', 'swatch_value' => UploadedFile::fake()->create('evil.html', 1, 'text/html')]],
        ], ['Accept' => 'application/json'])
        ->assertStatus(422);

    expect(Attribute::where('code', $code)->exists())->toBeFalse()
        ->and(Storage::allFiles('attribute_option'))->toBeEmpty();
});

it('rejects an uploaded file as swatch_value for an existing option in PUT /attributes', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.attribute_options.store_option', $attribute->code), [['code' => 'swatch_opt']])
        ->assertCreated();

    $this->withHeaders($this->headers)
        ->put(route('admin.api.attributes.update', $attribute->code), [
            'options' => [['code' => 'swatch_opt', 'swatch_value' => UploadedFile::fake()->create('evil.html', 1, 'text/html')]],
        ], ['Accept' => 'application/json'])
        ->assertStatus(422);

    expect(Storage::allFiles('attribute_option'))->toBeEmpty();
});

it('rejects a swatch_value path outside the swatch directory on every write route', function (string $path): void {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.attribute_options.store_option', $attribute->code), [['code' => 'path_opt', 'swatch_value' => $path]])
        ->assertStatus(422);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.attributes.store'), [
            'code'        => 'swatch_path_'.uniqid(),
            'type'        => 'select',
            'swatch_type' => 'image',
            'options'     => [['code' => 'path_opt', 'swatch_value' => $path]],
        ])
        ->assertStatus(422);
})->with([
    'another directory' => 'private/secrets.txt',
    'traversal'         => 'attribute_option/../.env',
    'absolute'          => '/etc/passwd',
]);

it('still accepts a swatch_value path inside the swatch directory', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.attribute_options.store_option', $attribute->code), [
            ['code' => 'kept_opt', 'swatch_value' => 'attribute_option/existing.png'],
        ])
        ->assertCreated();
});
