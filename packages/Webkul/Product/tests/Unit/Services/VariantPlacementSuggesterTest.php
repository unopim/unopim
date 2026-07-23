<?php

use Webkul\Product\Services\VariantPlacementSuggester;

function attr(string $code, string $type = 'text', bool $unique = false): array
{
    return ['code' => $code, 'type' => $type, 'is_unique' => $unique];
}

it('suggests variant level for unique attributes', function () {
    $suggester = new VariantPlacementSuggester;

    $result = $suggester->suggest([attr('serial_no', 'text', true)], 2, []);

    expect($result['serial_no'])->toBe('variant');
});

it('suggests variant for price/stock/sku-like attributes', function () {
    $suggester = new VariantPlacementSuggester;

    $result = $suggester->suggest([
        attr('price', 'price'),
        attr('special_price', 'price'),
        attr('stock', 'integer'),
        attr('weight', 'text'),
    ], 2, []);

    expect($result)->toMatchArray([
        'price'         => 'variant',
        'special_price' => 'variant',
        'stock'         => 'variant',
        'weight'        => 'variant',
    ]);
});

it('suggests sub_parent for media on a 2-level structure and variant on 1-level', function () {
    $suggester = new VariantPlacementSuggester;

    expect($suggester->suggest([attr('image', 'image')], 2, [])['image'])->toBe('sub_parent')
        ->and($suggester->suggest([attr('image', 'image')], 1, [])['image'])->toBe('variant');
});

it('defaults everything else to common', function () {
    $suggester = new VariantPlacementSuggester;

    $result = $suggester->suggest([
        attr('brand', 'select'),
        attr('material', 'text'),
        attr('description', 'textarea'),
    ], 2, []);

    expect($result)->toMatchArray([
        'brand'       => 'common',
        'material'    => 'common',
        'description' => 'common',
    ]);
});

it('excludes axis attributes from the suggestion', function () {
    $suggester = new VariantPlacementSuggester;

    $result = $suggester->suggest([
        attr('color', 'select'),
        attr('brand', 'select'),
    ], 2, ['color', 'size']);

    expect($result)->not->toHaveKey('color')
        ->and($result)->toHaveKey('brand');
});

it('sanitizes untrusted AI output (bad codes, axis, invalid levels)', function () {
    $suggester = new class extends VariantPlacementSuggester
    {
        public function callValidate(array $result, array $context): array
        {
            return $this->validateAiResult($result, $context);
        }
    };

    $context = [
        'attributes' => [attr('sku', 'text', true), attr('brand', 'select')],
        'levels'     => 2,
        'axisCodes'  => ['color'],
    ];

    $raw = [
        'sku'    => 'variant',      // valid
        'brand'  => 'common',       // valid
        'color'  => 'variant',      // axis -> dropped
        'ghost'  => 'variant',      // not a real attribute -> dropped
        'sku2'   => 'made_up_level', // not a real attribute -> dropped
    ];

    expect($suggester->callValidate($raw, $context))->toBe([
        'sku'   => 'variant',
        'brand' => 'common',
    ]);
});
