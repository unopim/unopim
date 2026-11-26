<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

beforeEach(function (): void {
    $this->loginAsAdmin();

    Attribute::query()->where('code', 'description')->update(['enable_wysiwyg' => 1]);
});

it('keeps a video embed and a new window link when a product is saved', function (): void {
    $product = seedRequiredProductValues(Product::factory()->simple()->create())->fresh();

    $values = $product->values;
    $values['channel_locale_specific']['default']['en_US']['description'] = '<iframe src="https://www.youtube.com/embed/abc"></iframe>'
        .'<a href="https://example.com" target="_blank">x</a>'
        .'<iframe src="https://evil.example/x"></iframe><script>alert(1)</script>';

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'status' => '1',
        'values' => $values,
    ])->assertSessionHasNoErrors();

    $description = $product->fresh()->values['channel_locale_specific']['default']['en_US']['description'];

    expect($description)->toContain('<iframe src="https://www.youtube.com/embed/abc"')
        ->toContain('sandbox=')
        ->toContain('target="_blank"')
        ->toContain('noopener')
        ->not->toContain('evil.example')
        ->not->toContain('<script');
});
