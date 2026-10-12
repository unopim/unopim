<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should assign uploaded image to the product attribute values (Issue #746)', function () {
    Storage::fake('public');

    $product = Product::factory()->create();

    // Pick a common (not per-channel, not per-locale) image attribute.
    $imageAttribute = Attribute::where('type', 'image')
        ->where('value_per_channel', false)
        ->where('value_per_locale', false)
        ->first();

    if (! $imageAttribute) {
        $imageAttribute = Attribute::factory()->create([
            'type'              => 'image',
            'value_per_channel' => false,
            'value_per_locale'  => false,
        ]);
    }

    $file = UploadedFile::fake()->image('sample.jpg');

    $response = $this->withHeaders($this->headers)->post(
        route('admin.api.media-files.product.store'),
        [
            'file'      => $file,
            'sku'       => $product->sku,
            'attribute' => $imageAttribute->code,
        ]
    );

    $response->assertOk();

    $product->refresh();
    $storedPath = data_get($product->values, 'common.'.$imageAttribute->code);

    expect($storedPath)->not->toBeEmpty();
});
