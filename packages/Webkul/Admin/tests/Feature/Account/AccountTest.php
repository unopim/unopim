<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('should handle multiple image upload via drag and drop for products', function () {
    $this->loginAsAdmin();

    $attribute = \Webkul\Attribute\Models\Attribute::factory()->create(['type' => 'gallery']);

    $product = \Webkul\Product\Models\Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $images = [
        UploadedFile::fake()->image('product.jpg'),
        UploadedFile::fake()->image('product2.jpg'),
        UploadedFile::fake()->image('product3.jpg'),
    ];

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $images,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');
    $this->assertCount(3, $product->values['common'][$attributeCode]);

    foreach ($product->values['common'][$attributeCode] as $media) {
        $this->assertTrue(Storage::exists($media));
    }
});

it('should validate file types on drag and drop upload for products', function () {
    $this->loginAsAdmin();

    $attribute = \Webkul\Attribute\Models\Attribute::factory()->create(['type' => 'gallery']);

    $product = \Webkul\Product\Models\Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $invalidFiles = [
        UploadedFile::fake()->image('product.jpg'),
        UploadedFile::fake()->image('product2.txt'),
        UploadedFile::fake()->image('product3.php'),
    ];

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $invalidFiles,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertInvalid('values[common]['.$attributeCode.']');

    $product->refresh();

    $this->assertEmpty($product->values['common'][$attributeCode] ?? '');
});

it('should upload file attribute via drag and drop for products', function () {
    $this->loginAsAdmin();

    $attribute = \Webkul\Attribute\Models\Attribute::factory()->create(['type' => 'file']);

    $product = \Webkul\Product\Models\Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $file = UploadedFile::fake()->create('document.pdf', 1024);

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => [$file],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});

it('should handle image attribute upload via drag and drop for simple product', function () {
    $this->loginAsAdmin();

    $attribute = \Webkul\Attribute\Models\Attribute::factory()->create(['type' => 'image']);

    $product = \Webkul\Product\Models\Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $image = UploadedFile::fake()->image('product.jpg');

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => [$image],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});
