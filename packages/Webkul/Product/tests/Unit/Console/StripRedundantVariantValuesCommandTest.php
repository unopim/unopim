<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Models\Product;

uses(DatabaseTransactions::class);

it('strips redundant duplicated values but keeps genuine overrides', function () {
    $parent = Product::factory()->configurable()->withConfigurableAttributes()->create([
        'values' => ['common' => ['brand' => 'Nike', 'material' => 'Cotton']],
    ]);

    $super = $parent->super_attributes->first();
    $option = optional($super->options->first())->code;

    expect($option)->not->toBeNull();

    // Legacy variant: brand duplicates the parent (redundant), material diverges (real override).
    $variant = Product::factory()->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => [
            $super->code => $option,
            'sku'        => $parent->sku.'-S',
            'brand'      => 'Nike',   // == ancestor -> should be stripped
            'material'   => 'Wool',   // != ancestor -> should be kept
        ]],
    ]);

    $this->artisan('unopim:variants:strip-redundant', ['--apply' => true])->assertSuccessful();

    $variant->refresh();

    expect($variant->values['common'])->not->toHaveKey('brand')
        ->and($variant->values['common']['material'])->toBe('Wool')
        ->and($variant->values['common'])->toHaveKey('sku')
        ->and($variant->values['common'][$super->code])->toBe($option);

    // Inheritance still resolves the stripped value from the parent.
    expect($variant->resolvedValues()['common']['brand'])->toBe('Nike');
});

it('dry-run reports without deleting', function () {
    $parent = Product::factory()->configurable()->withConfigurableAttributes()->create([
        'values' => ['common' => ['brand' => 'Nike']],
    ]);

    $super = $parent->super_attributes->first();
    $option = optional($super->options->first())->code;

    $variant = Product::factory()->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => [$super->code => $option, 'sku' => $parent->sku.'-S', 'brand' => 'Nike']],
    ]);

    $this->artisan('unopim:variants:strip-redundant')->assertSuccessful();

    $variant->refresh();

    // Dry-run: brand still present.
    expect($variant->values['common'])->toHaveKey('brand');
});
