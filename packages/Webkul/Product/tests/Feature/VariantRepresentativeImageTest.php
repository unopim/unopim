<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Contracts\ProductImageFallbackResolver as ProductImageFallbackResolverContract;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;

function productWithImage(array $overrides = [], ?string $image = null): Product
{
    $product = Product::factory()->create($overrides);

    $product->values = ['common' => $image === null ? [] : ['image' => $image]];
    $product->save();

    return $product->refresh();
}

it('shows a child variant image on the parent when the parent has none', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => ProductType::Configurable->value]);
    $child = productWithImage(['type' => ProductType::Simple->value, 'parent_id' => $parent->id], 'product/1/child.jpg');

    expect($child->getProductDisplayImage())->toBe('product/1/child.jpg');
    expect($parent->refresh()->getProductDisplayImage())->toBe('product/1/child.jpg');
});

it('walks down to a grandchild variant for the top level parent', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => ProductType::Configurable->value]);
    $subParent = productWithImage(['type' => ProductType::Configurable->value, 'parent_id' => $parent->id]);
    $child = productWithImage(['type' => ProductType::Simple->value, 'parent_id' => $subParent->id], 'product/2/grandchild.jpg');

    expect($subParent->refresh()->getProductDisplayImage())->toBe('product/2/grandchild.jpg');
    expect($parent->refresh()->getProductDisplayImage())->toBe('product/2/grandchild.jpg');
});

it('keeps the products own image ahead of any variant image', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => ProductType::Configurable->value], 'product/3/parent.jpg');
    productWithImage(['type' => ProductType::Simple->value, 'parent_id' => $parent->id], 'product/3/child.jpg');

    expect($parent->refresh()->getProductDisplayImage())->toBe('product/3/parent.jpg');
});

it('never writes the borrowed image back onto the parent values', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => ProductType::Configurable->value]);
    productWithImage(['type' => ProductType::Simple->value, 'parent_id' => $parent->id], 'product/4/child.jpg');

    $parent->refresh()->getProductDisplayImage();

    expect($parent->refresh()->values['common']['image'] ?? null)->toBeNull();
});

it('shows a configurable parents own image on a child with none of its own', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => ProductType::Configurable->value], 'product/5/parent.jpg');
    $child = productWithImage(['type' => ProductType::Simple->value, 'parent_id' => $parent->id]);

    expect($child->getProductDisplayImage())->toBe('product/5/parent.jpg');
});

it('prefers its own image over an inherited parent image', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => ProductType::Configurable->value], 'product/6/parent.jpg');
    $child = productWithImage(['type' => ProductType::Simple->value, 'parent_id' => $parent->id], 'product/6/child.jpg');

    expect($child->getProductDisplayImage())->toBe('product/6/child.jpg');
});

it('walks up through a sub-configurable to the top level parents own image', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => ProductType::Configurable->value], 'product/7/parent.jpg');
    $subParent = productWithImage(['type' => ProductType::Configurable->value, 'parent_id' => $parent->id]);
    $child = productWithImage(['type' => ProductType::Simple->value, 'parent_id' => $subParent->id]);

    expect($child->getProductDisplayImage())->toBe('product/7/parent.jpg');
});

it('never writes an inherited parent image back onto the childs own values', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => ProductType::Configurable->value], 'product/8/parent.jpg');
    $child = productWithImage(['type' => ProductType::Simple->value, 'parent_id' => $parent->id]);

    $child->getProductDisplayImage();

    expect($child->refresh()->values['common']['image'] ?? null)->toBeNull();
});

it('returns null from normalizeWithImage when nothing is bound and no image exists', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $product = productWithImage(['type' => ProductType::Simple->value]);

    expect($product->normalizeWithImage()['image'])->toBeNull();
});

it('asks the bound fallback resolver only when no classic image was found', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    app()->bind(ProductImageFallbackResolverContract::class, fn () => new class implements ProductImageFallbackResolverContract
    {
        public function resolveFallbackUrl(Product $product, ?string $currentChannelCode = null, ?string $currentLocaleCode = null): ?string
        {
            return 'https://dam.example.test/assets/'.$product->id.'/thumbnail';
        }
    });

    $withoutImage = productWithImage(['type' => ProductType::Simple->value]);
    $withImage = productWithImage(['type' => ProductType::Simple->value], 'product/9/own.jpg');

    expect($withoutImage->normalizeWithImage()['image'])->toBe('https://dam.example.test/assets/'.$withoutImage->id.'/thumbnail');
    expect($withImage->normalizeWithImage()['image'])->toBe(Storage::url('product/9/own.jpg'));
});
