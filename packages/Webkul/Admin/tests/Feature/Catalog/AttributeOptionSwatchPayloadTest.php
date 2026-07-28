<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;

$fetchOptions = fn ($test, int $attributeId) => $test
    ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
    ->getJson(route('admin.catalog.options.fetch-all', [
        'entityName'  => 'attribute',
        'attributeId' => $attributeId,
        'page'        => 1,
    ]));

$colorAttribute = fn (): Attribute => Attribute::where('code', 'color')->firstOrFail();

it('sends the swatch metadata for every attribute option', function () use ($fetchOptions, $colorAttribute) {
    $this->loginAsAdmin();

    $attribute = $colorAttribute();

    $options = $fetchOptions($this, $attribute->id)->assertOk()->json('options');

    expect($options)->not->toBeEmpty();

    foreach ($options as $option) {
        expect($option)->toHaveKey('attribute');
        expect($option['attribute'])->toHaveKey('swatch_type');
    }
});

it('keeps the option payload shape identical whatever the swatch value holds', function (?string $swatchValue) use ($fetchOptions, $colorAttribute) {
    $this->loginAsAdmin();

    $attribute = $colorAttribute();

    AttributeOption::where('attribute_id', $attribute->id)->update(['swatch_value' => $swatchValue]);

    $options = $fetchOptions($this, $attribute->id)->assertOk()->json('options');

    expect($options)->not->toBeEmpty();

    $keySets = collect($options)->map(fn (array $option): array => array_keys($option))->unique(fn ($keys) => implode(',', $keys));

    expect($keySets)->toHaveCount(1);

    foreach ($options as $option) {
        expect($option['attribute'])->toBe(['swatch_type' => $attribute->swatch_type]);
    }
})->with([
    'no swatch value'    => null,
    'empty swatch value' => '',
    'colour swatch'      => '#ff0000',
]);

it('does not attach attribute swatch metadata to category field options', function () {
    $this->loginAsAdmin();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.catalog.options.fetch-all', [
            'entityName' => 'category_field',
            'page'       => 1,
        ]))
        ->assertOk();

    foreach ($response->json('options') as $option) {
        expect($option)->not->toHaveKey('attribute');
    }
});

it('never renders an unguarded swatch_type read in the shared select template', function () {
    expect(file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/components/products/dynamic-attribute-fields.blade.php')
    ))->not->toContain('option.attribute.swatch_type');
});
