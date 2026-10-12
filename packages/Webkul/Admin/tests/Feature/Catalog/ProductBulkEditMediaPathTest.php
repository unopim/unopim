<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('copies a drag-filled image path into the target product own directory on bulk save', function () {
    Storage::fake();

    $family = AttributeFamily::find(1)
        ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create();

    $products = Product::factory()->withInitialValues()->count(2)->create(['attribute_family_id' => $family->id]);
    [$sourceProduct, $targetProduct] = $products;

    $imageAttribute = Attribute::factory()->create([
        'type'              => 'image',
        'value_per_locale'  => false,
        'value_per_channel' => false,
    ]);
    $family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($imageAttribute);

    // Simulate the file the source row actually uploaded through
    // storeProductMedia(): stored under the uploading product's own directory.
    $sourcePath = 'product/'.$sourceProduct->id.'/'.$imageAttribute->code.'/photo.jpg';
    Storage::put($sourcePath, 'fake-image-bytes');

    // Bulk-edit drag-fill just copies the string value to every selected
    // row, so the target row's payload points at the source row's path.
    $payload = [
        $sourceProduct->id => [$imageAttribute->code => $sourcePath],
        $targetProduct->id => [$imageAttribute->code => $sourcePath],
    ];

    $this->postJson(route('admin.catalog.products.bulk-edit.save'), ['data' => $payload])
        ->assertOk();

    $targetProduct->refresh();
    $savedPath = $targetProduct->values['common'][$imageAttribute->code] ?? null;

    expect($savedPath)->not->toBeNull();
    expect($savedPath)->not->toBe($sourcePath);
    expect($savedPath)->toStartWith('product/'.$targetProduct->id.'/'.$imageAttribute->code.'/');
    Storage::assertExists($savedPath);
    expect(Storage::get($savedPath))->toBe('fake-image-bytes');

    // The uploading row's own value is already legitimately its own path
    // and must not be rewritten.
    $sourceProduct->refresh();
    expect($sourceProduct->values['common'][$imageAttribute->code] ?? null)->toBe($sourcePath);
});
