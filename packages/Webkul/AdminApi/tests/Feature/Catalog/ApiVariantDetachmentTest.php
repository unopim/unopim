<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;


beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('creates the variants supplied in the POST payload to /configrable-products', function () {
    $family = AttributeFamily::where('code', 'default')->first()
        ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create();


    $configurableAttributes = $family->getConfigurableAttributes();

    expect($configurableAttributes->count())
        ->toBeGreaterThanOrEqual(2, 'family must expose at least 2 configurable attributes for this scenario');

    $firstAttr = $configurableAttributes->first();
    $secondAttr = $configurableAttributes->skip(1)->first();

    $firstAttrOption = $firstAttr->options->first()->code;
    $secondAttrOption = $secondAttr->options->first()->code;

    $parentSku = 'issue841-parent-'.fake()->unique()->randomNumber();
    $variantSku = 'issue841-variant-'.fake()->unique()->randomNumber();

    $payload = [
        'sku'              => $parentSku,
        'status'           => true,
        'parent'           => null,
        'family'           => $family->code,
        'additional'       => null,
        'values'           => [
            'common' => [
                'sku' => $parentSku,
            ],
        ],
        'super_attributes' => [$firstAttr->code, $secondAttr->code],
        'variants'         => [
            [
                'sku'        => $variantSku,
                'attributes' => [
                    $firstAttr->code  => $firstAttrOption,
                    $secondAttr->code => $secondAttrOption,
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.configurable_products.store'), $payload)
        ->assertStatus(201);

    $parent = Product::where('sku', $parentSku)->first();

    expect($parent)->not->toBeNull()
        ->and($parent->type)->toBe('configurable');

    $variant = Product::where('sku', $variantSku)->first();

    expect($variant)
        ->not->toBeNull('variant should be created by the POST — silently dropped without the fix')
        ->and($variant->parent_id)
        ->toBe($parent->id, 'created variant should be linked to the configurable parent');

    expect($parent->variants()->count())
        ->toBe(1, 'parent should expose the created variant in its variants() relation');
});
