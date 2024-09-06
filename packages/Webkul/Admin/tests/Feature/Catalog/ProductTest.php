<?php

use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

it('should return the product index page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.products.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.products.index.title'));
});

it('should return validation errors for certain fields when creating', function () {
    $this->loginAsAdmin();

    $this->post(route('admin.catalog.products.store', []))
        ->assertInvalid('type')
        ->assertInvalid('attribute_family_id')
        ->assertInvalid('sku');
});

it('should return the product datagrid', function () {
    $this->loginAsAdmin();

    Product::factory()->create();

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'));

    $response->assertStatus(200);

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(Product::class), [
        'sku' => $data['records'][0]['sku'],
    ]);
});

it('should return unique validation for product sku while creating', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create();

    $data = [
        'sku'                 => $product->sku,
        'type'                => 'simple',
        'attribute_family_id' => $product->attribute_family_id,
    ];

    $this->post(route('admin.catalog.products.store', $data))
        ->assertRedirect()
        ->assertInvalid('sku');

    $this->assertDatabaseMissing($this->getFullTableName(Product::class), $data);
});

it('should create a simple product successfully', function () {
    $this->loginAsAdmin();

    $data = Product::factory()->definition();

    $data['type'] = 'simple';

    $this->post(route('admin.catalog.products.store', $data))
        ->assertOk()
        ->assertSessionHas('success', trans('admin::app.catalog.products.create-success'))
        ->assertJson(fn (AssertableJson $json) => $json->whereType('data.redirect_url', 'string'));

    $this->assertDatabaseHas($this->getFullTableName(Product::class), $data);
});

it('should return json error if family lacks configurable attributes when creating configurable product', function () {
    $this->loginAsAdmin();

    $familyId = AttributeFamily::factory()->create()->id;

    $data = [
        'sku'                 => fake()->uuid,
        'attribute_family_id' => $familyId,
        'type'                => 'configurable',
    ];

    $this->post(route('admin.catalog.products.store'), $data)
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'attribute_family_id' => [trans('admin::app.catalog.products.index.create.not-config-family-error')],
            ],
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(Product::class), $data);
});

it('should return configurable attributes when creating configurable product', function () {
    $this->loginAsAdmin();

    $data = Product::factory()->definition();

    $data['type'] = 'configurable';

    $this->post(route('admin.catalog.products.store'), $data)
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json->whereType('data.attributes', 'array'));
});

it('should create a configurable product successfully', function () {
    $this->loginAsAdmin();

    $data = Product::factory()->definition();

    $configurableAttribute = AttributeFamily::find($data['attribute_family_id'])->getConfigurableAttributes()->first();

    $data['type'] = 'configurable';

    $data['super_attributes'] = json_encode([$configurableAttribute->code]);

    $this->post(route('admin.catalog.products.store'), $data)
        ->assertOk()
        ->assertSessionHas('success', trans('admin::app.catalog.products.create-success'))
        ->assertJson(fn (AssertableJson $json) => $json->whereType('data.redirect_url', 'string'));

    unset($data['super_attributes']);

    $this->assertDatabaseHas($this->getFullTableName(Product::class), $data);

    $productId = Product::where('sku', $data['sku'])->first()->id;

    $this->assertDatabaseHas('product_super_attributes', [
        'product_id'   => $productId,
        'attribute_id' => $configurableAttribute->id,
    ]);
});

it('should return the edit page for simple product successfully', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->simple()->create();

    $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.products.edit.title'))
        ->assertSeeText(trans('admin::app.catalog.products.edit.save-btn'))
        ->assertSeeText(trans('admin::app.catalog.products.edit.categories.title'))
        ->assertSeeText(trans('admin::app.catalog.products.edit.links.title'))
        ->assertDontSeeText(trans('admin::app.catalog.products.edit.types.configurable.empty-title'));
});

it('should return the edit page for configurable product successfully', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->configurable()->withConfigurableAttributes()->create();

    $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.products.edit.title'))
        ->assertSeeText(trans('admin::app.catalog.products.edit.save-btn'))
        ->assertSeeText(trans('admin::app.catalog.products.edit.categories.title'))
        ->assertSeeText(trans('admin::app.catalog.products.edit.links.title'))
        ->assertSeeText(trans('admin::app.catalog.products.edit.types.configurable.empty-title'));
});

it('should copy the product successfully', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->simple()->create();

    $response = $this->get(route('admin.catalog.products.copy', $product->id));

    $productId = Str::afterLast($response->getTargetUrl(), '/');

    $response->assertRedirect(route('admin.catalog.products.edit', $productId));

    $this->assertDatabaseHas($this->getFullTableName(Product::class), ['id' => $productId]);
});

it('should delete the product successfully', function () {
    $this->loginAsAdmin();

    $productId = Product::factory()->simple()->create()->id;

    $this->delete(route('admin.catalog.products.delete', $productId))
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.products.delete-success')]);

    $this->assertDatabaseMissing($this->getFullTableName(Product::class), ['id' => $productId]);
});

it('should mass delete the products successfully', function () {
    $this->loginAsAdmin();

    $productIds = Product::factory()->simple()->createMany(3)->pluck('id')->toArray();

    $this->post(route('admin.catalog.products.mass_delete'), ['indices' => $productIds])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.products.index.datagrid.mass-delete-success')]);

    foreach ($productIds as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(Product::class), ['id' => $id]);
    }
});

it('should mass update the status of products to enabled', function () {
    $this->loginAsAdmin();

    $products = Product::factory()->simple()->createMany(2);

    $this->post(route('admin.catalog.products.mass_update'), ['indices' => $products->pluck('id')->toArray(), 'value' => 'true'])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.products.index.datagrid.mass-update-success')]);

    foreach ($products as $product) {
        $product->refresh();

        $this->assertEquals('true', ($product->values['common']['status'] ?? false));
    }
});

it('should mass update the status of products to disabled', function () {
    $this->loginAsAdmin();

    $products = Product::factory()->simple()->createMany(2);

    $this->post(route('admin.catalog.products.mass_update'), ['indices' => $products->pluck('id')->toArray(), 'value' => 'false'])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.products.index.datagrid.mass-update-success')]);

    foreach ($products as $product) {
        $product->refresh();

        $this->assertEquals('false', ($product->values['common']['status'] ?? false));
    }
});
/** Need to add more assertions */
it('should search the products with sku successfully', function () {
    $this->loginAsAdmin();

    $products = Product::factory()->simple()->createMany(2);

    $sku = $products->first()->sku;

    $this->get(route('admin.catalog.products.search'), ['query' => $sku])
        ->assertOk();
});

it('should return validation error when setting duplicate variant configurable attribute value', function () {
    $this->loginAsAdmin();

    $configurableProduct = Product::factory()->configurable()->withVariantProduct()->create();

    $attribute = $configurableProduct->super_attributes->first();

    $newProduct = Product::factory()->simple()->create(['parent_id' => $configurableProduct->id]);

    $data = [
        'sku'    => $newProduct->sku,
        'values' => [
            'common' => [
                $attribute->code => $attribute->options->first()->code,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $newProduct->id), $data)
        ->assertSessionHas('warning', trans('admin::app.catalog.products.edit.types.configurable.create.variant-already-exists'));

    $newProduct->refresh();

    $this->assertNotEquals($newProduct->values['common'][$attribute->code] ?? '', $attribute->options->first()->code);
});

it('should create a new variant product for a configurable product without removing existing variant', function () {
    $this->loginAsAdmin();

    $configurableProduct = Product::factory()->configurable()->withVariantProduct()->withInitialValues()->create();

    $variantSku = $configurableProduct->sku.'-variant_1';

    $attribute = $configurableProduct->super_attributes->first();

    $variantValue = $attribute->options->last()->code;

    $existingVariant = $configurableProduct->variants->first();

    $attribute = $attribute->code;

    $data = [
        'sku'      => $configurableProduct->sku,
        'values'   => $configurableProduct->values,
        'variants' => [
            $existingVariant->id => [
                'sku'    => $existingVariant->sku,
                'values' => [
                    'common' => [
                        'sku'      => $existingVariant->sku,
                        $attribute => $existingVariant->values['common'][$attribute],
                    ],
                ],
            ],
            'variant_1' => [
                'sku'    => $variantSku,
                'values' => [
                    'common' => [
                        'sku'      => $variantSku,
                        $attribute => $variantValue,
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $configurableProduct->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $this->assertModelWise([
        Product::class => [
            [
                'sku'       => $variantSku,
                'parent_id' => $configurableProduct->id,
            ], [
                'sku'       => $existingVariant->sku,
                'parent_id' => $configurableProduct->id,
            ],
        ],
    ]);
});

it('should edit already existing variant product through a configurable product', function () {
    $this->loginAsAdmin();

    $configurableProduct = Product::factory()->configurable()->withVariantProduct()->withInitialValues()->create();

    $attribute = $configurableProduct->super_attributes->first();

    $variantValue = $attribute->options->last()->code;

    $attribute = $attribute->code;

    $variant = $configurableProduct->variants->first();

    $variantSku = $variant->sku;

    $data = [
        'sku'      => $configurableProduct->sku,
        'values'   => $configurableProduct->values,
        'variants' => [
            $variant->id => [
                'sku'    => $variantSku,
                'values' => [
                    'common' => [
                        'sku'      => $variantSku,
                        $attribute => $variantValue,
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $configurableProduct->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $variant->refresh();

    $this->assertEquals($variant->sku, $variantSku);

    $this->assertEquals($variant->values['common'][$attribute], $variantValue);
});

it('should remove already existing variant product through a configurable product', function () {
    $this->loginAsAdmin();

    $configurableProduct = Product::factory()->configurable()->withVariantProduct()->withInitialValues()->create();

    $variant = $configurableProduct->variants()->first();

    $variantData = [
        'id'  => $variant->id,
        'sku' => $variant->sku,
    ];

    $data = [
        'sku'      => $configurableProduct->sku,
        'values'   => $configurableProduct->values,
        'variants' => [],
    ];

    $this->put(route('admin.catalog.products.update', $configurableProduct->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $this->assertDatabaseMissing($this->getFullTableName(Product::class), $variantData);
});
