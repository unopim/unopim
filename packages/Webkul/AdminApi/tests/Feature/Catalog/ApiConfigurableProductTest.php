<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all configurable products', function () {
    $product = Product::factory()->configurable()->create();

    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.configrable_products.index'))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'sku',
                    'parent',
                    'family',
                    'type',
                    'additional',
                    'created_at',
                    'updated_at',
                    'values',
                    'super_attributes',
                    'variants',
                ],
            ],
            'current_page',
            'last_page',
            'total',
            'links' => [
                'first',
                'last',
                'next',
                'prev',
            ],
        ])
        ->assertJsonFragment(['total' => Product::where('type', 'configurable')->count()])
        ->json('data');

    $product = Product::where('type', 'configurable')->first();

    $data = [
        'sku'              => $product->sku,
        'parent'           => $product->parent,
        'family'           => $product->attribute_family->code,
        'type'             => $product->type,
        'additional'       => $product->additional,
        'created_at'       => $product->created_at->toISOString(),
        'updated_at'       => $product->updated_at->toISOString(),
        'values'           => $product->values,
        'super_attributes' => $product->super_attributes()->pluck('code')->toArray(),
        'variants'         => [],
    ];

    $this->assertTrue(
        collect($response)->contains($data),
    );
});

it('should return the configurable product by code', function () {
    $product = Product::factory()->configurable()->withVariantProduct()->create();

    $data = [
        'sku'              => $product->sku,
        'parent'           => $product->parent,
        'family'           => $product->attribute_family->code,
        'type'             => $product->type,
        'additional'       => $product->additional,
        'created_at'       => $product->created_at->toISOString(),
        'updated_at'       => $product->updated_at->toISOString(),
        'values'           => $product->values,
        'super_attributes' => $product->super_attributes()->pluck('code')->toArray(),
        'variants'         => [],
    ];

    foreach ($product->variants as $variant) {
        $attributes = [];

        foreach ($data['super_attributes'] as $attr) {
            $attributes[$attr] = $variant->values['common'][$attr];
        }

        $data['variants'][] = [
            'sku'        => $variant->sku,
            'attributes' => $attributes,
        ];
    }

    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.configrable_products.get', $data['sku']))
        ->assertOK()
        ->assertJsonStructure([
            'sku',
            'parent',
            'family',
            'type',
            'additional',
            'created_at',
            'updated_at',
            'values',
            'super_attributes',
            'variants',
        ])
        ->json();

    $this->assertEquals($response, $data);
});

it('should return error message when creating variant product non existing parent', function () {
    $sku = fake()->word();

    $productData = [
        'parent' => 'not_existing_product1_1',
        'type'   => 'simple',
        'family' => AttributeFamily::first()->code,
        'values' => [
            'common' => [
                'sku' => $sku,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), $productData)
        ->assertNotFound()
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false]);

    $this->assertDatabaseMissing($this->getFullTableName(Product::class), ['sku' => $sku]);
});

it('should return error message when creating variant product without super attributes', function () {
    $configurableProduct = Product::factory()->configurable()->withConfigurableAttributes()->create();

    $sku = fake()->word();

    $productData = [
        'parent' => $configurableProduct->sku,
        'type'   => 'simple',
        'family' => $configurableProduct->attribute_family->code,
        'values' => [
            'common' => [
                'sku' => $sku,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), $productData)
        ->assertNotFound()
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false]);

    $this->assertDatabaseMissing($this->getFullTableName(Product::class), ['sku' => $sku, 'parent_id' => $configurableProduct->id]);
});

it('should create a variant product successfully', function () {
    $configurableProduct = Product::factory()->configurable()->withConfigurableAttributes()->create();

    $sku = fake()->word();

    $variantValues = [];

    foreach ($configurableProduct->super_attributes as $attr) {
        $variantValues[$attr->code] = $attr->options()->first()->code;
    }

    $productData = [
        'parent' => $configurableProduct->sku,
        'type'   => 'simple',
        'family' => $configurableProduct->attribute_family->code,
        'values' => [
            'common' => [
                'sku' => $sku,
            ],
        ],
        'variant' => [
            'attributes' => $variantValues,
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), $productData)
        ->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true, 'message' => trans('admin::app.catalog.products.create-success')]);

    $this->assertDatabaseHas($this->getFullTableName(Product::class), ['sku' => $sku, 'parent_id' => $configurableProduct->id]);

    $configurableProduct->refresh();

    $variant = $configurableProduct->variants()->first()->values;

    foreach ($variantValues as $key => $value) {
        $this->assertEquals(
            $variant['common'][$key] ?? '', $value,
            'The variant configurable attribute value is not saved'
        );
    }
});

it('should create a configurable product successfully', function () {
    $family = AttributeFamily::first();
    $attribute = Attribute::factory()->create(['type' => 'select']);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, $attribute);

    $sku = fake()->word();

    $product = [
        'sku'    => $sku,
        'parent' => null,
        'family' => $family->code,
        'values' => [
            'common' => [
                'sku' => $sku,
            ],
        ],
        'super_attributes' => [$attribute->code],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configrable_products.store'), $product)
        ->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true, 'message' => trans('admin::app.catalog.products.create-success')]);

    $this->assertDatabaseHas($this->getFullTableName(Product::class), ['sku' => $sku]);
});

it('should give warning for super attribute when creating configurable product', function () {
    $family = AttributeFamily::first();
    $sku = fake()->word();

    $product = [
        'sku'    => $sku,
        'parent' => null,
        'family' => $family->code,
        'values' => [
            'common' => [
                'sku' => $sku,
            ],
        ],
        'super_attributes' => [],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configrable_products.store'), $product)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false]);

    $this->assertDatabaseMissing($this->getFullTableName(Product::class), ['sku' => $sku]);
});

it('should give warning if family does not have super attribute when creating configurable product', function () {
    $family = AttributeFamily::factory()->create();
    $attribute = Attribute::factory()->create(['type' => 'select']);

    AttributeFamily::factory()->linkStatusAndSkuToFamily($family);

    $sku = fake()->word();

    $product = [
        'sku'    => $sku,
        'parent' => null,
        'family' => $family->code,
        'values' => [
            'common' => [
                'sku' => $sku,
            ],
        ],
        'super_attributes' => [$attribute->code],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configrable_products.store'), $product)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false]);

    $this->assertDatabaseMissing($this->getFullTableName(Product::class), ['sku' => $sku]);
});

it('should store the price attribute value when updating configurable product', function () {
    $attribute = Attribute::factory()->create(['type' => 'price']);

    $product = Product::factory()->configurable()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = [];

    foreach (core()->getDefaultChannel()->currencies as $currency) {
        $value[$currency->code] = (string) random_int(1, 1000);
    }

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the boolean attribute value when updating configurable product', function () {
    $attribute = Attribute::factory()->create(['type' => 'boolean']);

    $product = Product::factory()->configurable()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = 'true';

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the select attribute value when updating configurable product', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    $product = Product::factory()->configurable()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = $attribute->options->first()->code;

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the multi select attribute value when updating configurable product', function () {
    $attribute = Attribute::factory()->create(['type' => 'multiselect']);

    $product = Product::factory()->configurable()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = implode(',', $attribute->options->pluck('code')->toArray());

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the date time attribute value when updating configurable product', function () {
    $attribute = Attribute::factory()->create(['type' => 'datetime']);

    $product = Product::factory()->configurable()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = '2024-09-04 12:00:00';

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the checkbox attribute value when updating configurable product', function () {
    $attribute = Attribute::factory()->create(['type' => 'checkbox']);

    $product = Product::factory()->configurable()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = $attribute->options->pluck('code')->toArray();

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => implode(',', $value),
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals(implode(',', $value), $product->values['common'][$attributeCode] ?? '');
});

it('should store the image attribute value when updating configurable product', function () {
    $product = Product::factory()->configurable()->create();
    $attribute = Attribute::factory()->create(['type' => 'image']);
    Storage::fake();

    $updatedCategory = [
        'sku'       => $product->sku,
        'file'      => UploadedFile::fake()->create('product.jpg'),
        'attribute' => $attribute->code,
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.media-files.product.store'), $updatedCategory);
    $response->assertStatus(200);

    if (! $response->status() === 200) {
        test()->skip('Media is not exported.');
    }

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $response->json()['data']['filePath'],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});

it('should store the file attribute value when updating configurable product', function () {
    $product = Product::factory()->configurable()->create();
    $attribute = Attribute::factory()->create(['type' => 'file']);
    Storage::fake();

    $updatedCategory = [
        'sku'       => $product->sku,
        'file'      => UploadedFile::fake()->create('product.pdf', 100),
        'attribute' => $attribute->code,
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.media-files.product.store'), $updatedCategory);
    $response->assertStatus(200);

    if (! $response->status() === 200) {
        test()->skip('Media is not exported.');
    }

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $response->json()['data']['filePath'],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});

it('should store the associations value when updating configurable product', function () {
    $configurableProduct = Product::factory()->configurable()->create();

    $product = Product::factory()->configurable()->create();

    $value = [$product->sku];

    $updatedproduct = [
        'sku'    => $configurableProduct->sku,
        'parent' => null,
        'family' => $configurableProduct->attribute_family->code,
        'values' => [
            'common' => [
                'sku'          => $configurableProduct->sku,
            ],
            'associations' => [
                'related_products' => $value,
                'cross_sells'      => $value,
                'up_sells'         => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $configurableProduct->refresh();

    $this->assertArrayHasKey('associations', $configurableProduct->values);

    foreach (['related_products', 'cross_sells', 'up_sells'] as $type) {
        $this->assertEquals($value, $configurableProduct->values['associations'][$type] ?? '');
    }
});
