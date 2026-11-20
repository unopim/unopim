<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\MagicAI\Jobs\SaveTranslatedAllAttributesJob;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Services\ProductValueMapper;

it('purifies translated rich-text values before they are stored', function () {
    $attribute = Attribute::factory()->create([
        'type'             => 'textarea',
        'enable_wysiwyg'   => 1,
        'value_per_locale' => 1,
    ]);

    $product = Product::factory()->create();

    (new SaveTranslatedAllAttributesJob(
        $product->id,
        [[
            'field'        => $attribute->code,
            'translations' => [['locale' => 'fr_FR', 'content' => '<p>Bonjour</p><script>alert(1)</script><img src="x" onerror="alert(2)">']],
        ]],
        'default',
    ))->handle(
        resolve(ProductRepository::class),
        resolve(AttributeRepository::class),
        resolve(ProductValueMapper::class),
    );

    expect(resolve(ProductValueMapper::class)->getScopedValue(
        $product->fresh()->values,
        $attribute,
        'default',
        'fr_FR',
    ))
        ->toContain('<p>Bonjour</p>')
        ->not->toContain('<script')
        ->not->toContain('onerror');
});
