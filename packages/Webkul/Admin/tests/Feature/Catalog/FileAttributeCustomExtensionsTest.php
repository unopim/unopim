<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

function customExtensionFileAttributeProduct(array $allowedExtensions): array
{
    $attribute = Attribute::factory()->create(['type' => 'file', 'allowed_extensions' => $allowedExtensions]);

    $product = seedRequiredProductValues(Product::factory()->simple()->create());

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    return [$product, $attribute->code];
}

function realZipUpload(string $name): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'zip');

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('readme.txt', 'packaged asset');
    $zip->close();

    return new UploadedFile($path, $name, 'application/zip', null, true);
}

it('saves a product file whose extension is only allowed by the attribute', function () {
    $this->loginAsAdmin();

    [$product, $attributeCode] = customExtensionFileAttributeProduct(['zip', 'rar', '7z', 'psd', 'pdf']);

    Storage::fake();

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'values' => ['common' => [$attributeCode => [realZipUpload('bundle.zip')]]],
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    $product->refresh();

    $storedPath = $product->values['common'][$attributeCode] ?? null;

    expect($storedPath)->toEndWith('.zip')
        ->and(Storage::exists($storedPath))->toBeTrue();
});

it('rejects spoofed content renamed to an attribute allowed extension on product save', function () {
    $this->loginAsAdmin();

    [$product, $attributeCode] = customExtensionFileAttributeProduct(['zip']);

    Storage::fake();

    $path = tempnam(sys_get_temp_dir(), 'spoof');
    file_put_contents($path, '<html><script>alert(1)</script></html>');

    $spoofed = new UploadedFile($path, 'bundle.zip', 'application/zip', null, true);

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'values' => ['common' => [$attributeCode => [$spoofed]]],
    ])->assertSessionHasErrors();

    $product->refresh();

    expect($product->values['common'][$attributeCode] ?? null)->toBeNull();
});
