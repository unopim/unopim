<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

it('should store the channel and locale wise attribute value in product correctly', function () {
    $this->loginAsAdmin();

    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE'])->update(['status' => 1]);

    $newChannel = Channel::factory()->create();

    $newChannelLocale = $newChannel->locales->first()->code;

    $newChannelCode = $newChannel->code;

    $defaultChannel = core()->getDefaultChannel();

    $defaultChannelLocale = $defaultChannel->locales->first()->code;

    $attribute = Attribute::factory()->create(['value_per_locale' => true, 'value_per_channel' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $product = Product::Factory()->simple()->create([
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    $defaultChannelLocale => [
                        $attributeCode => 'Default Channel Value',
                    ],
                ],
            ],
        ],
    ]);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'channel_locale_specific' => [
                $newChannelCode => [
                    $newChannelLocale => [
                        $attributeCode => 'New Channel Locale Value',
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', ['id' => $product->id, 'channel' => $newChannelCode, 'locale' => $newChannelLocale]), $data)
        ->assertRedirect(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $newChannelCode, 'locale' => $newChannelLocale]))
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertArrayHasKey('channel_locale_specific', $product->values);

    $this->assertEquals('New Channel Locale Value', $product->values['channel_locale_specific'][$newChannelCode][$newChannelLocale][$attributeCode] ?? '');

    $this->assertEquals('Default Channel Value', $product->values['channel_locale_specific']['default'][$defaultChannelLocale][$attributeCode] ?? '');
});

it('should store the channel wise attribute value in product correctly', function () {
    $this->loginAsAdmin();

    $newChannel = Channel::factory()->create();

    $newChannelLocale = $newChannel->locales->first()->code;

    $newChannelCode = $newChannel->code;

    $defaultChannel = core()->getDefaultChannel();

    $defaultChannelLocale = $defaultChannel->locales->first()->code;

    $attribute = Attribute::factory()->create(['value_per_channel' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $product = Product::Factory()->simple()->create([
        'values' => [
            'channel_specific' => [
                'default' => [
                    $attributeCode => 'Default Channel Value',
                ],
            ],
        ],
    ]);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'channel_specific' => [
                $newChannelCode => [
                    $attributeCode => 'New Channel Value',
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', ['id' => $product->id, 'channel' => $newChannelCode, 'locale' => $newChannelLocale]), $data)
        ->assertRedirect(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $newChannelCode, 'locale' => $newChannelLocale]))
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertArrayHasKey('channel_specific', $product->values);

    $this->assertEquals('New Channel Value', $product->values['channel_specific'][$newChannelCode][$attributeCode] ?? '');

    $this->assertEquals('Default Channel Value', $product->values['channel_specific']['default'][$attributeCode] ?? '');
});

it('should store the locale wise attribute value in product correctly', function () {
    $this->loginAsAdmin();

    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE'])->update(['status' => 1]);

    $locales = Channel::factory()->create(['code' => 'new_channel_for_testing'])->locales;

    $firstLocale = $locales->first()->code;

    $secondLocale = $locales->last()->code;

    $attribute = Attribute::factory()->create(['value_per_locale' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $product = Product::Factory()->simple()->create([
        'values' => [
            'locale_specific' => [
                $firstLocale => [
                    $attributeCode => 'Default Locale Value',
                ],
            ],
        ],
    ]);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'locale_specific' => [
                $secondLocale => [
                    $attributeCode => 'New Locale Value',
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', ['id' => $product->id, 'channel' => 'new_channel_for_testing', 'locale' => $secondLocale]), $data)
        ->assertRedirect(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => 'new_channel_for_testing', 'locale' => $secondLocale]))
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertArrayHasKey('locale_specific', $product->values);

    $this->assertEquals('New Locale Value', $product->values['locale_specific'][$secondLocale][$attributeCode] ?? '');

    $this->assertEquals('Default Locale Value', $product->values['locale_specific'][$firstLocale][$attributeCode] ?? '');
});

it('should return validation error for unique common attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['is_unique' => 1, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $value = 'Already Present Value';

    Product::factory()->create(['values' => ['common' => [$attributeCode => $value]]]);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertInvalid('values[common]['.$attributeCode.']');

    $product->refresh();

    $this->assertNotEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should return validation error for unique channel and locale wise attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['is_unique' => true, 'value_per_channel' => true, 'value_per_locale' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $localeCode = core()->getDefaultChannel()->locales()->first()->code;

    $value = 'Already Present Value';

    Product::factory()->create([
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    $localeCode => [
                        $attributeCode => $value,
                    ],
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    $localeCode => [
                        $attributeCode => $value,
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertInvalid('values[channel_locale_specific][default]['.$localeCode.']['.$attributeCode.']');

    $product->refresh();

    $this->assertNotEquals($value, $product->values['channel_locale_specific']['default'][$localeCode][$attributeCode] ?? '');
});

it('should return validation error for unique channel wise attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['is_unique' => true, 'value_per_channel' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $value = 'Already Present Value';

    Product::factory()->create([
        'values' => [
            'channel_specific' => [
                'default' => [
                    $attributeCode => $value,
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'channel_specific' => [
                'default' => [
                    $attributeCode => $value,
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertInvalid('values[channel_specific][default]'.'['.$attributeCode.']');

    $product->refresh();

    $this->assertNotEquals($value, $product->values['channel_specific']['default'][$attributeCode] ?? '');
});

it('should return validation error for unique locale wise attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['is_unique' => true, 'value_per_locale' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $localeCode = core()->getDefaultChannel()->locales->first()->code;

    $value = 'Already Present Value';

    Product::factory()->create([
        'values' => [
            'locale_specific' => [
                $localeCode => [
                    $attributeCode => $value,
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'locale_specific' => [
                $localeCode => [
                    $attributeCode => $value,
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertInvalid('values[locale_specific]['.$localeCode.']'.'['.$attributeCode.']');

    $product->refresh();

    $this->assertNotEquals($value, $product->values['locale_specific'][$localeCode][$attributeCode] ?? '');
});

/** Update cases for the simple product different attribute type values */
it('should store the price attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'price']);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = [];

    foreach (core()->getDefaultChannel()->currencies as $currency) {
        $value[$currency->code] = (string) random_int(1, 1000);
    }

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the boolean attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'boolean']);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = 'true';

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the select attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'select']);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = $attribute->options->first()->code;

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the multi select attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'multiselect']);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = implode(',', $attribute->options->pluck('code')->toArray());

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the date time attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'datetime']);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = '2024-09-04 12:00:00';

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the checkbox attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'checkbox']);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = $attribute->options->pluck('code')->toArray();

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals(implode(',', $value), $product->values['common'][$attributeCode] ?? '');
});

it('should store the image attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'image']);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => [UploadedFile::fake()->image('product.jpg')],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});

it('should store the gallery attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'gallery']);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => [
                    UploadedFile::fake()->image('product.jpg'),
                    UploadedFile::fake()->image('product2.jpg'),
                    UploadedFile::fake()->image('product3.jpg'),
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    foreach ($product->values['common'][$attributeCode] as $media) {
        $this->assertTrue(Storage::exists($media));
    }
});

it('should store the file attribute value when updating simple product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'file']);

    $product = Product::factory()->simple()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => [UploadedFile::fake()->create('product.pdf', 100)],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});

it('should store the categories value when updating simple product', function () {
    $this->loginAsAdmin();

    $category = Category::factory()->create();

    $product = Product::factory()->simple()->create();

    $value = [$category->code, $category->parent?->code];

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'categories' => $value,
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['categories'] ?? '');
});

it('should store the associations value when updating simple product', function () {
    $this->loginAsAdmin();

    $products = Product::factory()->simple()->createMany(2);

    $product = $products->first();

    $value = [$products->last()->sku];

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'associations' => [
                'related_products' => $value,
                'cross_sells'      => $value,
                'up_sells'         => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertArrayHasKey('associations', $product->values);

    foreach (['related_products', 'cross_sells', 'up_sells'] as $type) {
        $this->assertEquals($value, $product->values['associations'][$type] ?? '');
    }
});

/** Update cases for the configurable product different attribute type values */
it('should store the price attribute value when updating configurable product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'price']);

    $product = Product::factory()->configurable()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = [];

    foreach (core()->getDefaultChannel()->currencies as $currency) {
        $value[$currency->code] = (string) random_int(1, 1000);
    }

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the boolean attribute value when updating configurable product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'boolean']);

    $product = Product::factory()->configurable()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = 'true';

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the select attribute value when updating configurable product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'select']);

    $product = Product::factory()->configurable()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = $attribute->options->first()->code;

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the multi select attribute value when updating configurable product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'multiselect']);

    $product = Product::factory()->configurable()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = implode(',', $attribute->options->pluck('code')->toArray());

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the date time attribute value when updating configurable product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'datetime']);

    $product = Product::factory()->configurable()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = '2024-09-04 12:00:00';

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the checkbox attribute value when updating configurable product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'checkbox']);

    $product = Product::factory()->configurable()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = $attribute->options->pluck('code')->toArray();

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals(implode(',', $value), $product->values['common'][$attributeCode] ?? '');
});

it('should store the image attribute value when updating configurable product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'image']);

    $product = Product::factory()->configurable()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => [UploadedFile::fake()->image('product.jpg')],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});

it('should store the gallery attribute value when updating configurable product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'gallery']);

    $product = Product::factory()->configurable()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => [
                    UploadedFile::fake()->image('product.jpg'),
                    UploadedFile::fake()->image('product2.jpg'),
                    UploadedFile::fake()->image('product3.jpg'),
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    foreach ($product->values['common'][$attributeCode] as $media) {
        $this->assertTrue(Storage::exists($media));
    }
});

it('should store the file attribute value when updating configurable product', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'file']);

    $product = Product::factory()->configurable()->create();

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    Storage::fake();

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'common' => [
                $attributeCode => [UploadedFile::fake()->create('product.pdf', 100)],
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});

it('should store the categories value when updating configurable product', function () {
    $this->loginAsAdmin();

    $category = Category::factory()->create();

    $product = Product::factory()->configurable()->create();

    $value = [$category->code, $category->parent?->code];

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'categories' => $value,
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertEquals($value, $product->values['categories'] ?? '');
});

it('should store the associations value when updating configurable product', function () {
    $this->loginAsAdmin();

    $products = Product::factory()->configurable()->createMany(2);

    $product = $products->first();

    $value = [$products->last()->sku];

    $data = [
        'sku'    => $product->sku,
        'values' => [
            'associations' => [
                'related_products' => $value,
                'cross_sells'      => $value,
                'up_sells'         => $value,
            ],
        ],
    ];

    $this->put(route('admin.catalog.products.update', $product->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $product->refresh();

    $this->assertArrayHasKey('associations', $product->values);

    foreach (['related_products', 'cross_sells', 'up_sells'] as $type) {
        $this->assertEquals($value, $product->values['associations'][$type] ?? '');
    }
});
