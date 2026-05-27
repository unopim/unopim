<?php

use Illuminate\Support\Facades\Event;
use Webkul\Product\Models\Product;

it('should dispatch catalog.product.create.after event for each variant created under a configurable product', function () {
    $this->loginAsAdmin();

    $configurableProduct = Product::factory()->configurable()->withVariantProduct()->withInitialValues()->create();

    $attribute = $configurableProduct->super_attributes->first();

    $existingVariant = $configurableProduct->variants->first();

    $variantSku = $configurableProduct->sku.'-new-variant-'.Str::random(5);
    $variantValue = $attribute->options->last()->code;

    $attrCode = $attribute->code;

    Event::fake([
        'catalog.product.create.after',
    ]);

    $data = [
        'sku'      => $configurableProduct->sku,
        'values'   => $configurableProduct->values,
        'variants' => [
            $existingVariant->id => [
                'sku'    => $existingVariant->sku,
                'values' => [
                    'common' => [
                        'sku'      => $existingVariant->sku,
                        $attrCode  => $existingVariant->values['common'][$attrCode],
                    ],
                ],
            ],
            'variant_1' => [
                'sku'    => $variantSku,
                'values' => [
                    'common' => [
                        'sku'      => $variantSku,
                        $attrCode  => $variantValue,
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $configurableProduct->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $this->assertDatabaseHas(Product::class, [
        'sku'       => $variantSku,
        'parent_id' => $configurableProduct->id,
        'type'      => 'simple',
    ]);

    Event::assertDispatched('catalog.product.create.after', function ($event, $product) use ($variantSku) {
        return $product->sku === $variantSku;
    });
});

it('should dispatch catalog.product.create.after event for multiple variants created at once', function () {
    $this->loginAsAdmin();

    $configurableProduct = Product::factory()->configurable()->withConfigurableAttributes()->withInitialValues()->create();

    $attribute = $configurableProduct->super_attributes->first();
    $options = $attribute->options;

    if ($options->count() < 2) {
        $this->markTestSkipped('Need at least 2 options for this test');
    }

    $attrCode = $attribute->code;

    $variantSku1 = $configurableProduct->sku.'-multi-v1-'.Str::random(5);
    $variantSku2 = $configurableProduct->sku.'-multi-v2-'.Str::random(5);

    Event::fake([
        'catalog.product.create.after',
    ]);

    $data = [
        'sku'      => $configurableProduct->sku,
        'values'   => $configurableProduct->values,
        'variants' => [
            'variant_1' => [
                'sku'    => $variantSku1,
                'values' => [
                    'common' => [
                        'sku'      => $variantSku1,
                        $attrCode  => $options->first()->code,
                    ],
                ],
            ],
            'variant_2' => [
                'sku'    => $variantSku2,
                'values' => [
                    'common' => [
                        'sku'      => $variantSku2,
                        $attrCode  => $options->last()->code,
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $configurableProduct->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $this->assertDatabaseHas(Product::class, [
        'sku'       => $variantSku1,
        'parent_id' => $configurableProduct->id,
    ]);

    $this->assertDatabaseHas(Product::class, [
        'sku'       => $variantSku2,
        'parent_id' => $configurableProduct->id,
    ]);

    Event::assertDispatched('catalog.product.create.after', function ($event, $product) use ($variantSku1) {
        return $product->sku === $variantSku1;
    });

    Event::assertDispatched('catalog.product.create.after', function ($event, $product) use ($variantSku2) {
        return $product->sku === $variantSku2;
    });
});

it('should not dispatch catalog.product.create.after event when updating existing variants', function () {
    $this->loginAsAdmin();

    $configurableProduct = Product::factory()->configurable()->withVariantProduct()->withInitialValues()->create();

    $attribute = $configurableProduct->super_attributes->first();
    $existingVariant = $configurableProduct->variants->first();
    $attrCode = $attribute->code;

    Event::fake([
        'catalog.product.create.after',
    ]);

    $data = [
        'sku'      => $configurableProduct->sku,
        'values'   => $configurableProduct->values,
        'variants' => [
            $existingVariant->id => [
                'sku'    => $existingVariant->sku,
                'values' => [
                    'common' => [
                        'sku'      => $existingVariant->sku,
                        $attrCode  => $existingVariant->values['common'][$attrCode],
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $configurableProduct->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    Event::assertNotDispatched('catalog.product.create.after');
});
