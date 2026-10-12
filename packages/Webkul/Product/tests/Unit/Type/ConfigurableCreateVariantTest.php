<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Contracts\VariantValueResolver;
use Webkul\Product\Models\Product;

uses(DatabaseTransactions::class);

it('does not copy non-axis parent values into a newly created variant', function () {
    $parent = Product::factory()->configurable()->withConfigurableAttributes()->create([
        'values' => ['common' => ['brand' => 'Nike', 'material' => 'Cotton']],
    ]);

    $superAttribute = $parent->super_attributes->first();

    expect($superAttribute)->not->toBeNull();

    $optionCode = optional($superAttribute->options->first())->code;

    expect($optionCode)->not->toBeNull();

    $variant = $parent->getTypeInstance()->createVariant(
        $parent,
        $parent->super_attributes,
        [
            'sku'    => $parent->sku.'-V1',
            'values' => ['common' => [$superAttribute->code => $optionCode]],
        ]
    );

    // Stored variant values hold ONLY the axis option + sku - no inherited copies.
    expect($variant->values['common'])->not->toHaveKey('brand')
        ->and($variant->values['common'])->not->toHaveKey('material')
        ->and($variant->values['common'])->toHaveKey('sku')
        ->and($variant->values['common'][$superAttribute->code])->toBe($optionCode);

    // Resolution still returns the parent's values for that variant.
    $resolved = app(VariantValueResolver::class)->resolve($variant->refresh());

    expect($resolved['common']['brand'])->toBe('Nike')
        ->and($resolved['common']['material'])->toBe('Cotton');
});
