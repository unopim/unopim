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
